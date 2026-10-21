<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use TCPDF;

class AttendanceController extends Controller
{
    // detect waktu sesi (eg. siang/malam) {#638,2}
    public static function detectSessionType(?string $time = null): string
    {
        $time ??= Carbon::now()->format('H:i:s');

        return ($time >= '06:00:00' && $time <= '18:30:00') ? 'siang' : 'malam';
    }

    // auto status forgot session to pending_approval {#83c,2}
    public static function autoClockOutForgottenSessions(): void
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        Attendance::where(function ($query) {
            $query->where('session_type', 'siang')
                ->orWhereNull('session_type');
        })
            ->where('date', '<', $today)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->where('status', 'normal')
            ->update([
                'status' => 'pending_approval',
            ]);

        // prevent sesi malam expired tengah malam (expired 6:00 am) {#cc0,5}
        $pendingNightSessions = Attendance::where('session_type', 'malam')
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->where('status', 'normal')
            ->get();

        foreach ($pendingNightSessions as $session) {
            $clockIn = $session->clock_in ?? '00:00:00';
            $sessionDate = Carbon::parse($session->date);
            $expiryTime = $clockIn >= '18:30:00'
                ? $sessionDate->copy()->addDay()->setTime(6, 0, 0)
                : $sessionDate->copy()->setTime(6, 0, 0);

            if ($now->greaterThanOrEqualTo($expiryTime)) {
                $session->update([
                    'status' => 'pending_approval',
                ]);
            }
        }
    }

    public function dashboard(): View|RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return redirect()->route('report');
        }

        self::autoClockOutForgottenSessions();

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $activeClockIn = Attendance::where('user_id', $currentUser->id)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->where('status', 'normal')
            ->latest('id')
            ->first();

        $todayAttendance = Attendance::where('user_id', $currentUser->id)
            ->where(function ($query) use ($today, $yesterday, $activeClockIn) {
                $query->where('date', $today);
                if ($activeClockIn && $activeClockIn->date !== $today) {
                    $query->orWhere('id', $activeClockIn->id);
                } else {
                    $query->orWhere(function ($q) use ($yesterday) {
                        $q->where('date', $yesterday)
                            ->where('session_type', 'malam');
                    });
                }
            })
            ->orderBy('date', 'asc')
            ->orderBy('clock_in', 'asc')
            ->get();

        $absentToday = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', true)
            ->first();

        $pendingAppeal = Attendance::where('user_id', $currentUser->id)
            ->where('status', 'pending_approval')
            ->whereNull('proposed_clock_out')
            ->orderBy('date', 'desc')
            ->first();

        return view('dashboard', compact('todayAttendance', 'activeClockIn', 'absentToday', 'pendingAppeal'));
    }

    // validation submit rayuan masa pulang dan alasan {#55d,2}
    public function submitAppeal(Request $request, int $id): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $currentUser->id)
            ->where('status', 'pending_approval')
            ->firstOrFail();

        $request->validate([
            'proposed_clock_out' => 'required',
            'appeal_reason' => [
                'required',
                'string',
                'min:3',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $cleaned = trim((string) $value, " -\t\n\r\0\x0B.");
                    if ($cleaned === '') {
                        $fail('Sila nyatakan alasan yang sah.');
                    }
                },
            ],
        ], [
            'appeal_reason.required' => 'Sila nyatakan alasan anda.',
            'appeal_reason.min' => 'Alasan terlalu pendek. Sila nyatakan alasan sekurang-kurangnya 3 huruf.',
        ]);

        $proposedTime = Carbon::parse((string) $request->proposed_clock_out)->format('H:i:s');

        if (($attendance->session_type ?? 'siang') === 'siang' && $attendance->clock_in) {
            if ($proposedTime <= $attendance->clock_in) {
                return back()->withErrors([
                    'proposed_clock_out' => 'Waktu pulang tidak boleh lebih awal daripada waktu masuk ('.Carbon::parse($attendance->clock_in)->format('h:i A').').',
                ])->withInput();
            }
        }

        $attendance->update([
            'proposed_clock_out' => $proposedTime,
            'appeal_reason' => $request->appeal_reason,
        ]);

        return back()->with('success', 'Maklumat pengesahan waktu keluar berjaya dihantar. Menunggu kelulusan pentadbir.');
    }

    public function clockIn(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan untuk clock in.');
        }

        self::autoClockOutForgottenSessions();

        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();
        $sessionType = self::detectSessionType($currentTime);

        $absentToday = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', true)
            ->exists();

        if ($absentToday) {
            return back()->with('error', 'Anda sudah melaporkan tidak hadir hari ini. Tidak boleh clock in.');
        }

        $existingActive = Attendance::where('user_id', $currentUser->id)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->where('status', 'normal')
            ->first();

        if ($existingActive) {
            return back()->with('error', 'Anda masih mempunyai sesi clock in yang aktif. Sila clock out dahulu.');
        }

        Attendance::create([
            'user_id' => $currentUser->id,
            'date' => $today,
            'clock_in' => $currentTime,
            'remarks' => $request->remarks,
            'is_absent' => false,
            'session_type' => $sessionType,
        ]);

        return back()->with('success', 'Berjaya clock in pada '.$now->format('h:i A').' (Sesi '.ucfirst($sessionType).')');
    }

    // clock out pastu merentasi hari {#5b4,2}
    public function clockOut(): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan untuk clock out.');
        }

        self::autoClockOutForgottenSessions();

        $attendance = Attendance::where('user_id', $currentUser->id)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->where('status', 'normal')
            ->latest('id')
            ->first();

        if ($attendance) {
            $attendance->update([
                'clock_out' => Carbon::now()->toTimeString(),
            ]);

            return back()->with('success', 'Berjaya clock out pada '.Carbon::now()->format('h:i A'));
        }

        return back()->with('error', 'Tiada rekod clock in aktif.');
    }

    public function reportAbsent(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan untuk menghantar keterangan tidak hadir.');
        }

        $request->validate([
            'remarks' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $today = Carbon::today()->toDateString();

        $existingRecord = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', false)
            ->exists();

        if ($existingRecord) {
            return back()->with('error', 'Anda sudah mempunyai rekod clock in hari ini. Tidak boleh lapor tidak hadir.');
        }

        $alreadyAbsent = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', true)
            ->exists();

        if ($alreadyAbsent) {
            return back()->with('error', 'Anda sudah menghantar keterangan tidak hadir untuk hari ini.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $attachmentPath = $this->saveAttachment($request->file('attachment'));
        }

        Attendance::create([
            'user_id' => $currentUser->id,
            'date' => $today,
            'clock_in' => null,
            'clock_out' => null,
            'remarks' => $request->remarks,
            'attachment' => $attachmentPath,
            'is_absent' => true,
        ]);

        return back()->with('success', 'Keterangan tidak hadir berjaya dihantar.');
    }

    public function uploadAbsentAttachment(Request $request): RedirectResponse
    {
        if (Auth::user()->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan.');
        }

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $today = Carbon::today()->toDateString();

        $absentRecord = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->where('is_absent', true)
            ->first();

        if (! $absentRecord) {
            return back()->with('error', 'Tiada rekod tidak hadir untuk hari ini.');
        }

        $attachmentPath = $this->saveAttachment($request->file('attachment'));
        $absentRecord->update([
            'attachment' => $attachmentPath,
        ]);

        return back()->with('success', 'Lampiran bukti (MC / Cuti) berjaya dimuat naik.');
    }

    protected function saveAttachment(\Illuminate\Http\UploadedFile $file): string
    {
        $destination = storage_path('app/public/attachments');
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'dat'));
        $fileName = 'bukti_'.Auth::id().'_'. date('Ymd_His').'_'.uniqid().'.'.$extension;
        $file->move($destination, $fileName);

        return 'attachments/'.$fileName;
    }

    public function monthlyReport(Request $request): View
    {
        self::autoClockOutForgottenSessions();

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $sessionType = (string) $request->get('session_type', 'all');

        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            $allStaffs = User::where('role', 'staff')->orderBy('name', 'asc')->get();

            $selectedUserId = $request->filled('user_id')
                ? (int) $request->user_id
                : optional($allStaffs->first())->id;

            $selectedUser = $selectedUserId ? User::where('role', 'staff')->where('id', $selectedUserId)->first() : null;
        } else {
            $allStaffs = [];
            $selectedUser = $currentUser;
        }

        $calculated = $selectedUser ? $this->calculateMonthlyData($selectedUser, $month, $year, $sessionType) : [
            'reportData' => [],
            'grandTotalFormatted' => '00:00',
            'totalSkor' => 0.0,
        ];

        $reportData = $calculated['reportData'];
        $grandTotalFormatted = $calculated['grandTotalFormatted'];
        $totalSkor = $calculated['totalSkor'];

        return view('reports.monthly', compact(
            'reportData', 'grandTotalFormatted', 'totalSkor', 'month', 'year', 'sessionType', 'selectedUser', 'allStaffs'
        ));
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        self::autoClockOutForgottenSessions();

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $sessionType = (string) $request->get('session_type', 'all');

        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            $allStaffs = User::where('role', 'staff')->orderBy('name', 'asc')->get();
            $selectedUserId = $request->filled('user_id')
                ? (int) $request->user_id
                : optional($allStaffs->first())->id;

            $selectedUser = $selectedUserId ? User::where('role', 'staff')->where('id', $selectedUserId)->first() : null;
        } else {
            $selectedUser = $currentUser;
        }

        if (! $selectedUser) {
            return back()->with('error', 'Kakitangan tidak dijumpai.');
        }

        $calculated = $this->calculateMonthlyData($selectedUser, $month, $year, $sessionType);
        $reportData = $calculated['reportData'];
        $grandTotalFormatted = $calculated['grandTotalFormatted'];
        $totalSkor = $calculated['totalSkor'];

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('Sistem Kehadiran');
        $pdf->SetAuthor('HR');
        $pdf->SetTitle('Laporan Kehadiran - '.$selectedUser->name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        $html = view('reports.pdf', compact(
            'selectedUser', 'month', 'year', 'sessionType', 'reportData', 'grandTotalFormatted', 'totalSkor'
        ))->render();

        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'Laporan_Kehadiran_'.$selectedUser->staff_id.'_'.$month.'_'.$year.'.pdf';

        return response($pdf->Output($fileName, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * @return array{reportData: array<int, array{hb: int, keterangan: string, sessions: array<int, array{masuk: string, keluar: string, tempoh: string, session_type: string, status: string, has_proposed: bool}>, jumlah_masa: string, skor: string}>, grandTotalFormatted: string, totalSkor: float}
     */
    protected function calculateMonthlyData(User $user, int $month, int $year, string $sessionFilter = 'all'): array
    {
        $reportData = [];
        $grandTotalMinutes = 0;
        $totalSkor = 0.0;

        $query = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($sessionFilter === 'siang') {
            $query->where(function ($q) {
                $q->where('session_type', 'siang')
                    ->orWhereNull('session_type');
            });
        } elseif ($sessionFilter === 'malam') {
            $query->where('session_type', 'malam');
        }

        $records = $query->orderBy('date', 'asc')
            ->orderBy('clock_in', 'asc')
            ->get()
            ->groupBy(fn (Attendance $rec): string => Carbon::parse($rec->date)->toDateString());

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $today = Carbon::today()->toDateString();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateString = Carbon::createFromDate($year, $month, $day)->toDateString();
            $dayRecords = $records->get($dateString);

            if (! $dayRecords || $dayRecords->isEmpty()) {
                $isNotPassed = $dateString >= $today;

                $reportData[] = [
                    'hb' => $day,
                    'keterangan' => $isNotPassed ? '-' : 'Tidak hadir',
                    'attachments' => [],
                    'sessions' => [
                        [
                            'masuk' => '-',
                            'keluar' => '-',
                            'tempoh' => '-',
                            'session_type' => '-',
                            'status' => 'normal',
                            'has_proposed' => false,
                        ],
                    ],
                    'jumlah_masa' => $isNotPassed ? '-' : '00:00',
                    'skor' => $isNotPassed ? '-' : '0.0',
                ];

                continue;
            }

            $dailyMinutes = 0;
            $sessions = [];
            $keteranganArr = [];
            $attachmentsArr = [];
            $isAbsentDay = false;

            foreach ($dayRecords as $rec) {
                if (! empty($rec->remarks)) {
                    $keteranganArr[] = $rec->remarks;
                }

                if (! empty($rec->attachment)) {
                    $attachmentsArr[] = $rec->attachment;
                }

                $recSessionType = $rec->session_type ?? 'siang';

                if ($rec->is_absent) {
                    $isAbsentDay = true;
                    $sessions[] = [
                        'masuk' => '-',
                        'keluar' => '-',
                        'tempoh' => '-',
                        'session_type' => $recSessionType,
                        'status' => 'normal',
                        'has_proposed' => false,
                    ];

                    continue;
                }

                if ($rec->status === 'pending_approval') {
                    $start = $rec->clock_in ? Carbon::parse($rec->date.' '.$rec->clock_in) : null;
                    $proposedStr = 'Tidak Clock Out';
                    if ($rec->proposed_clock_out && $start) {
                        $proposed = Carbon::parse($rec->date.' '.$rec->proposed_clock_out);
                        if ($proposed->lessThan($start)) {
                            $proposed->addDay();
                        }
                        $proposedStr = $proposed->format('h:i A');
                    }
                    $keteranganArr[] = 'Perlu pengesahan keluar';

                    $sessions[] = [
                        'masuk' => $start ? $start->format('h:i A') : '-',
                        'keluar' => $proposedStr,
                        'tempoh' => '-',
                        'session_type' => $recSessionType,
                        'status' => 'pending_approval',
                        'has_proposed' => ! empty($rec->proposed_clock_out),
                    ];

                    continue;
                }

                if ($rec->status === 'rejected') {
                    $start = $rec->clock_in ? Carbon::parse($rec->date.' '.$rec->clock_in) : null;
                    $keteranganArr[] = 'Ditolak Admin';

                    $sessions[] = [
                        'masuk' => $start ? $start->format('h:i A') : '-',
                        'keluar' => '-',
                        'tempoh' => '-',
                        'session_type' => $recSessionType,
                        'status' => 'rejected',
                        'has_proposed' => ! empty($rec->proposed_clock_out),
                    ];

                    continue;
                }

                // pengiraan masa merentasi hari , skor {#e02,67}
                if ($rec->clock_in && $rec->clock_out) {
                    $start = Carbon::parse($rec->date.' '.$rec->clock_in);
                    $end = Carbon::parse($rec->date.' '.$rec->clock_out);
                    if ($end->lessThan($start)) {
                        $end->addDay();
                    }
                    $diffMinutes = (int) abs($start->diffInMinutes($end));
                    $dailyMinutes += $diffMinutes;

                    if ($rec->status === 'approved') {
                        $keteranganArr[] = 'Disahkan Admin';
                    }

                    $sessions[] = [
                        'masuk' => $start->format('h:i A'),
                        'keluar' => $end->format('h:i A'),
                        'tempoh' => sprintf('%02d:%02d', floor($diffMinutes / 60), $diffMinutes % 60),
                        'session_type' => $recSessionType,
                        'status' => $rec->status,
                        'has_proposed' => ! empty($rec->proposed_clock_out),
                    ];
                } elseif ($rec->clock_in) {
                    $start = Carbon::parse($rec->date.' '.$rec->clock_in);
                    $sessions[] = [
                        'masuk' => $start->format('h:i A'),
                        'keluar' => 'Sedang Bertugas',
                        'tempoh' => '-',
                        'session_type' => $recSessionType,
                        'status' => 'normal',
                        'has_proposed' => false,
                    ];
                } else {
                    $sessions[] = [
                        'masuk' => '-',
                        'keluar' => '-',
                        'tempoh' => '-',
                        'session_type' => $recSessionType,
                        'status' => 'normal',
                        'has_proposed' => false,
                    ];
                }
            }

            if ($isAbsentDay) {
                $keterangan = ! empty($keteranganArr) ? implode(', ', $keteranganArr) : 'Tidak hadir';
                $dailyMinutes = 0;
                $skor = 0.0;
            } elseif (in_array('Perlu pengesahan keluar', $keteranganArr, true)) {
                $keterangan = 'Perlu pengesahan keluar';
                $dailyMinutes = 0;
                $skor = 0.0;
            } elseif (in_array('Ditolak Admin', $keteranganArr, true)) {
                $keterangan = 'Ditolak Admin';
                $dailyMinutes = 0;
                $skor = 0.0;
            } else {
                $keterangan = ! empty($keteranganArr) ? implode(', ', $keteranganArr) : 'Hadir';

                $hours = $dailyMinutes / 60;
                if ($hours >= 5.0) {
                    $skor = 1.0;
                } elseif ($hours >= 3.0) {
                    $skor = 0.5;
                } else {
                    $skor = 0.0;
                }
            }

            $grandTotalMinutes += $dailyMinutes;
            $totalSkor += $skor;

            $reportData[] = [
                'hb' => $day,
                'keterangan' => $keterangan,
                'attachments' => $attachmentsArr,
                'sessions' => $sessions,
                'jumlah_masa' => sprintf('%02d:%02d', floor($dailyMinutes / 60), $dailyMinutes % 60),
                'skor' => number_format($skor, 1),
            ];
        }

        $grandTotalFormatted = sprintf('%02d:%02d', floor($grandTotalMinutes / 60), $grandTotalMinutes % 60);

        return [
            'reportData' => $reportData,
            'grandTotalFormatted' => $grandTotalFormatted,
            'totalSkor' => $totalSkor,
        ];
    }
}
