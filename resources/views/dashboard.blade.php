@extends('layouts.app')

@section('content')
<div class="card mb-4 rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
    <div class="card-body p-3 px-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-5">
                <span class="small fw-semibold d-block mb-1" style="font-size: 0.78rem; color: var(--ln-text-muted);">Waktu semasa</span>
                <div id="liveClock" class="fs-3 fw-bold font-monospace lh-1" style="letter-spacing: 0.5px; color: var(--ln-dark);">--:--:-- --</div>
            </div>

            <div class="col-12 col-md-7 d-flex flex-wrap justify-content-md-end align-items-center gap-3">
                <div class="px-3 py-2 rounded-3 border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                    <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Waktu bertugas</span>
                    <span class="fw-bold small font-monospace" style="color: var(--ln-dark);">08:00 AM – 06:00 PM</span>
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
                            <div class="d-inline-flex align-items-center mb-2 px-3 py-1 rounded-pill small fw-bold" style="background-color: #0f172a; color: var(--ln-neon); border: 1.5px solid #000000;">
                                Aktif bertugas
                            </div>
                            <div class="small mb-3" style="color: var(--ln-text-muted);">
                                Clock in pada: <strong style="color: var(--ln-dark);">{{ \Carbon\Carbon::parse($activeClockIn->clock_in)->format('h:i A') }}</strong>
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
                            <div class="small p-2 rounded-3 border bg-white" style="border: 1.5px solid #000000 !important;">
                                <strong style="color: var(--ln-dark);">Keterangan:</strong> <em>{{ $absentToday->remarks }}</em>
                            </div>
                        </div>
                    @else
                        <div class="p-3 mb-3 rounded-3 text-start border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary rounded-pill" style="border: 1.5px solid #000000;">Luar waktu</span>
                                <span class="small" style="color: var(--ln-text-muted);">Belum Clock In</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clock.in') }}" class="mb-4">
                            @csrf
                            <button type="submit" class="btn btn-ln-neon btn-lg w-100 py-3 rounded-pill shadow-sm" style="border: 1.5px solid #000000 !important;">
                                Clock in
                            </button>
                        </form>

                        <div class="border rounded-3 p-3 text-start" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold" style="color: var(--ln-text-muted);">Lapor tidak hadir:</span>
                            </div>
                            <form method="POST" action="{{ route('report.absent') }}">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="remarks" id="remarks" class="form-control form-control-sm" placeholder="Cth: MC, cuti tahunan, urusan peribadi" required style="border: 1.5px solid #000000 !important;">
                                </div>
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-2" style="border: 1.5px solid #000000 !important;">
                                    Hantar keterangan tidak hadir
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
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Sesi Kehadiran Hari Ini</h5>
                <span class="badge badge-ln-neon rounded-pill px-3">{{ count($todayAttendance) }} sesi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                        <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                            <tr>
                                <th style="width: 10%;">#</th>
                                <th style="width: 22%;">Masuk</th>
                                <th style="width: 22%;">Keluar</th>
                                <th style="width: 20%;">Tempoh</th>
                                <th style="width: 26%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAttendance as $idx => $att)
                                @php
                                    $diffFormatted = '-';
                                    if ($att->clock_in && $att->clock_out) {
                                        $in = \Carbon\Carbon::parse($att->date . ' ' . $att->clock_in);
                                        $out = \Carbon\Carbon::parse($att->date . ' ' . $att->clock_out);
                                        if ($out->lessThan($in)) {
                                            $out->addDay();
                                        }
                                        $mins = abs($in->diffInMinutes($out));
                                        $diffFormatted = sprintf('%02d:%02d', floor($mins / 60), $mins % 60);
                                    }
                                @endphp
                                <tr>
                                    <td class="small fw-bold" style="color: var(--ln-text-muted);">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="fw-bold font-monospace" style="color: var(--ln-dark);">
                                            {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('h:i A') : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($att->clock_out)
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
                                    <td colspan="5" class="py-5" style="color: var(--ln-text-muted);">
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
</script>
@endsection