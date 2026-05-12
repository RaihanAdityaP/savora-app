<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savora — Discover & Share Your Favorite Recipes</title>
    <meta name="description" content="Savora is an AI-powered recipe platform. Discover, create, and share delicious recipes with the community.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- AppTheme CSS vars + utility classes --}}
    @include('components.app-theme')

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #fff;
        }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        .hero-bg {
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(231,111,81,.30) 0%, transparent 70%),
                radial-gradient(ellipse 55% 40% at 85% 85%, rgba(42,157,143,.18) 0%, transparent 60%),
                #0a0a0a;
        }

        .gradient-text {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-glass {
            background: rgba(10,10,10,.70);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .card-feature-dark {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: var(--radius-xl);
            transition: transform .25s, border-color .25s;
        }
        .card-feature-dark:hover {
            transform: translateY(-4px);
            border-color: rgba(231,111,81,.30);
        }

        .android-frame {
            background: #111;
            border: 2px solid rgba(255,255,255,.15);
            border-radius: 2rem;
            box-shadow:
                0 50px 100px rgba(0,0,0,.70),
                0 0 0 1px rgba(255,255,255,.04),
                inset 0 1px 0 rgba(255,255,255,.08);
            overflow: hidden;
            position: relative;
        }

        .android-statusbar {
            background: rgba(0,0,0,.6);
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            position: relative;
            z-index: 10;
        }

        .android-camera {
            width: 10px;
            height: 10px;
            background: #000;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.15);
        }

        .android-navbar {
            background: rgba(0,0,0,.5);
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            position: relative;
            z-index: 10;
        }
        .android-navbar-pill {
            width: 60px;
            height: 4px;
            background: rgba(255,255,255,.35);
            border-radius: 9999px;
        }

        .phone-screen-img {
            width: 100%;
            display: block;
            object-fit: cover;
            object-position: top center;
        }

        .android-btn-power {
            position: absolute;
            right: -3px;
            top: 100px;
            width: 3px;
            height: 52px;
            background: #2a2a2a;
            border-radius: 0 2px 2px 0;
        }
        .android-btn-vol-up {
            position: absolute;
            left: -3px;
            top: 80px;
            width: 3px;
            height: 36px;
            background: #2a2a2a;
            border-radius: 2px 0 0 2px;
        }
        .android-btn-vol-dn {
            position: absolute;
            left: -3px;
            top: 128px;
            width: 3px;
            height: 36px;
            background: #2a2a2a;
            border-radius: 2px 0 0 2px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up   { animation: fadeUp .75s cubic-bezier(.22,1,.36,1) both; }
        .fade-up-2 { animation: fadeUp .75s cubic-bezier(.22,1,.36,1) both .12s; }
        .fade-up-3 { animation: fadeUp .75s cubic-bezier(.22,1,.36,1) both .24s; }
        .fade-up-4 { animation: fadeUp .75s cubic-bezier(.22,1,.36,1) both .36s; }

        .cta-card {
            background: linear-gradient(135deg, rgba(231,111,81,.12), rgba(244,162,97,.06));
            border: 1px solid rgba(231,111,81,.22);
            border-radius: 1.75rem;
        }

        .pill-live {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 9999px;
        }

        .section-glow {
            background: linear-gradient(90deg, transparent, rgba(231,111,81,.14), transparent);
            height: 1px;
            max-width: 72rem;
            margin: 0 auto;
        }

        .btn-apk-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 28px;
            border-radius: 1rem;
            font-weight: 700;
            font-size: 1rem;
            border: 1px solid rgba(255,255,255,.10);
            color: rgba(255,255,255,.30);
            cursor: not-allowed;
        }

        /* ── Support section ── */
        .support-section {
            background:
                radial-gradient(ellipse 60% 50% at 50% 100%, rgba(244,162,97,.10) 0%, transparent 70%),
                rgba(255,255,255,.015);
            border-top: 1px solid rgba(255,255,255,.06);
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .support-card {
            background: rgba(255,255,255,.035);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 2rem;
            position: relative;
            overflow: hidden;
        }
        .support-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(231,111,81,.10) 0%, transparent 60%);
            pointer-events: none;
        }
        .trakteer-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #E76F51 0%, #F4A261 50%, #E9C46A 100%);
            color: #fff;
            font-weight: 800;
            font-size: 1.0625rem;
            text-decoration: none;
            padding: 16px 36px;
            border-radius: 9999px;
            box-shadow: 0 8px 32px rgba(231,111,81,.35), 0 2px 8px rgba(0,0,0,.4);
            transition: transform .2s, box-shadow .2s;
            position: relative;
            z-index: 1;
        }
        .trakteer-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 16px 48px rgba(231,111,81,.45), 0 4px 16px rgba(0,0,0,.4);
        }
        .trakteer-btn:active { transform: translateY(0) scale(0.99); }
        .support-tier {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 1rem;
            padding: 16px 20px;
            text-align: center;
            transition: border-color .2s, transform .2s;
        }
        .support-tier:hover { border-color: rgba(231,111,81,.30); transform: translateY(-2px); }
        .coin-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px; height: 44px;
            border-radius: 50%;
            background: rgba(233,196,106,.12);
            border: 1px solid rgba(233,196,106,.22);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-6px); }
        }
        .float-anim { animation: float 3.5s ease-in-out infinite; }
        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0    rgba(231,111,81,.45); }
            70%  { box-shadow: 0 0 0 14px rgba(231,111,81,0); }
            100% { box-shadow: 0 0 0 0    rgba(231,111,81,0); }
        }
        .pulse-ring { animation: pulse-ring 2.4s ease-out infinite; }

        @media (max-width: 420px) {
            .nav-shell {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            .nav-actions { gap: 0.4rem; }
            .nav-license { display: none; }
            .nav-login {
                padding: 0.4rem 0.7rem;
                font-size: 0.8125rem;
            }
            .nav-register {
                padding: 0.4rem 0.75rem !important;
                font-size: 0.8125rem !important;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body class="hero-bg min-h-screen">

    {{-- NAVBAR --}}
    <nav class="navbar-glass fixed top-0 inset-x-0 z-50">
        <div class="nav-shell max-w-6xl mx-auto px-6 h-16 flex items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="relative w-9 h-9 rounded-full p-0.5 shadow-lg"
                     style="background: var(--gradient-logo)">
                    <div class="w-full h-full bg-white rounded-full overflow-hidden flex items-center justify-center">
                        <img src="{{ asset('storage/images/logo.png') }}"
                             alt="Savora"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <span style="display:none;color:var(--color-primary-coral)"
                              class="font-black text-sm w-full h-full items-center justify-center flex">S</span>
                    </div>
                </div>
                <span class="font-black text-lg tracking-widest gradient-text">SAVORA</span>
            </div>
            <div class="nav-actions flex items-center gap-3">
                <a href="#support"
                   class="nav-license text-sm transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5"
                   style="color:rgba(255,255,255,.55)">Support</a>
                <a href="{{ route('license') }}"
                   class="nav-license text-sm transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5"
                   style="color:rgba(255,255,255,.55)">Lisensi</a>
                <a href="{{ route('app.login') }}"
                   class="nav-login text-sm transition-all px-4 py-2 rounded-xl border hover:bg-white/5"
                   style="color:rgba(255,255,255,.80);border-color:rgba(255,255,255,.12)">Masuk</a>
                <a href="{{ route('app.register') }}"
                   class="nav-register btn-primary-savora"
                   style="padding:8px 18px;border-radius:var(--radius-md);font-size:var(--text-sm)">Daftar</a>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="pt-32 pb-24 px-6">
        <div class="max-w-6xl mx-auto flex flex-col lg:flex-row items-center gap-16 lg:gap-20">

            {{-- Left copy --}}
            <div class="flex-1 text-center lg:text-left">
                <div class="fade-up pill-live inline-flex items-center gap-2 px-4 py-1.5 text-sm mb-7"
                     style="color:rgba(255,255,255,.65)">
                    <span class="w-2 h-2 rounded-full"
                          style="background:#4CAF50;box-shadow:0 0 6px #4CAF50"></span>
                    Available on Android
                </div>

                <h1 class="fade-up-2 text-5xl lg:text-6xl font-black leading-[1.1] mb-6">
                    Discover Recipes<br>
                    <span class="gradient-text">Delicious Every Day</span>
                </h1>

                <p class="fade-up-3 text-lg leading-relaxed mb-10 max-w-lg mx-auto lg:mx-0"
                   style="color:rgba(255,255,255,.55)">
                    Savora is an AI-powered recipe platform. Explore thousands of recipes,
                    build your favorite collection, and get personal recommendations from our AI chef.
                </p>

                <div class="fade-up-4 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @if(env('APK_DOWNLOAD_URL'))
                    <a href="{{ env('APK_DOWNLOAD_URL') }}" download
                       class="btn-primary-savora rounded-2xl px-7 py-4 text-base shadow-xl">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Download APK
                    </a>
                    @else
                    <span class="btn-apk-disabled">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        APK Coming Soon
                    </span>
                    @endif

                    <a href="{{ route('app.register') }}"
                       class="btn-outlined-savora rounded-2xl px-7 py-4 text-base">
                        Try Web Version
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Right — Android phone mockup --}}
            <div class="shrink-0">
                <div class="relative" style="width:260px">
                    <div class="android-btn-power"></div>
                    <div class="android-btn-vol-up"></div>
                    <div class="android-btn-vol-dn"></div>

                    <div class="android-frame">
                        <div class="android-statusbar">
                            <div class="android-camera"></div>
                            <span style="font-size:10px;color:rgba(255,255,255,.7);font-family:'Inter',sans-serif;font-weight:600">10:35</span>
                            <div style="display:flex;align-items:center;gap:4px">
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <rect x="0" y="6" width="2" height="4" rx=".5" fill="rgba(255,255,255,.7)"/>
                                    <rect x="3" y="4" width="2" height="6" rx=".5" fill="rgba(255,255,255,.7)"/>
                                    <rect x="6" y="2" width="2" height="8" rx=".5" fill="rgba(255,255,255,.7)"/>
                                    <rect x="9" y="0" width="2" height="10" rx=".5" fill="rgba(255,255,255,.7)"/>
                                </svg>
                                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M6 8.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" fill="rgba(255,255,255,.7)"/>
                                    <path d="M2.5 5.5C3.5 4.5 4.7 4 6 4s2.5.5 3.5 1.5" stroke="rgba(255,255,255,.7)" stroke-width="1.2" stroke-linecap="round"/>
                                    <path d="M.5 3.5C2 2 3.9 1 6 1s4 1 5.5 2.5" stroke="rgba(255,255,255,.5)" stroke-width="1.2" stroke-linecap="round"/>
                                </svg>
                                <svg width="16" height="10" viewBox="0 0 16 10" fill="none">
                                    <rect x=".5" y=".5" width="13" height="9" rx="1.5" stroke="rgba(255,255,255,.7)"/>
                                    <rect x="1.5" y="1.5" width="9" height="7" rx="1" fill="rgba(255,255,255,.7)"/>
                                    <path d="M14 3.5v3" stroke="rgba(255,255,255,.5)" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>

                        <img src="{{ asset('storage/images/homescreen.jpg') }}"
                             alt="Savora App"
                             class="phone-screen-img"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">

                        <div style="display:none;height:440px;background:#0f0f0f;flex-direction:column;align-items:center;justify-content:center;gap:12px">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                 style="background:var(--gradient-accent)">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <p class="font-black tracking-widest gradient-text text-sm">SAVORA</p>
                        </div>

                        <div class="android-navbar">
                            <div class="android-navbar-pill"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-glow"></div>

    {{-- FEATURES --}}
    <section class="py-24 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 flex flex-col items-center gap-4">
                <x-app-theme.section-header
                    title="Why Savora?"
                    title-color="#ffffff"
                    icon='<svg class="w-[18px] h-[18px]" fill="#ffffff" viewBox="0 0 24 24">
                        <path d="M12 2l2.9 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l7.1-1.01L12 2z"/>
                    </svg>'
                />
                <p class="text-base" style="color:rgba(255,255,255,.45)">
                    Everything you need to cook better
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    [
                        'icon'  => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                        'title' => 'Personal AI Chef',
                        'desc'  => 'Chat with AI for recipe advice, cooking tips, and ingredient substitutions in real time.',
                        'color' => 'var(--color-primary-teal)',
                    ],
                    [
                        'icon'  => 'M4.318 6.318a4.5 4.5 0 0 0 0 6.364L12 20.364l7.682-7.682a4.5 4.5 0 0 0-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 0 0-6.364 0z',
                        'title' => 'Favorites Collection',
                        'desc'  => 'Save recipes to your favorites board and access them anytime, even offline.',
                        'color' => 'var(--color-primary-coral)',
                    ],
                    [
                        'icon'  => 'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
                        'title' => 'Active Community',
                        'desc'  => 'Share your recipes, follow favorite chefs, and get feedback from fellow cooks.',
                        'color' => 'var(--color-primary-orange)',
                    ],
                    [
                        'icon'  => 'M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z',
                        'title' => 'Smart Search',
                        'desc'  => 'Search recipes by ingredients, category, or specific tags.',
                        'color' => 'var(--color-primary-yellow)',
                    ],
                    [
                        'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 0 0 .95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 0 0-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 0 0-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 0 0-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 0 0 .951-.69l1.519-4.674z',
                        'title' => 'Ratings & Reviews',
                        'desc'  => 'Rate recipes and read honest user reviews before you start cooking.',
                        'color' => 'var(--color-primary-yellow)',
                    ],
                    [
                        'icon'  => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9',
                        'title' => 'Real-time Notifications',
                        'desc'  => 'Receive alerts for comments, likes, and new followers on your profile.',
                        'color' => 'var(--color-primary-coral)',
                    ],
                ] as $f)
                <div class="card-feature-dark p-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-5"
                         style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
                        <svg class="w-5 h-5" fill="none" stroke="{{ $f['color'] }}"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="{{ $f['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-base mb-2">{{ $f['title'] }}</h3>
                    <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,.48)">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="section-glow"></div>

    {{-- SUPPORT THE DEVELOPER --}}
    <section id="support" class="support-section py-28 px-6">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-14">
                <div class="float-anim inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-7"
                     style="background:linear-gradient(135deg,rgba(233,196,106,.15),rgba(244,162,97,.12));border:1px solid rgba(233,196,106,.22)">
                    <svg width="38" height="38" viewBox="0 0 38 38" fill="none">
                        <path d="M8 14h22l-2.5 14A3 3 0 0 1 24.5 31h-11A3 3 0 0 1 10.5 28L8 14z"
                              stroke="#F4A261" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M30 17h2a3 3 0 0 1 0 6h-2"
                              stroke="#F4A261" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 8c0-2 2-2 2-4M19 8c0-2 2-2 2-4M23 8c0-2 2-2 2-4"
                              stroke="#E9C46A" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>

                <p class="text-sm font-semibold tracking-widest mb-3"
                   style="color:var(--color-primary-orange)">SUPPORT THE DEVELOPER</p>

                <h2 class="text-4xl lg:text-5xl font-black mb-5 leading-tight">
                    Keep Savora<br>
                    <span class="gradient-text">Growing</span>
                </h2>

                <p class="text-base leading-relaxed max-w-lg mx-auto"
                   style="color:rgba(255,255,255,.50)">
                    Savora is built with love by an indie developer. Your support helps keep
                    the servers running, new features shipping, and the app free for everyone.
                </p>
            </div>

            {{-- Main card --}}
            <div class="support-card p-10 lg:p-14 text-center mb-10">

                {{-- 3 pillars --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-12">
                    <div class="support-tier">
                        <div class="coin-icon mx-auto mb-3">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                 stroke="#E9C46A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                <line x1="2" y1="10" x2="22" y2="10"/>
                            </svg>
                        </div>
                        <p class="font-bold text-sm mb-1">Server Costs</p>
                        <p class="text-xs" style="color:rgba(255,255,255,.40)">Keep the app online 24/7</p>
                    </div>

                    <div class="support-tier">
                        <div class="coin-icon mx-auto mb-3">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                 stroke="#E9C46A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="16 18 22 12 16 6"/>
                                <polyline points="8 6 2 12 8 18"/>
                            </svg>
                        </div>
                        <p class="font-bold text-sm mb-1">New Features</p>
                        <p class="text-xs" style="color:rgba(255,255,255,.40)">Fund ongoing development</p>
                    </div>

                    <div class="support-tier">
                        <div class="coin-icon mx-auto mb-3">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                 stroke="#E9C46A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <p class="font-bold text-sm mb-1">Stay Free</p>
                        <p class="text-xs" style="color:rgba(255,255,255,.40)">No paywalls, forever</p>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="flex flex-col items-center gap-5">
                    <a href="https://trakteer.id/rendyt"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="trakteer-btn pulse-ring">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 8h1a4 4 0 0 1 0 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z"/>
                            <line x1="6" y1="2" x2="6" y2="4"/>
                            <line x1="10" y1="2" x2="10" y2="4"/>
                            <line x1="14" y1="2" x2="14" y2="4"/>
                        </svg>
                        Buy Me a Coffee on Trakteer
                    </a>

                    <p class="text-xs" style="color:rgba(255,255,255,.30)">
                        You'll be redirected to
                        <span style="color:rgba(255,255,255,.50);font-weight:600">trakteer.id/rendyt</span>
                        &nbsp;·&nbsp; Secure &amp; no account required
                    </p>
                </div>


            </div>

            {{-- Reassurance note --}}
            <div class="flex items-start gap-4 px-6 py-5 rounded-2xl"
                 style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06)">
                <div class="shrink-0 mt-0.5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="rgba(255,255,255,.35)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,.40)">
                    Your contribution is entirely voluntary — Savora will always be free to use.
                    Every coffee you buy goes directly to the developer to improve this app and keep it ad-free.
                    Thank you so much, it genuinely means the world. ☕
                </p>
            </div>

        </div>
    </section>

    <div class="section-glow"></div>

    {{-- DOWNLOAD CTA --}}
    @if(env('APK_DOWNLOAD_URL'))
    <section class="py-24 px-6">
        <div class="max-w-2xl mx-auto text-center">
            <div class="cta-card p-14">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-7 shadow-xl"
                     style="background:var(--gradient-accent)">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-black mb-3">Download Savora</h2>
                <p class="mb-9" style="color:rgba(255,255,255,.48)">Available on Android. Free forever.</p>
                <a href="{{ env('APK_DOWNLOAD_URL') }}" download
                   class="btn-primary-savora rounded-2xl px-8 py-4 text-base shadow-xl mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download APK Android
                </a>
                <div class="mt-8 text-left">
                    @include('components.app-theme.info-banner', [
                        'message' => 'Enable "Install from unknown sources" in Android Settings before installing.',
                        'icon'    => 'bi bi-shield-check',
                    ])
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- FOOTER --}}
    <footer style="border-top:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02)"
            class="py-10 px-6">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-5">
            <span class="font-black text-sm tracking-widest gradient-text">SAVORA</span>
            <div class="flex items-center gap-6 text-sm" style="color:rgba(255,255,255,.50)">
                <a href="#support" class="transition-colors hover:text-white">Support</a>
                <a href="{{ route('license') }}" class="transition-colors hover:text-white">License</a>
                <a href="{{ route('app.login') }}" class="transition-colors hover:text-white">Log In</a>
                <a href="{{ route('app.register') }}" class="transition-colors hover:text-white">Register</a>
            </div>
            <p class="text-xs" style="color:rgba(255,255,255,.30)">
                &copy; {{ date('Y') }} Savora. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>