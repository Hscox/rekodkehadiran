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
    public static function autoClockOutForgottenSessions(): void
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        Attendance::where('date', '<', $today)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->update([
                'clock_out' => '18:00:00',
            ]);

        if ($nowTime >= '18:00:00') {
            Attendance::where('date', $today)
                ->whereNull('clock_out')
                ->where('is_absent', false)
                ->update([
                    'clock_out' => '18:00:00',
                ]);
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

        $todayAttendance = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->orderBy('clock_in', 'asc')
            ->get();

        $activeClockIn = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->where('is_absent', false)
            ->first();

        $absentToday = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', true)
            ->first();

        return view('dashboard', compact('todayAttendance', 'activeClockIn', 'absentToday'));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan untuk clock in.');
        }

        self::autoClockOutForgottenSessions();

        $today = Carbon::today()->toDateString();

        $absentToday = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->where('is_absent', true)
            ->exists();

        if ($absentToday) {
            return back()->with('error', 'Anda sudah melaporkan tidak hadir hari ini. Tidak boleh clock in.');
        }

        $existingActive = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->first();

        if ($existingActive) {
            return back()->with('error', 'Anda masih mempunyai sesi clock in yang aktif. Sila clock out dahulu.');
        }

        Attendance::create([
            'user_id' => $currentUser->id,
            'date' => $today,
            'clock_in' => Carbon::now()->toTimeString(),
            'remarks' => $request->remarks,
            'is_absent' => false,
        ]);

        return back()->with('success', 'Berjaya clock in pada '.Carbon::now()->format('h:i A'));
    }

    public function clockOut(): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            return back()->with('error', 'Akaun Admin tidak dibenarkan untuk clock out.');
        }

        self::autoClockOutForgottenSessions();

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $currentUser->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->latest()
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

        Attendance::create([
            'user_id' => $currentUser->id,
            'date' => $today,
            'clock_in' => null,
            'clock_out' => null,
            'remarks' => $request->remarks,
            'is_absent' => true,
        ]);

        return back()->with('success', 'Keterangan tidak hadir berjaya dihantar.');
    }

    public function monthlyReport(Request $request): View
    {
        self::autoClockOutForgottenSessions();

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);

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

        $calculated = $selectedUser ? $this->calculateMonthlyData($selectedUser, $month, $year) : [
            'reportData' => [],
            'grandTotalFormatted' => '00:00',
            'totalSkor' => 0.0,
        ];

        $reportData = $calculated['reportData'];
        $grandTotalFormatted = $calculated['grandTotalFormatted'];
        $totalSkor = $calculated['totalSkor'];

        return view('reports.monthly', compact(
            'reportData', 'grandTotalFormatted', 'totalSkor', 'month', 'year', 'selectedUser', 'allStaffs'
        ));
    }

    // TCPDF logic - handles generating a downloadable pdf report {#129,62}
    public function exportPdf(Request $request): Response|RedirectResponse
    {
        self::autoClockOutForgottenSessions();

        // checks for month and year in the request, defaults to current month and year if not provided
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // checks if the current user is an admin, if so, retrieves all staff users and selects the user based on the request or defaults to the first staff user
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

        // calculates the monthly data for the selected user, month, and year
        $calculated = $this->calculateMonthlyData($selectedUser, $month, $year);
        $reportData = $calculated['reportData'];
        $grandTotalFormatted = $calculated['grandTotalFormatted'];
        $totalSkor = $calculated['totalSkor'];

        // initializes a new TCPDF instance and sets up the PDF document properties
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // sets the document information and formatting options for the PDF
        $pdf->SetCreator('Sistem Kehadiran');
        $pdf->SetAuthor('HR');
        $pdf->SetTitle('Laporan Kehadiran - '.$selectedUser->name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15); // left, top, right margins
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // takes blade file (resources/views/reports/pdf.blade.php) and renders it into HTML, then writes that HTML into the PDF document
        $html = view('reports.pdf', compact(
            'selectedUser', 'month', 'year', 'reportData', 'grandTotalFormatted', 'totalSkor'
        ))->render();

        // html is written to the PDF document
        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'Laporan_Kehadiran_'.$selectedUser->staff_id.'_'.$month.'_'.$year.'.pdf';

        return response($pdf->Output($fileName, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * @return array{reportData: array<int, array{hb: int, keterangan: string, sessions: array<int, array{masuk: string, keluar: string, tempoh: string}>, jumlah_masa: string, skor: string}>, grandTotalFormatted: string, totalSkor: float}
     */
    protected function calculateMonthlyData(User $user, int $month, int $year): array
    {
        $reportData = [];
        $grandTotalMinutes = 0;
        $totalSkor = 0.0;

        $records = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->orderBy('clock_in', 'asc')
            ->get()
            ->groupBy('date');

        foreach ($records as $date => $dayRecords) {
            $dailyMinutes = 0;
            $sessions = [];
            $keteranganArr = [];
            $isAbsentDay = false;

            foreach ($dayRecords as $rec) {
                if (! empty($rec->remarks)) {
                    $keteranganArr[] = $rec->remarks;
                }

                if ($rec->is_absent) {
                    $isAbsentDay = true;
                    $sessions[] = [
                        'masuk' => '-',
                        'keluar' => '-',
                        'tempoh' => '-',
                    ];

                    continue;
                }

                if ($rec->clock_in && $rec->clock_out) {
                    $start = Carbon::parse($rec->date.' '.$rec->clock_in);
                    $end = Carbon::parse($rec->date.' '.$rec->clock_out);
                    if ($end->lessThan($start)) {
                        $end->addDay();
                    }
                    $diffMinutes = (int) abs($start->diffInMinutes($end));
                    $dailyMinutes += $diffMinutes;

                    $sessions[] = [
                        'masuk' => $start->format('h:i A'),
                        'keluar' => $end->format('h:i A'),
                        'tempoh' => sprintf('%02d:%02d', floor($diffMinutes / 60), $diffMinutes % 60),
                    ];
                } elseif ($rec->clock_in) {
                    $start = Carbon::parse($rec->date.' '.$rec->clock_in);
                    $sessions[] = [
                        'masuk' => $start->format('h:i A'),
                        'keluar' => 'Sedang Bertugas',
                        'tempoh' => '-',
                    ];
                } else {
                    $sessions[] = [
                        'masuk' => '-',
                        'keluar' => '-',
                        'tempoh' => '-',
                    ];
                }
            }

            if ($isAbsentDay) {
                $keterangan = ! empty($keteranganArr) ? implode(', ', $keteranganArr) : 'Tidak hadir';
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
                'hb' => Carbon::parse($date)->day,
                'keterangan' => $keterangan,
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
