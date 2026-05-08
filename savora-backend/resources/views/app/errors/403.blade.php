<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak | Savora</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @include('components.app-theme')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: var(--color-bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.14;
            pointer-events: none;
        }
        .blob-1 {
            width: 450px; height: 450px;
            background: var(--color-primary-dark);
            top: -100px; right: -80px;
            animation: floatA 9s ease-in-out infinite;
        }
        .blob-2 {
            width: 300px; height: 300px;
            background: var(--color-primary-teal);
            bottom: -60px; left: -60px;
            animation: floatA 7s ease-in-out infinite reverse;
        }
        @keyframes floatA {
            0%, 100% { transform: scale(1) translate(0,0); }
            50% { transform: scale(1.08) translate(-10px, 15px); }
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: var(--color-card-bg);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(38,70,83,0.15);
            box-shadow: 0 24px 60px rgba(38,70,83,0.12);
            padding: 56px 48px 48px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        .error-number {
            font-size: 96px;
            font-weight: 900;
            line-height: 1;
            background: var(--gradient-category);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -4px;
        }

        /* Lock icon animated */
        .error-icon-wrap {
            width: 80px; height: 80px;
            background: var(--gradient-category);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(38,70,83,0.30);
        }
        .error-icon-wrap i {
            font-size: 36px;
            color: #fff;
            animation: lockBounce 2s ease-in-out 0.6s infinite;
        }
        @keyframes lockBounce {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            30% { transform: translateY(-4px) rotate(-5deg); }
            60% { transform: translateY(-2px) rotate(5deg); }
        }

        .error-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: 12px;
        }
        .error-subtitle {
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: var(--radius-lg);
            border: 1.5px solid rgba(38,70,83,0.25);
            background: transparent;
            color: var(--color-primary-dark);
            font-size: var(--text-sm);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-back:hover { background: rgba(38,70,83,0.06); border-color: var(--color-primary-dark); }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            background: var(--gradient-category);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 15px rgba(38,70,83,0.30);
            color: #fff;
            font-size: var(--text-sm);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-login:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

        .error-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
            font-size: var(--text-xs);
            color: var(--color-text-secondary);
        }
        .error-breadcrumb span { color: var(--color-primary-dark); font-weight: 600; }

        .dots-pattern {
            position: absolute;
            top: 20px; right: 20px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 5px;
            opacity: 0.12;
        }
        .dot { width: 6px; height: 6px; background: var(--color-primary-dark); border-radius: 50%; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="error-card">
        <div class="dots-pattern">
            @for($i = 0; $i < 16; $i++)<div class="dot"></div>@endfor
        </div>

        <div class="error-breadcrumb">
            <i class="bi bi-house-fill"></i>
            <i class="bi bi-chevron-right" style="font-size:10px"></i>
            <span>403</span>
        </div>

        <div class="error-icon-wrap">
            <i class="bi bi-shield-lock-fill"></i>
        </div>

        <div class="error-number">403</div>

        <h1 class="error-title">Akses Ditolak</h1>
        <p class="error-subtitle">
            Kamu tidak memiliki izin untuk mengakses halaman ini.<br>
            Silakan login terlebih dahulu atau hubungi admin jika kamu yakin ini adalah kesalahan.
        </p>

        <div class="error-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            @guest
                <a href="{{ route('app.login') ?? '/login' }}" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Login Sekarang
                </a>
            @else
                <a href="{{ route('app.home') ?? '/' }}" class="btn-login">
                    <i class="bi bi-house-fill"></i>
                    Ke Beranda
                </a>
            @endguest
        </div>
    </div>
</body>
</html>