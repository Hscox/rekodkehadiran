@extends('layouts.app')

@section('content')
<div class="card rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
    <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3" style="border-bottom: 1.5px solid #000000 !important;">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Senarai Kakitangan Berdaftar</h5>
            <span class="badge badge-ln-neon rounded-pill px-3">{{ count($staffs) }} orang</span>
        </div>
        <a href="{{ route('admin.createStaff') }}" class="btn btn-sm btn-ln-neon px-3 rounded-pill fw-bold" style="border: 1.5px solid #000000 !important;">
            + Daftar Staf Baru
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" style="border-color: #000000 !important;">
                <thead class="table-light small border-bottom" style="border-bottom: 1.5px solid #000000 !important;">
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 18%;">Staff ID</th>
                        <th style="width: 38%;" class="text-start">Nama kakitangan</th>
                        <th style="width: 18%;">Tarikh daftar</th>
                        <th style="width: 20%;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffs as $idx => $stf)
                        <tr>
                            <td class="small fw-bold" style="color: var(--ln-text-muted);">{{ $idx + 1 }}</td>
                            <td><span class="badge badge-ln-neon fw-semibold">{{ $stf->staff_id }}</span></td>
                            <td class="text-start fw-semibold text-uppercase" style="color: var(--ln-dark);">{{ $stf->name }}</td>
                            <td class="small font-monospace" style="color: var(--ln-text-muted);">{{ $stf->created_at ? $stf->created_at->format('d/m/Y') : '-' }}</td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5" style="color: var(--ln-text-muted);">
                                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                    <span class="small mb-3">Tiada staf didaftarkan lagi.</span>
                                    <a href="{{ route('admin.createStaff') }}" class="btn btn-ln-neon btn-sm rounded-pill px-4" style="border: 1.5px solid #000000 !important;">
                                        Daftar staf pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

