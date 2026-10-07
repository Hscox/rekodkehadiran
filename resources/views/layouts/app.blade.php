<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kehadiran Kakitangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

        .custom-navbar {
            background-color: #ffffff !important;
            border-bottom: 1.5px solid #000000 !important;
        }

        .custom-navbar .navbar-brand {
            color: var(--ln-dark) !important;
            font-weight: 800 !important;
            letter-spacing: -0.3px;
        }

        .custom-navbar .nav-link {
            color: var(--ln-text-muted) !important;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .custom-navbar .nav-link:hover, .custom-navbar .nav-link.active {
            color: var(--ln-dark) !important;
            font-weight: 700;
        }

        .card {
            background-color: var(--ln-card) !important;
            border: 1.5px solid #000000 !important;
            color: var(--ln-text) !important;
            box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04) !important;
        }

        .card-header {
            border-bottom: 1.5px solid #000000 !important;
        }

        .btn-ln-neon {
            background-color: var(--ln-neon) !important;
            color: #000000 !important;
            border: 1.5px solid #000000 !important;
            font-weight: 800 !important;
            transition: all 0.2s ease;
        }

        .btn-ln-neon:hover {
            background-color: #e2ff40 !important;
            color: #000000 !important;
            border-color: #000000 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--ln-neon-glow) !important;
        }

        .btn-logout {
            color: #ef4444 !important;
            border: 1.5px solid #000000 !important;
            background: rgba(239, 68, 68, 0.05);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background-color: #ef4444 !important;
            color: #ffffff !important;
            border-color: #000000 !important;
        }

        .form-control, .form-select {
            background-color: #ffffff !important;
            border: 1.5px solid #000000 !important;
            color: var(--ln-dark) !important;
        }

        .form-control:focus, .form-select:focus {
            border-color: #000000 !important;
            box-shadow: 0 0 0 0.25rem rgba(210, 255, 0, 0.35) !important;
            color: var(--ln-dark) !important;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
        }

        .table {
            color: var(--ln-text) !important;
            border-color: #000000 !important;
        }

        .table > :not(caption) > * > * {
            padding: 0.95rem 0.85rem;
            background-color: transparent !important;
            color: inherit !important;
            border-bottom: 1px solid #000000 !important;
        }

        .table-light {
            background-color: #f8fafc !important;
            color: var(--ln-text-muted) !important;
            border-bottom: 1.5px solid #000000 !important;
        }

        .badge-ln-neon {
            background-color: var(--ln-neon) !important;
            color: #000000 !important;
            border: 1.5px solid #000000 !important;
            font-weight: 700;
        }

        .border, .border-top, .border-bottom, .border-start, .border-end {
            border-color: #000000 !important;
        }

        .alert-success {
            background-color: rgba(210, 255, 0, 0.15) !important;
            border: 1.5px solid #000000 !important;
            color: #1e293b !important;
        }

        .alert-danger {
            background-color: #fef2f2 !important;
            border: 1.5px solid #000000 !important;
            color: #991b1b !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg custom-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ Auth::user()->role === 'admin' ? route('report') : route('dashboard') }}">
            Sistem Kehadiran Kakitangan
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                @if(Auth::user()->role === 'staff')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('report') ? 'active' : '' }}" href="{{ route('report') }}">Laporan Kehadiran</a>
                </li>
                @if(Auth::user()->role === 'admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.createStaff') ? 'active' : '' }}" href="{{ route('admin.createStaff') }}">Daftar Staff ID</a>
                    </li>
                @endif
            </ul>
            
            <div class="d-flex align-items-center">
                <span class="me-3 small text-dark"><strong>{{ Auth::user()->name }}</strong> <span class="badge badge-ln-neon ms-1">{{ ucfirst(Auth::user()->role) }}</span></span>
                <form action="{{ route('logout') }}" method="POST" class="m-0" onsubmit="return confirm('Adakah anda pasti ingin log keluar?');">
                    @csrf
                    <button type="submit" class="btn btn-logout btn-sm rounded-pill px-3">Log keluar</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>