<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2 { font-family: 'Poppins', sans-serif; }

        .gear-spin {
            animation: gearSpin 8s linear infinite;
            transform-origin: center;
        }
        .gear-spin-reverse {
            animation: gearSpin 6s linear infinite reverse;
            transform-origin: center;
        }
        @keyframes gearSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .pulse-dot {
            animation: pulseDot 2s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.3); }
        }
        .pulse-dot:nth-child(2) { animation-delay: 0.3s; }
        .pulse-dot:nth-child(3) { animation-delay: 0.6s; }

        .float-card {
            animation: floatCard 3s ease-in-out infinite;
        }
        @keyframes floatCard {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }

        .shimmer-row {
            background: linear-gradient(90deg, rgba(231,111,81,0.08) 25%, rgba(231,111,81,0.18) 50%, rgba(231,111,81,0.08) 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
            border-radius: 8px;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="relative rounded-3xl overflow-hidden mb-5 shadow-xl p-5 text-white" style="background: var(--gradient-accent);">
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-white/25 rounded-2xl border-2 border-white/40">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold">Pengaturan</h1>
                    <p class="text-white/80 text-xs">Atur pengalaman Savora kamu</p>
                </div>
            </div>
        </div>

        {{-- Coming Soon Card --}}
        <div class="card-savora overflow-hidden">

            {{-- Gear Illustration --}}
            <div class="flex justify-center items-center py-10" style="background: linear-gradient(135deg, rgba(231,111,81,0.06) 0%, rgba(244,162,97,0.06) 100%);">
                <div class="relative w-40 h-40 flex items-center justify-center">

                    {{-- Big gear --}}
                    <svg class="gear-spin absolute" width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="top: 10px; left: 10px; opacity: 0.85;">
                        <path d="M50 30a20 20 0 100 40 20 20 0 000-40z" stroke="#E76F51" stroke-width="3" fill="rgba(231,111,81,0.10)"/>
                        <path d="M43 10h14l2 8a30 30 0 018.66 5l8-2 7 12.12-6 5.66a30 30 0 010 10l6 5.66L75.66 67l-8-2A30 30 0 0157 70l-2 8H43l-2-8a30 30 0 01-8.66-5l-8 2L17.34 54.88l6-5.66a30 30 0 010-10l-6-5.66L24.34 21.12l8 2A30 30 0 0141 18l2-8z" stroke="#E76F51" stroke-width="3" stroke-linejoin="round" fill="rgba(231,111,81,0.07)"/>
                    </svg>

                    {{-- Small gear --}}
                    <svg class="gear-spin-reverse absolute" width="54" height="54" viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg" style="top: 4px; right: 2px; opacity: 0.70;">
                        <path d="M27 18a9 9 0 100 18 9 9 0 000-18z" stroke="#F4A261" stroke-width="2.5" fill="rgba(244,162,97,0.15)"/>
                        <path d="M23 4h8l1 4a16 16 0 014.66 2.7l4-1 4 6.93-3 2.87a16 16 0 010 5.4l3 2.87-4 6.93-4-1A16 16 0 0131 46l-1 4h-8l-1-4a16 16 0 01-4.66-2.7l-4 1-4-6.93 3-2.87a16 16 0 010-5.4l-3-2.87 4-6.93 4 1A16 16 0 0122 8l1-4z" stroke="#F4A261" stroke-width="2.5" stroke-linejoin="round" fill="rgba(244,162,97,0.07)"/>
                    </svg>

                </div>
            </div>

            {{-- Text Content --}}
            <div class="px-6 pb-8 text-center">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold mb-4" style="background: rgba(231,111,81,0.12); color: #E76F51;">
                    <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block" style="background:#E76F51;"></span>
                    <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block" style="background:#E76F51;"></span>
                    <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block" style="background:#E76F51;"></span>
                    Segera Hadir
                </div>

                <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text-primary);">Halaman Pengaturan</h2>
                <p class="text-sm leading-relaxed mb-6" style="color: var(--color-text-secondary);">
                    Kami sedang menyiapkan halaman pengaturan yang lebih lengkap dan personal.<br>
                    Sementara itu, nikmati fitur-fitur Savora lainnya ya!
                </p>

                {{-- Shimmer preview rows --}}
                <div class="float-card space-y-3 mb-6 text-left">
                    <div class="flex items-center gap-3 p-3 rounded-xl" style="border: 1px solid rgba(231,111,81,0.1); background: rgba(231,111,81,0.03);">
                        <div class="w-8 h-8 rounded-lg shimmer-row shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-2.5 shimmer-row w-1/3"></div>
                            <div class="h-2 shimmer-row w-2/3"></div>
                        </div>
                        <div class="w-10 h-5 rounded-full shimmer-row shrink-0"></div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl" style="border: 1px solid rgba(231,111,81,0.1); background: rgba(231,111,81,0.03);">
                        <div class="w-8 h-8 rounded-lg shimmer-row shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-2.5 shimmer-row w-2/5"></div>
                            <div class="h-2 shimmer-row w-3/5"></div>
                        </div>
                        <div class="w-10 h-5 rounded-full shimmer-row shrink-0"></div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl" style="border: 1px solid rgba(231,111,81,0.1); background: rgba(231,111,81,0.03);">
                        <div class="w-8 h-8 rounded-lg shimmer-row shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-2.5 shimmer-row w-1/4"></div>
                            <div class="h-2 shimmer-row w-1/2"></div>
                        </div>
                        <div class="w-10 h-5 rounded-full shimmer-row shrink-0"></div>
                    </div>
                </div>

                <a href="{{ route('app.home') }}" class="btn-primary-savora inline-flex items-center gap-2 px-6 py-3 rounded-xl">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </div>

</body>
</html>