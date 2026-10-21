@extends('layouts.app')

@section('content')
@if($pendingAppeal)
    <div class="alert alert-warning border shadow-sm rounded-4 p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border: 1.5px solid #000000 !important; background-color: #fefce8;">
        <div>
            <strong class="d-block" style="color: var(--ln-dark);">
                Anda tidak clock out semalam, sila nyatakan masa anda pulang dan sebab.
            </strong>
            <span class="small" style="color: var(--ln-text-muted);">
                Tarikh: {{ \Carbon\Carbon::parse($pendingAppeal->date)->format('d/m/Y') }} (Waktu Masuk: {{ $pendingAppeal->clock_in ? \Carbon\Carbon::parse($pendingAppeal->clock_in)->format('h:i A') : '-' }})
            </span>
        </div>
        <div>
            <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm" style="border: 1.5px solid #000000;" data-bs-toggle="modal" data-bs-target="#forgotClockOutModal">
                Isi Pengesahan Keluar &rarr;
            </button>
        </div>
    </div>
@endif

<div class="card mb-4 rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
    <div class="card-body p-3 px-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-5">
                <span class="small fw-semibold d-block mb-1" style="font-size: 0.78rem; color: var(--ln-text-muted);">Waktu semasa</span>
                <div id="liveClock" class="fs-3 fw-bold font-monospace lh-1" style="letter-spacing: 0.5px; color: var(--ln-dark);">--:--:-- --</div>
            </div>

            <div class="col-12 col-md-7 d-flex flex-wrap justify-content-md-end align-items-center gap-3">
                <div class="px-3 py-2 rounded-3 border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                    <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Waktu bertugas (2 Sesi Auto)</span>
                    <span class="fw-bold small font-monospace" style="color: var(--ln-dark);">Siang (06:00 AM – 06:30 PM) | Malam (06:30 PM – 06:00 AM)</span>
                </div>
                <div class="text-md-end">
                    <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Tarikh hari ini</span>
                    <span class="fs-6 fw-bold" style="color: var(--ln-dark);" id="liveDate">{{ \Carbon\Carbon::now()->translatedFormat('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-5">
        <div class="card h-100 rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
            <div class="card-header bg-transparent py-3 px-4 border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Status Bertugas Hari Ini</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between mb-4" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                    <div>
                        <span class="small d-block" style="font-size: 0.75rem; color: var(--ln-text-muted);">Nama kakitangan</span>
                        <span class="fw-bold fs-6" style="color: var(--ln-dark);">{{ Auth::user()->name }}</span>
                    </div>
                    <div class="text-end">
                        <span class="small d-block" style="font-size: 0.75rem; color: var(--ln-text-muted);">Staff ID</span>
                        <span class="badge badge-ln-neon fw-semibold">{{ Auth::user()->staff_id }}</span>
                    </div>
                </div>

                <div class="my-auto text-center">
                    @if($activeClockIn)
                        <div class="p-4 mb-3 rounded-4" style="background-color: rgba(210, 255, 0, 0.15); border: 1.5px solid #000000;">
                            <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-1 rounded-pill small fw-bold" style="background-color: #0f172a; color: var(--ln-neon); border: 1.5px solid #000000;">
                                <span>Aktif bertugas</span>
                                <span class="badge {{ ($activeClockIn->session_type ?? 'siang') === 'malam' ? 'bg-dark text-white' : 'bg-warning text-dark' }} rounded-pill" style="border: 1px solid #ffffff; font-size: 0.68rem;">
                                    Sesi {{ ucfirst($activeClockIn->session_type ?? 'siang') }}
                                </span>
                            </div>
                            <div class="small mb-3" style="color: var(--ln-text-muted);">
                                Clock in: <strong style="color: var(--ln-dark);">{{ \Carbon\Carbon::parse($activeClockIn->clock_in)->format('h:i A') }}</strong>
                                @if($activeClockIn->date !== \Carbon\Carbon::today()->toDateString())
                                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($activeClockIn->date)->format('d/m') }}</span>
                                @endif
                            </div>
                            <div class="pt-2 border-top" style="border-top: 1.5px solid #000000 !important;">
                                <span class="small d-block mb-1" style="font-size: 0.78rem; color: var(--ln-text-muted);">Tempoh sesi semasa:</span>
                                <span id="activeTimer" class="fs-1 fw-bold font-monospace lh-1 d-inline-block" style="color: var(--ln-dark);" data-clockin="{{ \Carbon\Carbon::parse($activeClockIn->date . ' ' . $activeClockIn->clock_in, 'Asia/Kuala_Lumpur')->toIso8601String() }}">00:00:00</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clock.out') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg w-100 py-3 fw-bold rounded-pill shadow-sm" style="background-color: #ef4444; border: 1.5px solid #000000;">
                                Clock out
                            </button>
                        </form>
                    @elseif($absentToday)
                        <div class="p-4 mb-3 rounded-4 text-start" style="background-color: #fefce8; border: 1.5px solid #000000;">
                            <div class="d-inline-flex align-items-center gap-2 mb-2 bg-warning text-dark px-3 py-1 rounded-pill small fw-bold" style="border: 1.5px solid #000000;">
                                Tidak hadir
                            </div>
                            <p class="small mb-2" style="color: var(--ln-text-muted);">Anda telah melaporkan tidak hadir untuk hari ini.</p>
                            <div class="small p-2 rounded-3 border bg-white mb-3" style="border: 1.5px solid #000000 !important;">
                                <strong style="color: var(--ln-dark);">Keterangan:</strong> <em>{{ $absentToday->remarks }}</em>
                            </div>

                            @if($absentToday->attachment)
                                <div class="mb-3">
                                    <a href="{{ asset('storage/' . $absentToday->attachment) }}" target="_blank" class="btn btn-sm btn-ln-neon w-100 rounded-pill py-1" style="border: 1.5px solid #000000 !important;">
                                        📎 Lihat Lampiran Bukti (MC / Cuti)
                                    </a>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('report.absent.attachment') }}" enctype="multipart/form-data" class="pt-2 border-top" style="border-top: 1px dashed #000000 !important;">
                                @csrf
                                <label class="small fw-semibold d-block mb-1" style="font-size: 0.75rem; color: var(--ln-dark);">
                                    {{ $absentToday->attachment ? 'Tukar lampiran bukti (PDF/Gambar):' : 'Muat naik sijil MC / surat cuti (PDF/Gambar):' }}
                                </label>
                                <div class="d-flex gap-2">
                                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="form-control form-control-sm" style="border: 1.5px solid #000000 !important;">
                                    <button type="submit" class="btn btn-dark btn-sm px-3 fw-bold flex-shrink-0" style="border: 1.5px solid #000000 !important;">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        @php
                            $detectedSession = \App\Http\Controllers\AttendanceController::detectSessionType();
                        @endphp
                        <div class="p-3 mb-3 rounded-3 text-start border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary rounded-pill" style="border: 1.5px solid #000000;">Belum Clock In</span>
                                </div>
                                <div>
                                    <span class="badge {{ $detectedSession === 'malam' ? 'bg-dark text-white' : 'bg-warning text-dark' }} rounded-pill px-2 py-1" style="border: 1.5px solid #000000;">
                                        Auto: Sesi {{ ucfirst($detectedSession) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clock.in') }}" class="mb-4">
                            @csrf
                            <button type="submit" class="btn btn-ln-neon btn-lg w-100 py-3 rounded-pill shadow-sm" style="border: 1.5px solid #000000 !important;">
                                Clock in (Sesi {{ ucfirst($detectedSession) }})
                            </button>
                        </form>

                        <div class="border rounded-3 p-3 text-start" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold" style="color: var(--ln-dark);">Lapor tidak hadir / Cuti / MC:</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <button type="button" class="btn btn-sm badge bg-white text-dark border py-1 px-2" style="border: 1px solid #000000 !important; font-weight: 600;" onclick="document.getElementById('remarks').value='Cuti Sakit (MC)'; document.getElementById('remarks').focus();">🏥 Cuti Sakit (MC)</button>
                                <button type="button" class="btn btn-sm badge bg-white text-dark border py-1 px-2" style="border: 1px solid #000000 !important; font-weight: 600;" onclick="document.getElementById('remarks').value='Cuti Tahunan (AL)'; document.getElementById('remarks').focus();">🌴 Cuti Tahunan</button>
                                <button type="button" class="btn btn-sm badge bg-white text-dark border py-1 px-2" style="border: 1px solid #000000 !important; font-weight: 600;" onclick="document.getElementById('remarks').value='Cuti Kecemasan (EL)'; document.getElementById('remarks').focus();">🚨 Kecemasan (EL)</button>
                            </div>
                            <form method="POST" action="{{ route('report.absent') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="remarks" id="remarks" class="form-control form-control-sm" placeholder="Sebab (Cth: Cuti Sakit MC, Cuti Tahunan)" required style="border: 1.5px solid #000000 !important;">
                                </div>
                                <div class="mb-2">
                                    <label for="attachment" class="form-label small mb-1" style="font-size: 0.72rem; color: var(--ln-text-muted);">
                                        📎 Lampirkan Sijil Sakit (MC) / Surat Cuti <span class="text-muted">(Pilihan - PDF/JPG/PNG)</span>
                                    </label>
                                    <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-control form-control-sm" style="border: 1.5px solid #000000 !important;">
                                </div>
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-2" style="border: 1.5px solid #000000 !important;">
                                    Hantar keterangan & lampiran
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="d-grid mt-4 pt-3 border-top text-center" style="border-top: 1.5px solid #000000 !important;">
                    <a href="{{ route('report') }}" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold" style="color: var(--ln-dark);">
                        Lihat rekod kehadiran bulanan &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-7">
        <div class="card h-100 rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
            <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="border-bottom: 1.5px solid #000000 !important;">
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Sesi Kehadiran Terkini</h5>
                <span class="badge badge-ln-neon rounded-pill px-3">{{ count($todayAttendance) }} sesi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                        <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                            <tr>
                                <th style="width: 8%;">#</th>
                                <th style="width: 16%;">Sesi</th>
                                <th style="width: 20%;">Masuk</th>
                                <th style="width: 20%;">Keluar</th>
                                <th style="width: 16%;">Tempoh</th>
                                <th style="width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAttendance as $idx => $att)
                                @php
                                    $diffFormatted = '-';
                                    $isCrossMidnight = false;
                                    if ($att->clock_in && $att->clock_out) {
                                        $in = \Carbon\Carbon::parse($att->date . ' ' . $att->clock_in);
                                        $out = \Carbon\Carbon::parse($att->date . ' ' . $att->clock_out);
                                        if ($out->lessThan($in)) {
                                            $out->addDay();
                                            $isCrossMidnight = true;
                                        }
                                        $mins = abs($in->diffInMinutes($out));
                                        $diffFormatted = sprintf('%02d:%02d', floor($mins / 60), $mins % 60);
                                    }
                                @endphp
                                <tr>
                                    <td class="small fw-bold" style="color: var(--ln-text-muted);">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge {{ ($att->session_type ?? 'siang') === 'malam' ? 'bg-dark text-white' : 'bg-warning-subtle text-dark' }} rounded-pill" style="border: 1px solid #000000; font-size: 0.72rem;">
                                            {{ ucfirst($att->session_type ?? 'siang') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold font-monospace" style="color: var(--ln-dark);">
                                            {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('h:i A') : '-' }}
                                        </span>
                                        @if($att->date !== \Carbon\Carbon::today()->toDateString())
                                            <div class="small text-muted" style="font-size: 0.68rem;">{{ \Carbon\Carbon::parse($att->date)->format('d/m') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($att->status === 'pending_approval')
                                            @if($att->proposed_clock_out)
                                                <span class="fw-bold font-monospace text-danger">
                                                    {{ \Carbon\Carbon::parse($att->proposed_clock_out)->format('h:i A') }}
                                                </span>
                                                <div class="text-danger fw-semibold" style="font-size: 0.68rem;">*Rayuan (Belum Sah)</div>
                                            @else
                                                <span class="badge bg-warning text-dark border border-dark rounded-pill">Tidak Clock Out</span>
                                            @endif
                                        @elseif($att->status === 'rejected')
                                            <span class="badge bg-danger text-white rounded-pill">Ditolak</span>
                                        @elseif($att->clock_out)
                                            <span class="fw-bold font-monospace text-danger">
                                                {{ \Carbon\Carbon::parse($att->clock_out)->format('h:i A') }}
                                            </span>
                                        @elseif($att->is_absent)
                                            <span style="color: var(--ln-text-muted);">-</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">Sedang bertugas</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold font-monospace" style="color: var(--ln-dark);">{{ $diffFormatted }}</td>
                                    <td class="text-start small">
                                        @if($att->is_absent)
                                            <span class="badge bg-danger rounded-pill">Tidak hadir</span>
                                            <div class="small mt-1" style="color: var(--ln-text-muted);"><em>{{ $att->remarks }}</em></div>
                                            @if($att->attachment)
                                                <a href="{{ asset('storage/' . $att->attachment) }}" target="_blank" class="badge badge-ln-neon text-decoration-none mt-1 d-inline-block">
                                                    📎 Lihat Bukti
                                                </a>
                                            @endif
                                        @elseif($att->status === 'pending_approval')
                                            <span class="badge bg-warning text-dark border border-dark rounded-pill">Perlu pengesahan keluar</span>
                                        @elseif($att->clock_out)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Selesai</span>
                                        @elseif($att->clock_in)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">Sedang bertugas</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-5" style="color: var(--ln-text-muted);">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                            <span class="small">Tiada rekod kehadiran direkodkan untuk hari ini.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingAppeal)
<div class="modal fade" id="forgotClockOutModal" tabindex="-1" aria-labelledby="forgotClockOutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow" style="border: 1.5px solid #000000 !important;">
            <div class="modal-header border-bottom py-3" style="border-bottom: 1.5px solid #000000 !important;">
                <h5 class="modal-title fw-bold fs-6" id="forgotClockOutModalLabel" style="color: var(--ln-dark);">
                    Pengesahan Keluar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ route('attendance.submitAppeal', $pendingAppeal->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small rounded-3 mb-3 fw-semibold" style="border: 1px solid #000000; background-color: #fefce8; color: var(--ln-dark);">
                        Anda tidak clock out semalam, sila nyatakan masa anda pulang dan sebab.
                    </div>
                    <p class="small mb-3" style="color: var(--ln-text-muted);">
                        Tarikh rekod: <strong>{{ \Carbon\Carbon::parse($pendingAppeal->date)->format('d/m/Y') }}</strong> (Waktu Masuk: <strong>{{ $pendingAppeal->clock_in ? \Carbon\Carbon::parse($pendingAppeal->clock_in)->format('h:i A') : '-' }}</strong>)
                    </p>

                    <div class="mb-3">
                        <label for="proposed_clock_out" class="form-label small fw-bold" style="color: var(--ln-dark);">Masa Anda Pulang:</label>
                        <input type="time" class="form-control @error('proposed_clock_out') is-invalid @enderror" id="proposed_clock_out" name="proposed_clock_out" value="{{ old('proposed_clock_out', '16:00') }}" required style="border: 1.5px solid #000000 !important;">
                        @error('proposed_clock_out')
                            <div class="text-danger small mt-1 fw-semibold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="appeal_reason" class="form-label small fw-bold" style="color: var(--ln-dark);">
                            Sebab / Alasan <span class="text-danger">*</span>:
                        </label>
                        <input type="text" class="form-control @error('appeal_reason') is-invalid @enderror" id="appeal_reason" name="appeal_reason" value="{{ old('appeal_reason') }}" placeholder="Cth: Terlupa clock out kerana tergesa-gesa balik" minlength="3" required pattern="^(?![\s\-\.]+$).+" title="Sila nyatakan alasan yang sah. Tidak boleh dibiarkan kosong atau diisi dengan tanda (-)." style="border: 1.5px solid #000000 !important;">
                        @error('appeal_reason')
                            <div class="text-danger small mt-1 fw-semibold">{{ $message }}</div>
                        @enderror
                        <div class="form-text small" style="font-size: 0.73rem; color: var(--ln-text-muted);">
                            Wajib dinyatakan alasan sebenar. Tidak boleh dibiarkan kosong atau diisi dengan tanda (-).
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2" style="border-top: 1.5px solid #000000 !important;">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" style="border: 1.5px solid #000000;">Tutup</button>
                    <button type="submit" class="btn btn-ln-neon btn-sm rounded-pill px-4" style="border: 1.5px solid #000000;">Hantar Pengesahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->has('proposed_clock_out') || $errors->has('appeal_reason'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModal = new bootstrap.Modal(document.getElementById('forgotClockOutModal'));
            myModal.show();
        });
    </script>
@endif
@endif

<script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', {
            timeZone: 'Asia/Kuala_Lumpur',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            clockEl.textContent = timeStr;
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    function updateSessionTimer() {
        const timerEl = document.getElementById('activeTimer');
        if (!timerEl || !timerEl.dataset.clockin) {
            return;
        }
        const clockInDate = new Date(timerEl.dataset.clockin).getTime();
        if (isNaN(clockInDate)) {
            return;
        }
        const now = new Date().getTime();
        const diffMs = Math.max(0, now - clockInDate);
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);

        timerEl.textContent = String(hours).padStart(2, '0') + ':' +
                              String(minutes).padStart(2, '0') + ':' +
                              String(seconds).padStart(2, '0');
    }
    updateSessionTimer();
    setInterval(updateSessionTimer, 1000);

    @if($pendingAppeal)
        document.addEventListener('DOMContentLoaded', function () {
            const appealModalEl = document.getElementById('forgotClockOutModal');
            if (appealModalEl) {
                const appealModal = new bootstrap.Modal(appealModalEl);
                appealModal.show();
            }
        });
    @endif
</script>
@endsection