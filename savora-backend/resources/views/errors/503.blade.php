@php($isEnglish = (session('user_language', 'en') === 'en'))
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? '503 - Under Maintenance' : '503 - Dalam Pemeliharaan' }} | Savora</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{--
        503 / maintenance page does NOT use @include('components.app-theme')
        because the app may be fully down. CSS vars are inlined instead.
    --}}
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --color-primary-coral:  #E76F51;
            --color-primary-teal:   #2A9D8F;
            --color-primary-dark:   #264653;
            --color-bg-light:       #F5F7FA;
            --color-card-bg:        #ffffff;
            --color-text-primary:   #264653;
            --color-text-secondary: #6B7280;
            --gradient-teal:  linear-gradient(90deg, #2A9D8F, #3DB9A9);
            --radius-xl: 20px;
            --radius-lg: 16px;
            --radius-md: 14px;
            --text-2xl: 26px;
            --text-lg:  18px;
            --text-sm:  14px;
            --text-xs:  13px;
        }

        body {
            min-height: 100vh;
            background: var(--color-bg-light);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-y: auto;
            position: relative;
            padding: 24px 0;
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.14;
            pointer-events: none;
        }
        .blob-1 {
            width: 500px; height: 500px;
            background: var(--color-primary-teal);
            top: -120px; right: -100px;
            animation: pulsate 8s ease-in-out infinite;
        }
        .blob-2 {
            width: 350px; height: 350px;
            background: var(--color-primary-dark);
            bottom: -80px; left: -80px;
            animation: pulsate 6s ease-in-out infinite reverse;
        }
        @keyframes pulsate {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.12); }
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: var(--color-card-bg);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(42,157,143,0.18);
            box-shadow: 0 24px 60px rgba(42,157,143,0.12);
            padding: clamp(28px, 5vh, 48px) 48px 40px;
            max-width: 540px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* Savora logo mark */
        .logo-mark {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 32px;
        }
        .logo-circle {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #2B6CB0, #FF6B35);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-circle i { font-size: 16px; color: #fff; }
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(90deg, #2B6CB0, #FF6B35);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-icon-wrap {
            width: 80px; height: 80px;
            background: var(--gradient-teal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(42,157,143,0.35);
        }
        .error-icon-wrap i {
            font-size: 36px;
            color: #fff;
            animation: wrench 1.5s ease-in-out 0.5s infinite;
        }
        @keyframes wrench {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            75% { transform: rotate(15deg); }
        }

        .error-number {
            font-size: 96px;
            font-weight: 900;
            line-height: 1;
            background: var(--gradient-teal);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -4px;
        }

        @media (max-height: 760px) {
            .error-card { padding-top: 28px; padding-bottom: 28px; }
            .logo-mark { margin-bottom: 22px; }
            .error-icon-wrap { width: 64px; height: 64px; margin-bottom: 14px; }
            .error-icon-wrap i { font-size: 30px; }
            .error-number { font-size: 76px; margin-bottom: 4px; }
            .error-subtitle { margin-bottom: 18px; }
            .maintenance-progress { margin-bottom: 18px; }
            .error-detail-box { margin-bottom: 22px; }
        }

        @media (max-width: 640px) {
            body { padding: 14px 0; }
            .error-card { width: calc(100% - 28px); padding-left: 22px; padding-right: 22px; }
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

        /* Progress bar looping */
        .maintenance-progress {
            background: rgba(42,157,143,0.12);
            border-radius: 9999px;
            height: 6px;
            margin-bottom: 28px;
            overflow: hidden;
        }
        .maintenance-progress-bar {
            height: 100%;
            background: var(--gradient-teal);
            border-radius: 9999px;
            animation: progressLoop 2.5s ease-in-out infinite;
        }
        @keyframes progressLoop {
            0%   { width: 0%;   margin-left: 0; }
            50%  { width: 60%;  margin-left: 20%; }
            100% { width: 0%;   margin-left: 100%; }
        }

        .error-detail-box {
            background: linear-gradient(90deg, rgba(42,157,143,0.08), rgba(38,70,83,0.06));
            border: 1px solid rgba(42,157,143,0.22);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 32px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
        }
        .error-detail-box i { color: var(--color-primary-teal); font-size: 16px; flex-shrink: 0; margin-top: 2px; }
        .error-detail-box p { font-size: var(--text-xs); color: rgba(42,157,143,0.90); font-weight: 500; line-height: 1.5; }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--gradient-teal);
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 15px rgba(42,157,143,0.35);
            color: #fff;
            font-size: var(--text-sm);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-home:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

        .dots-pattern {
            position: absolute;
            top: 20px; right: 20px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 5px;
            opacity: 0.12;
        }
        .dot { width: 6px; height: 6px; background: var(--color-primary-teal); border-radius: 50%; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="error-card">
        <div class="dots-pattern">
            @for($i = 0; $i < 16; $i++)<div class="dot"></div>@endfor
        </div>

        <div class="logo-mark">
            <div class="logo-circle"><i class="bi bi-fire"></i></div>
            <span class="logo-text">Savora</span>
        </div>

        <div class="error-icon-wrap">
            <i class="bi bi-tools"></i>
        </div>

        <div class="error-number">503</div>

        <h1 class="error-title">{{ $isEnglish ? 'Under Maintenance' : 'Sedang Dalam Pemeliharaan' }}</h1>
        <p class="error-subtitle">
            {{ $isEnglish ? 'Savora is being updated for a better cooking experience.' : 'Dapur Savora sedang direnovasi! Kami sedang melakukan pembaruan' }}<br>
            {{ $isEnglish ? 'Please come back in a little while.' : 'untuk pengalaman memasak yang lebih baik. Kembali lagi sebentar ya!' }}
        </p>

        <div class="maintenance-progress">
            <div class="maintenance-progress-bar"></div>
        </div>

        <div class="error-detail-box">
            <i class="bi bi-clock"></i>
            <p>{{ $isEnglish ? 'Maintenance usually takes less than 30 minutes. Please check back shortly.' : 'Pemeliharaan biasanya berlangsung kurang dari 30 menit. Coba kunjungi kembali halaman ini dalam beberapa saat.' }}</p>
        </div>

        <button onclick="window.location.reload()" class="btn-home">
            <i class="bi bi-arrow-clockwise"></i>
            {{ $isEnglish ? 'Try Again' : 'Coba Lagi' }}
        </button>
    </div>
</body>
</html>
