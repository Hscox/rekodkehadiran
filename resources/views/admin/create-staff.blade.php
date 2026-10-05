@extends('layouts.app')

@section('content')
<div class="row g-4">
    <div class="col-12 col-md-5">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-transparent py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Daftar Akaun Staf Baru</h5>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 rounded-3 small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ url('/admin/create-staff') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="staff_id" class="form-label small fw-semibold text-muted mb-1">Staff ID</label>
                        <input type="text" class="form-control" id="staff_id" name="staff_id" value="{{ old('staff_id') }}" required placeholder="Cth: STF001">
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-muted mb-1">Nama kakitangan</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="Nama penuh staf">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-muted mb-1">Kata laluan</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 6 aksara">
                    </div>

                    <button type="submit" class="btn btn-ln-neon w-100 py-3 rounded-pill shadow-sm">
                        Daftar staf
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-7">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Senarai Kakitangan Berdaftar</h5>
                <span class="badge badge-ln-neon rounded-pill px-3">{{ count($staffs) }} orang</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light small border-bottom">
                            <tr>
                                <th style="width: 8%;">#</th>
                                <th style="width: 22%;">Staff ID</th>
                                <th style="width: 35%;" class="text-start">Nama kakitangan</th>
                                <th style="width: 20%;">Tarikh daftar</th>
                                <th style="width: 15%;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $idx => $stf)
                                <tr>
                                    <td class="small fw-bold" style="color: var(--ln-text-muted);">{{ $idx + 1 }}</td>
                                    <td><span class="badge badge-ln-neon fw-semibold">{{ $stf->staff_id }}</span></td>
                                    <td class="text-start fw-semibold" style="color: var(--ln-dark);">{{ $stf->name }}</td>
                                    <td class="small font-monospace" style="color: var(--ln-text-muted);">{{ $stf->created_at ? $stf->created_at->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.deleteStaff', $stf->id) }}" onsubmit="return confirm('Adakah anda pasti ingin memadam staf {{ $stf->name }} ({{ $stf->staff_id }})?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                                Padam
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-5" style="color: var(--ln-text-muted);">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                            <span class="small">Tiada staf didaftarkan lagi. Sila daftar staf menggunakan borang di sebelah.</span>
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
@endsection
