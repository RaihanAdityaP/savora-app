<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Sesi Kedaluwarsa | Savora</title>
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
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
        }
        .blob-1 {
            width: 420px; height: 420px;
            background: var(--color-primary-yellow);
            top: -100px; left: -80px;
            animation: spin1 12s ease-in-out infinite;
        }
        .blob-2 {
            width: 300px; height: 300px;
            background: var(--color-primary-orange);
            bottom: -60px; right: -60px;
            animation: spin1 10s ease-in-out infinite reverse;
        }
        @keyframes spin1 {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(10deg); }
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: var(--color-card-bg);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(233,196,106,0.25);
            box-shadow: 0 24px 60px rgba(233,196,106,0.15);
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
            background: var(--gradient-orange);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -4px;
        }

        .error-icon-wrap {
            width: 80px; height: 80px;
            background: var(--gradient-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(244,162,97,0.35);
        }
        .error-icon-wrap i {
            font-size: 36px;
            color: #fff;
            animation: spinIcon 3s linear infinite;
        }
        @keyframes spinIcon {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
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
            margin-bottom: 28px;
        }

        .error-detail-box {
            background: linear-gradient(90deg, rgba(233,196,106,0.10), rgba(244,162,97,0.10));
            border: 1px solid rgba(233,196,106,0.35);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 32px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
        }
        .error-detail-box i { color: #D97706; font-size: 16px; flex-shrink: 0; margin-top: 2px; }
        .error-detail-box p { font-size: var(--text-xs); color: #92400E; font-weight: 500; line-height: 1.5; }

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
            border: 1.5px solid rgba(217,119,6,0.30);
            background: transparent;
            color: #D97706;
            font-size: var(--text-sm);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-back:hover { background: rgba(217,119,6,0.06); border-color: #D97706; }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            background: var(--gradient-orange);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 15px rgba(244,162,97,0.35);
            color: #fff;
            font-size: var(--text-sm);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-refresh:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

        .error-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
            font-size: var(--text-xs);
            color: var(--color-text-secondary);
        }
        .error-breadcrumb span { color: #D97706; font-weight: 600; }

        .dots-pattern {
            position: absolute;
            top: 20px; right: 20px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 5px;
            opacity: 0.15;
        }
        .dot { width: 6px; height: 6px; background: #D97706; border-radius: 50%; }
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
            <span>419</span>
        </div>

        <div class="error-icon-wrap">
            <i class="bi bi-clock-history"></i>
        </div>

        <div class="error-number">419</div>

        <h1 class="error-title">Sesi Kedaluwarsa</h1>
        <p class="error-subtitle">
            Sesi kamu sudah habis masa berlakunya. Ini terjadi jika tab dibiarkan terlalu lama<br>
            atau token keamanan tidak valid. Muat ulang halaman untuk melanjutkan.
        </p>

        <div class="error-detail-box">
            <i class="bi bi-shield-exclamation"></i>
            <p>Token CSRF tidak valid. Pastikan cookies diaktifkan di browser kamu, lalu coba muat ulang halaman.</p>
        </div>

        <div class="error-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            <button onclick="window.location.reload()" class="btn-refresh">
                <i class="bi bi-arrow-clockwise"></i>
                Muat Ulang
            </button>
        </div>
    </div>
</body>
</html>