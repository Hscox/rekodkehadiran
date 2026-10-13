<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Sistem Kehadiran Kakitangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        :root {
            --ln-bg: #f8fafc;
            --ln-card: #ffffff;
            --ln-card-inner: #f1f5f9;
            --ln-border: #000000;
            --ln-neon: #D2FF00;
            --ln-neon-glow: rgba(210, 255, 0, 0.45);
            --ln-dark: #0f172a;
            --ln-text: #0f172a;
            --ln-text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            background-color: var(--ln-bg) !important;
            color: var(--ln-text) !important;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        .card {
            background-color: var(--ln-card) !important;
            border: 1.5px solid #000000 !important;
            color: var(--ln-text) !important;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05) !important;
        }

        .form-control {
            background-color: #ffffff !important;
            border: 1.5px solid #000000 !important;
            color: var(--ln-dark) !important;
        }

        .form-control:focus {
            border-color: #000000 !important;
            box-shadow: 0 0 0 0.25rem rgba(210, 255, 0, 0.35) !important;
            color: var(--ln-dark) !important;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
        }

        .btn-ln-neon {
            background-color: var(--ln-neon) !important;
            color: #000000 !important;
            border: 1.5px solid #000000 !important;
            font-weight: 800 !important;
            letter-spacing: 0.2px;
            transition: all 0.2s ease;
        }

        .btn-ln-neon:hover {
            background-color: #e2ff40 !important;
            color: #000000 !important;
            border-color: #000000 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--ln-neon-glow) !important;
        }

        .alert-danger {
            background-color: #fef2f2 !important;
            border: 1.5px solid #000000 !important;
            color: #991b1b !important;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="card border-0 rounded-4 shadow-sm" style="width: 100%; max-width: 420px;">
    <div class="card-body p-4 p-sm-5">
        <div class="text-center mb-4">
            <h5 class="fw-bold mb-1 fs-4" style="color: var(--ln-dark); letter-spacing: -0.3px;">Sistem Kehadiran Kakitangan</h5>
            <span class="small" style="color: var(--ln-text-muted);">Sila masukkan maklumat untuk log masuk</span>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 rounded-3 small mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="staff_id" class="form-label small fw-semibold text-muted mb-1">Staff ID</label>
                <input type="text" class="form-control" id="staff_id" name="staff_id" value="{{ old('staff_id') }}" required autofocus placeholder="Contoh: HR001 atau STF001">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold text-muted mb-1">Kata laluan</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan kata laluan">
            </div>

            <button type="submit" class="btn btn-ln-neon w-100 py-3 rounded-pill shadow-sm">
                Log masuk
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "4000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    @if(session('success'))
        toastr.success("{{ session('success') }}", "Berjaya!");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}", "Ralat!");
    @endif

    @if(session('warning'))
        toastr.warning("{{ session('warning') }}", "Perhatian!");
    @endif

    @if(session('info'))
        toastr.info("{{ session('info') }}", "Info");
    @endif
</script>
</body>
</html>