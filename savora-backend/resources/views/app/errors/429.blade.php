<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 — Terlalu Banyak Permintaan | Savora</title>
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
            background: #8B5CF6;
            top: -100px; right: -80px;
            animation: floatC 9s ease-in-out infinite;
        }
        .blob-2 {
            width: 280px; height: 280px;
            background: var(--color-primary-coral);
            bottom: -60px; left: -60px;
            animation: floatC 7s ease-in-out infinite reverse;
        }
        @keyframes floatC {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1) translate(10px, -10px); }
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: var(--color-card-bg);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(139,92,246,0.15);
            box-shadow: 0 24px 60px rgba(139,92,246,0.12);
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
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -4px;
        }

        .error-icon-wrap {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(139,92,246,0.35);
        }
        .error-icon-wrap i {
            font-size: 36px;
            color: #fff;
            animation: rapidPulse 0.4s ease-in-out 0.5s 5;
        }
        @keyframes rapidPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
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

        /* Countdown timer */
        .countdown-wrap {
            background: linear-gradient(90deg, rgba(139,92,246,0.08), rgba(236,72,153,0.08));
            border: 1px solid rgba(139,92,246,0.22);
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 32px;
        }
        .countdown-label {
            font-size: var(--text-xs);
            color: #8B5CF6;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .countdown-timer {
            font-size: 36px;
            font-weight: 900;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
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
            border: 1.5px solid rgba(139,92,246,0.28);
            background: transparent;
            color: #8B5CF6;
            font-size: var(--text-sm);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-back:hover { background: rgba(139,92,246,0.06); border-color: #8B5CF6; }

        .btn-wait {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 15px rgba(139,92,246,0.35);
            color: #fff;
            font-size: var(--text-sm);
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-wait:hover { opacity: .9; transform: translateY(-1px); }
        .btn-wait:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .error-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
            font-size: var(--text-xs);
            color: var(--color-text-secondary);
        }
        .error-breadcrumb span { color: #8B5CF6; font-weight: 600; }

        .dots-pattern {
            position: absolute;
            top: 20px; right: 20px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 5px;
            opacity: 0.12;
        }
        .dot { width: 6px; height: 6px; background: #8B5CF6; border-radius: 50%; }
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
            <span>429</span>
        </div>

        <div class="error-icon-wrap">
            <i class="bi bi-speedometer2"></i>
        </div>

        <div class="error-number">429</div>

        <h1 class="error-title">Terlalu Banyak Permintaan</h1>
        <p class="error-subtitle">
            Kamu terlalu cepat! Savora perlu sedikit jeda sebelum melayani<br>
            permintaan berikutnya. Tunggu sebentar lalu coba lagi.
        </p>

        <div class="countdown-wrap">
            <p class="countdown-label"><i class="bi bi-hourglass-split me-1"></i> Coba lagi dalam</p>
            <div class="countdown-timer" id="countdown">60</div>
        </div>

        <div class="error-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            <button id="retryBtn" onclick="window.location.reload()" class="btn-wait" disabled>
                <i class="bi bi-arrow-clockwise"></i>
                Coba Lagi
            </button>
        </div>
    </div>

    <script>
        let seconds = 60;
        const el = document.getElementById('countdown');
        const btn = document.getElementById('retryBtn');
        const timer = setInterval(() => {
            seconds--;
            el.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                btn.disabled = false;
                el.textContent = '✓';
            }
        }, 1000);
    </script>
</body>
</html>