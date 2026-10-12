@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-11">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--ln-dark);">Pengesahan Keluar Kakitangan</h4>
                <p class="small mb-0" style="color: var(--ln-text-muted);">
                    Pengurusan rekod staf yang tidak clock out, permohonan masa pulang dan sejarah pengesahan.
                </p>
            </div>
            <div>
                <a href="{{ route('report') }}" class="btn btn-outline-dark btn-sm rounded-3 px-3 fw-bold" style="border: 1.5px solid #000000 !important;">
                    &larr; Kembali ke Laporan
                </a>
            </div>
        </div>

        <div class="card rounded-4 shadow-sm mb-5" style="border: 1.5px solid #000000 !important;">
            <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1.5px solid #000000 !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark border border-dark rounded-pill px-2 py-1" style="font-size: 0.75rem;">Menunggu Tindakan</span>
                    <h6 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Permohonan Pengesahan Keluar</h6>
                </div>
                <span class="badge badge-ln-neon px-3 py-2 rounded-pill">{{ $appeals->total() }} rekod</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                        <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 13%;">Tarikh</th>
                                <th style="width: 22%;" class="text-start">Kakitangan</th>
                                <th style="width: 12%;">Waktu Masuk</th>
                                <th style="width: 15%;">Masa Pulang Dimohon</th>
                                <th style="width: 18%;" class="text-start">Sebab / Alasan</th>
                                <th style="width: 15%;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appeals as $idx => $appeal)
                                <tr>
                                    <td class="small fw-bold" style="color: var(--ln-text-muted);">
                                        {{ $appeals->firstItem() + $idx }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold small font-monospace" style="color: var(--ln-dark);">
                                            {{ \Carbon\Carbon::parse($appeal->date)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold" style="color: var(--ln-dark);">{{ $appeal->user->name }}</div>
                                        <span class="badge badge-ln-neon" style="font-size: 0.7rem;">{{ $appeal->user->staff_id }}</span>
                                    </td>
                                    <td>
                                        <span class="font-monospace fw-bold" style="color: var(--ln-dark);">
                                            {{ $appeal->clock_in ? \Carbon\Carbon::parse($appeal->clock_in)->format('h:i A') : '-' }}
                                        </span>
                                        <div>
                                            <span class="badge {{ ($appeal->session_type ?? 'siang') === 'malam' ? 'bg-dark text-white' : 'bg-warning-subtle text-dark' }} rounded-pill" style="border: 1px solid #000000; font-size: 0.65rem;">
                                                Sesi {{ ucfirst($appeal->session_type ?? 'siang') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($appeal->proposed_clock_out)
                                            <span class="badge bg-warning-subtle text-dark border border-warning font-monospace px-2 py-1 fs-6">
                                                {{ \Carbon\Carbon::parse($appeal->proposed_clock_out)->format('h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-danger small fw-semibold">Menunggu maklum balas staf</span>
                                        @endif
                                    </td>
                                    <td class="text-start small">
                                        @if($appeal->appeal_reason)
                                            <span style="color: var(--ln-dark);" class="fw-semibold">{{ $appeal->appeal_reason }}</span>
                                        @else
                                            <span style="color: var(--ln-text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <form method="POST" action="{{ route('admin.approveAppeal', $appeal->id) }}" onsubmit="return confirm('Sahkan kelulusan waktu pulang ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm fw-bold px-2 rounded-2" style="border: 1.5px solid #000000 !important; font-size: 0.78rem;">
                                                    Lulus
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.rejectAppeal', $appeal->id) }}" onsubmit="return confirm('Tolak permohonan ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm fw-bold px-2 rounded-2" style="border: 1.5px solid #000000 !important; font-size: 0.78rem;">
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-5" style="color: var(--ln-text-muted);">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                            <span class="fw-semibold">Tiada permohonan tertangguh pada masa ini.</span>
                                            <span class="small text-muted mt-1">Semua rekod staf telah selesai diproses.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($appeals->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-top: 1.5px solid #000000 !important;">
                        <span class="small" style="color: var(--ln-text-muted);">
                            Menunjukkan {{ $appeals->firstItem() }} ke {{ $appeals->lastItem() }} daripada {{ $appeals->total() }} rekod
                        </span>
                        <div>
                            {{ $appeals->appends(['history_page' => $pastAppeals->currentPage()])->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card rounded-4 shadow-sm mb-4" style="border: 1.5px solid #000000 !important;">
            <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1.5px solid #000000 !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-2 py-1" style="border: 1px solid #000000; font-size: 0.75rem;">Sejarah</span>
                    <h6 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Rekod Lepas Pengesahan Keluar</h6>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill" style="border: 1.5px solid #000000 !important;">
                    {{ $pastAppeals->total() }} rekod selesai
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                        <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 12%;">Tarikh</th>
                                <th style="width: 20%;" class="text-start">Kakitangan</th>
                                <th style="width: 11%;">Masuk</th>
                                <th style="width: 11%;">Pulang</th>
                                <th style="width: 18%;" class="text-start">Sebab / Alasan</th>
                                <th style="width: 11%;">Keputusan</th>
                                <th style="width: 12%;">Diproses Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pastAppeals as $pIdx => $past)
                                <tr>
                                    <td class="small fw-bold" style="color: var(--ln-text-muted);">
                                        {{ $pastAppeals->firstItem() + $pIdx }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold small font-monospace" style="color: var(--ln-dark);">
                                            {{ \Carbon\Carbon::parse($past->date)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold" style="color: var(--ln-dark);">{{ $past->user->name }}</div>
                                        <span class="badge badge-ln-neon" style="font-size: 0.7rem;">{{ $past->user->staff_id }}</span>
                                    </td>
                                    <td>
                                        <span class="font-monospace small" style="color: var(--ln-dark);">
                                            {{ $past->clock_in ? \Carbon\Carbon::parse($past->clock_in)->format('h:i A') : '-' }}
                                        </span>
                                        <div>
                                            <span class="badge {{ ($past->session_type ?? 'siang') === 'malam' ? 'bg-dark text-white' : 'bg-warning-subtle text-dark' }} rounded-pill" style="border: 1px solid #000000; font-size: 0.65rem;">
                                                Sesi {{ ucfirst($past->session_type ?? 'siang') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($past->status === 'approved' && $past->clock_out)
                                            <span class="font-monospace fw-bold small text-success">
                                                {{ \Carbon\Carbon::parse($past->clock_out)->format('h:i A') }}
                                            </span>
                                        @elseif($past->proposed_clock_out)
                                            <span class="font-monospace small text-muted text-decoration-line-through">
                                                {{ \Carbon\Carbon::parse($past->proposed_clock_out)->format('h:i A') }}
                                            </span>
                                        @else
                                            <span style="color: var(--ln-text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td class="text-start small">
                                        <span style="color: var(--ln-dark);">{{ $past->appeal_reason ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if($past->status === 'approved')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                                Disahkan
                                            </span>
                                        @elseif($past->status === 'rejected')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill">{{ ucfirst($past->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small fw-semibold" style="color: var(--ln-dark);">
                                            {{ $past->approver ? $past->approver->name : 'Admin' }}
                                        </div>
                                        <span class="small font-monospace" style="font-size: 0.68rem; color: var(--ln-text-muted);">
                                            {{ \Carbon\Carbon::parse($past->updated_at)->format('d/m/y h:i A') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5" style="color: var(--ln-text-muted);">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                            <span class="fw-semibold">Belum ada rekod lepas pengesahan keluar.</span>
                                            <span class="small text-muted mt-1">Rekod yang telah diluluskan atau ditolak akan muncul di sini.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($pastAppeals->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-top: 1.5px solid #000000 !important;">
                        <span class="small" style="color: var(--ln-text-muted);">
                            Menunjukkan {{ $pastAppeals->firstItem() }} ke {{ $pastAppeals->lastItem() }} daripada {{ $pastAppeals->total() }} rekod
                        </span>
                        <div>
                            {{ $pastAppeals->appends(['pending_page' => $appeals->currentPage()])->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
