@extends('layouts.app')

@section('content')
<div class="card rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
    <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3" style="border-bottom: 1.5px solid #000000 !important;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Senarai Kakitangan Berdaftar</h5>
            <div class="btn-group btn-group-sm rounded-pill p-1 border" style="background-color: #f8fafc; border: 1.5px solid #000000 !important;">
                <a href="{{ route('admin.staffList', ['status' => 'active']) }}" class="btn btn-sm rounded-pill fw-semibold px-3 {{ ($status ?? 'active') === 'active' ? 'btn-ln-neon' : 'btn-light' }}" style="{{ ($status ?? 'active') === 'active' ? 'border: 1.5px solid #000000 !important;' : '' }}">
                    Aktif ({{ $activeCount ?? $staffs->total() }})
                </a>
                <a href="{{ route('admin.staffList', ['status' => 'trashed']) }}" class="btn btn-sm rounded-pill fw-semibold px-3 {{ ($status ?? 'active') === 'trashed' ? 'btn-danger text-white' : 'btn-light' }}" style="{{ ($status ?? 'active') === 'trashed' ? 'border: 1.5px solid #000000 !important;' : '' }}">
                    Dipadam ({{ $trashedCount ?? 0 }})
                </a>
            </div>
        </div>
        @if(($status ?? 'active') === 'active')
            <a href="{{ route('admin.createStaff') }}" class="btn btn-sm btn-ln-neon px-3 rounded-pill fw-bold" style="border: 1.5px solid #000000 !important;">
                + Daftar Staf Baru
            </a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 18%;">Staff ID</th>
                        <th style="width: 36%;" class="text-start">Nama kakitangan</th>
                        <th style="width: 18%;">{{ ($status ?? 'active') === 'trashed' ? 'Tarikh dipadam' : 'Tarikh daftar' }}</th>
                        <th style="width: 22%;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffs as $idx => $stf)
                        <tr>
                            <td class="small fw-bold" style="color: var(--ln-text-muted);">{{ ($staffs->currentPage() - 1) * $staffs->perPage() + $idx + 1 }}</td>
                            <td><span class="badge {{ ($status ?? 'active') === 'trashed' ? 'bg-secondary' : 'badge-ln-neon' }} fw-semibold">{{ $stf->staff_id }}</span></td>
                            <td class="text-start fw-semibold text-uppercase" style="color: var(--ln-dark);">
                                {{ $stf->name }}
                                @if(($status ?? 'active') === 'trashed')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill ms-1" style="font-size: 0.65rem;">Dipadam</span>
                                @endif
                            </td>
                            <td class="small font-monospace" style="color: var(--ln-text-muted);">
                                @if(($status ?? 'active') === 'trashed')
                                    {{ $stf->deleted_at ? $stf->deleted_at->format('d/m/Y h:i A') : '-' }}
                                @else
                                    {{ $stf->created_at ? $stf->created_at->format('d/m/Y') : '-' }}
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    @if(($status ?? 'active') === 'trashed')
                                        <form method="POST" action="{{ route('admin.restoreStaff', $stf->id) }}" onsubmit="return confirm('Pulihkan semula akaun staf {{ $stf->name }} ({{ $stf->staff_id }})?');" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem; border: 1.5px solid #000000 !important;">
                                                Pulihkan
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.forceDeleteStaff', $stf->id) }}" onsubmit="return confirm('AMARAN: Anda pasti ingin memadam staf {{ $stf->name }} ({{ $stf->staff_id }}) secara KEKAL? Tindakan ini tidak boleh diundur!');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem; border: 1.5px solid #000000 !important;">
                                                Padam Kekal
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.editStaff', $stf->id) }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem; border: 1.5px solid #000000 !important;">
                                            Kemaskini
                                        </a>
                                        <form method="POST" action="{{ route('admin.deleteStaff', $stf->id) }}" onsubmit="return confirm('Adakah anda pasti ingin memadam staf {{ $stf->name }} ({{ $stf->staff_id }})?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem; border: 1.5px solid #000000 !important;">
                                                Padam
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5" style="color: var(--ln-text-muted);">
                                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                    @if(($status ?? 'active') === 'trashed')
                                        <span class="small">Tiada rekod staf dipadam.</span>
                                    @else
                                        <span class="small mb-3">Tiada staf didaftarkan lagi.</span>
                                        <a href="{{ route('admin.createStaff') }}" class="btn btn-ln-neon btn-sm rounded-pill px-4" style="border: 1.5px solid #000000 !important;">
                                            Daftar staf pertama
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($staffs->hasPages())
        <div class="card-footer bg-transparent py-3 px-4 border-top d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-top: 1.5px solid #000000 !important;">
            <div class="small fw-semibold" style="color: var(--ln-text-muted);">
                Menunjukkan {{ $staffs->firstItem() ?? 0 }} hingga {{ $staffs->lastItem() ?? 0 }} daripada {{ $staffs->total() }} staf
            </div>
            <div>
                {{ $staffs->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

