@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card rounded-4 shadow-sm" style="border: 1.5px solid #000000 !important;">
            <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="border-bottom: 1.5px solid #000000 !important;">
                <h5 class="card-title mb-0 fw-bold fs-6" style="color: var(--ln-dark);">Kemaskini Maklumat Staf</h5>
                <span class="badge badge-ln-neon fw-semibold">{{ $staff->staff_id }}</span>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 rounded-3 small mb-3" style="border: 1.5px solid #000000 !important;">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.updateStaff', $staff->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Staff ID</label>
                        <input type="text" class="form-control bg-light" value="{{ $staff->staff_id }}" disabled readonly style="border: 1.5px solid #000000 !important;">
                        <span class="small text-muted" style="font-size: 0.72rem;">Staff ID tidak boleh diubah</span>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-muted mb-1">Nama kakitangan</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $staff->name) }}" required placeholder="Nama penuh staf" style="border: 1.5px solid #000000 !important;">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-muted mb-1">Kata laluan baru</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 aksara" style="border: 1.5px solid #000000 !important;">
                        <span class="small text-muted" style="font-size: 0.72rem;"></span>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-ln-neon flex-fill py-2 rounded-pill shadow-sm" style="border: 1.5px solid #000000 !important;">
                            Simpan perubahan
                        </button>
                        <a href="{{ route('admin.staffList') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill" style="border: 1.5px solid #000000 !important;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
