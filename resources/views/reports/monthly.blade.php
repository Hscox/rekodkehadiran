@extends('layouts.app')

@section('content')
<div class="card rounded-4 shadow-sm p-4" style="border: 1.5px solid #000000 !important;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-bold fs-5" style="color: var(--ln-dark);">Rekod Kehadiran Kakitangan</h4>
            <span class="small" style="color: var(--ln-text-muted);">Paparan ringkasan kehadiran mengikut bulan dan staf</span>
        </div>
        
        @if($selectedUser)
            <form method="GET" action="{{ route('report') }}" class="d-flex gap-2 flex-wrap align-items-center p-2 rounded-3 border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                @if(Auth::user()->role === 'admin' && count($allStaffs) > 0)
                    <select name="user_id" class="form-select form-select-sm shadow-none" style="background-color: #ffffff; color: var(--ln-dark); border: 1.5px solid #000000 !important;">
                        @foreach($allStaffs as $stf)
                            <option value="{{ $stf->id }}" {{ $selectedUser->id == $stf->id ? 'selected' : '' }}>
                                {{ strtoupper($stf->staff_id . ' - ' . $stf->name) }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="month" class="form-select form-select-sm shadow-none" style="width: auto; background-color: #ffffff; color: var(--ln-dark); border: 1.5px solid #000000 !important;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            Bulan {{ $m }}
                        </option>
                    @endfor
                </select>

                <select name="year" class="form-select form-select-sm shadow-none" style="width: auto; background-color: #ffffff; color: var(--ln-dark); border: 1.5px solid #000000 !important;">
                    @php $curYear = (int) date('Y'); @endphp
                    @for($y = $curYear + 1; $y >= $curYear - 3; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <button type="submit" class="btn btn-sm btn-ln-neon px-3 rounded-2" style="border: 1.5px solid #000000 !important;">Cari</button>
                <a href="{{ route('report.exportPdf', ['user_id' => $selectedUser->id, 'month' => $month, 'year' => $year]) }}" target="_blank" class="btn btn-sm btn-outline-dark px-3 rounded-2 fw-bold" style="border: 1.5px solid #000000 !important;">
                    Cetak PDF
                </a>
            </form>
        @endif
    </div>

    @if($selectedUser)
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-8">
                <div class="p-3 rounded-3 border h-100 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: var(--ln-neon); color: #000000; border: 1.5px solid #000000;">
                            {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                        </div>
                        <div>
                            <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Nama kakitangan</span>
                            <span class="fs-6 fw-bold text-uppercase" style="color: var(--ln-dark);">{{ $selectedUser->name }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-4">
                        <div>
                            <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Staff ID</span>
                            <span class="badge badge-ln-neon fw-semibold">{{ $selectedUser->staff_id }}</span>
                        </div>
                        <div>
                            <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Tempoh</span>
                            <span class="fw-semibold small" style="color: var(--ln-dark);">Bulan {{ $month }}, {{ $year }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 h-100 d-flex justify-content-around align-items-center text-center" style="background-color: rgba(210, 255, 0, 0.15); border: 1.5px solid #000000 !important;">
                    <div>
                        <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Jumlah masa</span>
                        <span class="fs-4 fw-bold font-monospace" style="color: var(--ln-dark);">{{ $grandTotalFormatted }}</span>
                    </div>
                    <div class="border-start ps-4" style="border-color: #000000 !important; border-left-width: 1.5px !important;">
                        <span class="small d-block" style="font-size: 0.72rem; color: var(--ln-text-muted);">Jumlah skor</span>
                        <span class="fs-4 fw-bold font-monospace" style="color: var(--ln-dark);">{{ number_format($totalSkor, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 border" style="border: 1.5px solid #000000 !important;">
            <table class="table table-hover align-middle text-center mb-0" style="border-color: #000000 !important;">
                <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                    <tr>
                        <th style="width: 6%;">HB</th>
                        <th style="width: 32%;">Keterangan</th>
                        <th style="width: 12%;">Masuk</th>
                        <th style="width: 12%;">Keluar</th>
                        <th style="width: 12%;">Tempoh</th>
                        <th style="width: 13%;">Jumlah</th>
                        <th style="width: 13%;">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $row)
                        @php $sessionCount = count($row['sessions']); @endphp
                        @foreach($row['sessions'] as $index => $session)
                            <tr>
                                @if($index === 0)
                                    <td rowspan="{{ $sessionCount }}" class="fw-bold small" style="color: var(--ln-text-muted);">{{ $row['hb'] }}</td>
                                    <td rowspan="{{ $sessionCount }}" class="text-start">
                                        @if($row['keterangan'] === 'Tidak hadir' || str_contains($row['keterangan'], 'MC') || str_contains($row['keterangan'], 'Cuti'))
                                             <span class="text-danger fw-semibold">{{ $row['keterangan'] }}</span>
                                        @else
                                            <span style="color: var(--ln-dark);">{{ $row['keterangan'] }}</span>
                                        @endif
                                    </td>
                                @endif

                                <td>
                                    <span class="font-monospace" style="color: var(--ln-dark);">{{ $session['masuk'] }}</span>
                                </td>
                                <td><span class="font-monospace" style="color: var(--ln-dark);">{{ $session['keluar'] }}</span></td>
                                <td class="font-monospace" style="color: var(--ln-text-muted);">{{ $session['tempoh'] }}</td>

                                @if($index === 0)
                                    <td rowspan="{{ $sessionCount }}" class="fw-bold font-monospace" style="color: var(--ln-dark);">{{ $row['jumlah_masa'] }}</td>
                                    <td rowspan="{{ $sessionCount }}" class="fw-bold font-monospace" style="color: var(--ln-dark);">{{ $row['skor'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="py-5" style="color: var(--ln-text-muted);">
                                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                    <span class="small">Tiada rekod kehadiran untuk staf ini bagi bulan yang dipilih.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold border-top" style="border-top: 1.5px solid #000000 !important;">
                    <tr>
                        <td colspan="5" class="text-end small" style="color: var(--ln-text-muted);">Jumlah keseluruhan:</td>
                        <td class="font-monospace" style="color: var(--ln-dark);">{{ $grandTotalFormatted }}</td>
                        <td class="font-monospace fs-6" style="color: var(--ln-dark);">{{ number_format($totalSkor, 1) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="alert alert-info text-center py-5 my-3 rounded-4" style="background-color: #f8fafc; border: 1.5px solid #000000 !important; color: var(--ln-dark);">
            <h5 class="fw-bold mb-2">Tiada kakitangan berdaftar</h5>
            <p class="mb-3" style="color: var(--ln-text-muted);">Belum ada kakitangan yang didaftarkan dalam sistem untuk dilihat rekod kehadirannya.</p>
            @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.createStaff') }}" class="btn btn-ln-neon rounded-pill px-4" style="border: 1.5px solid #000000 !important;">Daftar staf sekarang</a>
            @endif
        </div>
    @endif
</div>
@endsection