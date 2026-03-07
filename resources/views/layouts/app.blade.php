<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>iPayOS Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ipayos-primary: #0d9488;
            --ipayos-primary-dark: #0f766e;
            --ipayos-dark: #1e293b;
            --ipayos-nav-bg: #ffffff;
            --ipayos-body-bg: #f8fafc;
        }
        .navbar-ipayos {
            background-color: var(--ipayos-nav-bg) !important;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .navbar-ipayos .navbar-brand {
            color: var(--ipayos-dark) !important;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-ipayos .navbar-brand img {
            height: 32px;
            width: auto;
        }
        .navbar-ipayos .nav-link {
            color: var(--ipayos-dark) !important;
            font-weight: 500;
        }
        .navbar-ipayos .nav-link:hover {
            color: var(--ipayos-primary) !important;
        }
        .navbar-ipayos .navbar-toggler {
            border-color: #e2e8f0;
        }
        .navbar-ipayos .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%231e293b' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .btn-ipayos, .btn-primary {
            background-color: var(--ipayos-primary);
            border-color: var(--ipayos-primary);
        }
        .btn-ipayos:hover, .btn-primary:hover {
            background-color: var(--ipayos-primary-dark);
            border-color: var(--ipayos-primary-dark);
        }
        .btn-outline-primary {
            color: var(--ipayos-primary);
            border-color: var(--ipayos-primary);
        }
        .btn-outline-primary:hover, .btn-outline-primary.active {
            background-color: var(--ipayos-primary);
            border-color: var(--ipayos-primary);
            color: #fff;
        }
        body {
            background-color: var(--ipayos-body-bg);
        }
        .card {
            border-color: #e2e8f0;
        }
        .card-header {
            background-color: #fff;
            border-bottom-color: #e2e8f0;
            color: var(--ipayos-dark);
            font-weight: 600;
        }
        .text-muted {
            color: #64748b !important;
        }
        .navbar-ipayos .btn-outline-light {
            color: var(--ipayos-primary);
            border-color: var(--ipayos-primary);
        }
        .navbar-ipayos .btn-outline-light:hover {
            background-color: var(--ipayos-primary);
            border-color: var(--ipayos-primary);
            color: #fff;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-ipayos mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('ipayos-black-logo.png') }}" alt="iPayOS" class="d-inline-block align-text-top">
        </a>
        @auth
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('transactions.index') }}">Transactions</a>
                    </li>
                </ul>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main class="container mb-4">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

