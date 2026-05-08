<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | Savora</title>
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

        /* Decorative background blobs */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            pointer-events: none;
            animation: floatBlob 8s ease-in-out infinite;
        }
        .blob-1 {
            width: 400px; height: 400px;
            background: var(--color-primary-coral);
            top: -80px; right: -80px;
            animation-delay: 0s;
        }
        .blob-2 {
            width: 300px; height: 300px;
            background: var(--color-primary-teal);
            bottom: -60px; left: -60px;
            animation-delay: 3s;
        }
        .blob-3 {
            width: 200px; height: 200px;
            background: var(--color-primary-yellow);
            top: 40%; left: 10%;
            animation-delay: 5s;
        }
        @keyframes floatBlob {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        /* Main card */
        .error-card {
            position: relative;
            z-index: 1;
            background: var(--color-card-bg);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(231,111,81,0.15);
            box-shadow: 0 24px 60px rgba(231,111,81,0.12);
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

        /* Big error number */
        .error-number {
            font-size: 96px;
            font-weight: 900;
            line-height: 1;
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -4px;
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Icon */
        .error-icon-wrap {
            width: 80px; height: 80px;
            background: var(--gradient-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: var(--shadow-primary);
            animation: bounce 2s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .error-icon-wrap i {
            font-size: 36px;
            color: #fff;
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
            margin-bottom: 36px;
        }

        /* Action buttons */
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
            border: 1.5px solid rgba(231,111,81,0.30);
            background: transparent;
            color: var(--color-primary-coral);
            font-size: var(--text-sm);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-back:hover {
            background: rgba(231,111,81,0.08);
            border-color: var(--color-primary-coral);
        }

        /* Decorative dots pattern */
        .dots-pattern {
            position: absolute;
            top: 20px; right: 20px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 5px;
            opacity: 0.15;
        }
        .dot {
            width: 6px; height: 6px;
            background: var(--color-primary-coral);
            border-radius: 50%;
        }

        /* Breadcrumb trail */
        .error-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
            font-size: var(--text-xs);
            color: var(--color-text-secondary);
        }
        .error-breadcrumb span { color: var(--color-primary-coral); font-weight: 600; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="error-card">
        <div class="dots-pattern">
            @for($i = 0; $i < 16; $i++)<div class="dot"></div>@endfor
        </div>

        <div class="error-breadcrumb">
            <i class="bi bi-house-fill"></i>
            <i class="bi bi-chevron-right" style="font-size:10px"></i>
            <span>404</span>
        </div>

        <div class="error-icon-wrap">
            <i class="bi bi-map"></i>
        </div>

        <div class="error-number">404</div>

        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-subtitle">
            Sepertinya resep yang kamu cari sudah dipindahkan, dihapus, atau memang tidak pernah ada.<br>
            Yuk kembali dan jelajahi ribuan resep lainnya!
        </p>

        <div class="error-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            <a href="{{ route('app.home') ?? '/' }}" class="btn-primary-savora" style="padding: 12px 22px; font-size: var(--text-sm);">
                <i class="bi bi-house-fill"></i>
                Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>