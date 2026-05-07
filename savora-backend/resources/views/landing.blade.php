<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savora — Temukan & Bagikan Resep Favoritmu</title>
    <meta name="description" content="Savora adalah platform resep masakan berbasis AI. Temukan, buat, dan bagikan resep lezat bersama komunitas.">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        .hero-bg {
            background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(231,111,81,.35) 0%, transparent 70%),
                        radial-gradient(ellipse 60% 40% at 80% 80%, rgba(42,157,143,.2) 0%, transparent 60%),
                        #0a0a0a;
        }
        .gradient-text {
            background: linear-gradient(135deg, #E76F51, #F4A261, #E9C46A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn-primary {
            background: linear-gradient(135deg, #E76F51, #F4A261);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(231,111,81,.4); }
        .card-feature {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            transition: transform .2s, border-color .2s;
        }
        .card-feature:hover { transform: translateY(-4px); border-color: rgba(231,111,81,.3); }
        .phone-mockup {
            background: linear-gradient(145deg, #1a1a1a, #111);
            border: 2px solid rgba(255,255,255,.1);
            box-shadow: 0 40px 80px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.05);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .float-anim { animation: float 4s ease-in-out infinite; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp .7s ease both; }
        .fade-up-2 { animation: fadeUp .7s ease both .15s; }
        .fade-up-3 { animation: fadeUp .7s ease both .3s; }
        .fade-up-4 { animation: fadeUp .7s ease both .45s; }
    </style>
</head>
<body class="hero-bg min-h-screen">

    {{-- ── Navbar ── --}}
    <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur-md bg-black/40 border-b border-white/5">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="relative w-9 h-9 rounded-xl bg-gradient-to-br from-[{{ \App\View\Components\AppTheme::LOGO_BLUE }}] to-[{{ \App\View\Components\AppTheme::LOGO_ORANGE }}] p-[2px] shadow-lg">
                    <div class="w-full h-full bg-white rounded-[10px] overflow-hidden flex items-center justify-center">
                        <img src="{{ asset('storage/images/logo.png') }}" alt="Savora"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <span style="display:none" class="text-[#E76F51] font-black text-sm w-full h-full items-center justify-center">S</span>
                    </div>
                </div>
                <span class="font-black text-lg tracking-widest gradient-text">SAVORA</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('license') }}" class="text-sm text-white/60 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                    Lisensi
                </a>
                <a href="{{ route('app.login') }}" class="text-sm text-white/80 hover:text-white transition-colors px-4 py-2 rounded-xl border border-white/10 hover:border-white/20">
                    Masuk
                </a>
                <a href="{{ route('app.register') }}" class="btn-primary text-sm font-semibold text-white px-4 py-2 rounded-xl">
                    Daftar
                </a>
            </div>
        </div>
    </nav>

    {{-- ── Hero ── --}}
    <section class="pt-32 pb-20 px-6">
        <div class="max-w-6xl mx-auto flex flex-col lg:flex-row items-center gap-16">

            {{-- Left --}}
            <div class="flex-1 text-center lg:text-left">
                <div class="fade-up inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-1.5 text-sm text-white/70 mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#4CAF50] shadow-[0_0_6px_#4CAF50]"></span>
                    Tersedia untuk Android
                </div>
                <h1 class="fade-up-2 text-5xl lg:text-6xl font-black leading-tight mb-6">
                    Temukan Resep<br>
                    <span class="gradient-text">Lezat Setiap Hari</span>
                </h1>
                <p class="fade-up-3 text-white/60 text-lg leading-relaxed mb-10 max-w-lg mx-auto lg:mx-0">
                    Savora adalah platform resep berbasis AI. Jelajahi ribuan resep, buat koleksi favoritmu, dan dapatkan rekomendasi personal dari chef AI kami.
                </p>
                <div class="fade-up-4 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @if(env('APK_DOWNLOAD_URL'))
                    <a href="{{ env('APK_DOWNLOAD_URL') }}" download
                       class="btn-primary flex items-center justify-center gap-3 px-7 py-4 rounded-2xl font-bold text-white text-base shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/>
                            <path d="M8 12l4 4 4-4M12 8v8"/>
                        </svg>
                        Download APK
                    </a>
                    @else
                    <span class="flex items-center justify-center gap-3 px-7 py-4 rounded-2xl font-bold text-white/40 text-base border border-white/10 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/>
                            <path d="M8 12l4 4 4-4M12 8v8"/>
                        </svg>
                        APK Segera Hadir
                    </span>
                    @endif
                    <a href="{{ route('app.register') }}"
                       class="flex items-center justify-center gap-2 px-7 py-4 rounded-2xl font-semibold text-white/80 text-base border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all">
                        Coba Versi Web
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Right — phone mockup --}}
            <div class="flex-shrink-0 float-anim">
                {{-- Device frame --}}
                <div class="relative w-[260px]">
                    {{-- Side buttons --}}
                    <div class="absolute -left-[3px] top-24 w-[3px] h-8 bg-[#2a2a2a] rounded-l-sm"></div>
                    <div class="absolute -left-[3px] top-36 w-[3px] h-12 bg-[#2a2a2a] rounded-l-sm"></div>
                    <div class="absolute -left-[3px] top-52 w-[3px] h-12 bg-[#2a2a2a] rounded-l-sm"></div>
                    <div class="absolute -right-[3px] top-36 w-[3px] h-16 bg-[#2a2a2a] rounded-r-sm"></div>

                    {{-- Phone body --}}
                    <div class="phone-mockup w-full rounded-[3rem] overflow-hidden" style="height: 540px; border: 2px solid rgba(255,255,255,0.12);">

                        {{-- Status bar --}}
                        <div class="bg-[#111] px-5 pt-3 pb-1 flex items-center justify-between">
                            {{-- Dynamic island --}}
                            <div class="absolute left-1/2 -translate-x-1/2 top-3 w-24 h-6 bg-black rounded-full z-10"></div>
                            <span class="text-white text-[10px] font-semibold">9:41</span>
                            <div class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.56 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><rect x="1" y="6" width="18" height="12" rx="2"/><path d="M23 13v-2a2 2 0 0 0 0 4v-2z"/></svg>
                            </div>
                        </div>

                        {{-- App content --}}
                        <div class="bg-[#0f0f0f] h-full px-4 pt-6 pb-4 flex flex-col gap-3 overflow-hidden">

                            {{-- App header --}}
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-white/40 text-[10px]">Selamat datang 👋</p>
                                    <p class="text-white font-bold text-sm">Temukan Resep</p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#E76F51] to-[#F4A261] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </div>
                            </div>

                            {{-- Search bar --}}
                            <div class="bg-white/5 border border-white/8 rounded-xl px-3 py-2 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-white/30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                <span class="text-white/30 text-[11px]">Cari resep...</span>
                            </div>

                            {{-- Recipe cards --}}
                            @foreach([
                                ['emoji' => '🍜', 'name' => 'Mie Goreng Spesial', 'time' => '20 mnt', 'rating' => '4.8'],
                                ['emoji' => '🥗', 'name' => 'Caesar Salad', 'time' => '15 mnt', 'rating' => '4.6'],
                                ['emoji' => '🍰', 'name' => 'Tiramisu Klasik', 'time' => '45 mnt', 'rating' => '4.9'],
                            ] as $recipe)
                            <div class="bg-white/5 border border-white/8 rounded-xl p-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#E76F51]/20 to-[#F4A261]/10 flex items-center justify-center text-lg flex-shrink-0">
                                    {{ $recipe['emoji'] }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-[11px] font-semibold truncate">{{ $recipe['name'] }}</p>
                                    <p class="text-white/40 text-[10px]">⏱ {{ $recipe['time'] }}</p>
                                </div>
                                <div class="flex items-center gap-0.5">
                                    <span class="text-[#F4A261] text-[10px]">★</span>
                                    <span class="text-white/60 text-[10px]">{{ $recipe['rating'] }}</span>
                                </div>
                            </div>
                            @endforeach

                            {{-- AI chip --}}
                            <div class="mt-auto bg-gradient-to-r from-[#E76F51]/20 to-[#F4A261]/10 border border-[#E76F51]/30 rounded-xl p-3 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-[#E76F51] to-[#F4A261] flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <p class="text-white/70 text-[10px]">Tanya AI Chef sekarang →</p>
                            </div>

                            {{-- Bottom nav --}}
                            <div class="bg-white/5 border border-white/8 rounded-2xl px-4 py-2 flex items-center justify-around">
                                @foreach(['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'] as $i => $icon)
                                <div class="{{ $i === 0 ? 'text-[#E76F51]' : 'text-white/30' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Features ── --}}
    <section class="py-20 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-black mb-3">Kenapa Savora?</h2>
                <p class="text-white/50">Semua yang kamu butuhkan untuk memasak lebih baik</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'title' => 'AI Chef Pribadi', 'desc' => 'Chat dengan AI untuk rekomendasi resep, tips memasak, dan substitusi bahan secara real-time.'],
                    ['icon' => 'M4.318 6.318a4.5 4.5 0 0 0 0 6.364L12 20.364l7.682-7.682a4.5 4.5 0 0 0-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 0 0-6.364 0z', 'title' => 'Koleksi Favorit', 'desc' => 'Simpan resep ke board favoritmu dan akses kapan saja, bahkan saat offline.'],
                    ['icon' => 'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z', 'title' => 'Komunitas Aktif', 'desc' => 'Bagikan resepmu, ikuti chef favorit, dan dapatkan feedback dari komunitas memasak.'],
                    ['icon' => 'M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z', 'title' => 'Pencarian Cerdas', 'desc' => 'Cari resep berdasarkan bahan yang ada di dapur, kategori, atau tag spesifik.'],
                    ['icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 0 0 .95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 0 0-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 0 0-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 0 0-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 0 0 .951-.69l1.519-4.674z', 'title' => 'Rating & Review', 'desc' => 'Nilai resep dan baca ulasan jujur dari pengguna lain sebelum mulai memasak.'],
                    ['icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9', 'title' => 'Notifikasi Real-time', 'desc' => 'Dapatkan notifikasi saat ada komentar, like, atau follower baru di profilmu.'],
                ] as $f)
                <div class="card-feature rounded-2xl p-6">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E76F51]/20 to-[#F4A261]/10 border border-[#E76F51]/20 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-[#F4A261]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="{{ $f['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-base mb-2">{{ $f['title'] }}</h3>
                    <p class="text-white/50 text-sm leading-relaxed">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Download CTA ── --}}
    @if(env('APK_DOWNLOAD_URL'))
    <section class="py-20 px-6">
        <div class="max-w-2xl mx-auto text-center">
            <div class="bg-gradient-to-br from-[#E76F51]/10 to-[#F4A261]/5 border border-[#E76F51]/20 rounded-3xl p-12">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#E76F51] to-[#F4A261] flex items-center justify-center mx-auto mb-6 shadow-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-black mb-3">Download Savora</h2>
                <p class="text-white/50 mb-8">Tersedia untuk Android. Gratis selamanya.</p>
                <a href="{{ env('APK_DOWNLOAD_URL') }}" download
                   class="btn-primary inline-flex items-center gap-3 px-8 py-4 rounded-2xl font-bold text-white text-base shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download APK Android
                </a>
                <p class="text-white/30 text-xs mt-4">Aktifkan "Install dari sumber tidak dikenal" di pengaturan Android</p>
            </div>
        </div>
    </section>
    @endif

    {{-- ── Footer ── --}}
    <footer class="border-t border-white/10 py-10 px-6 bg-white/[0.03]">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="font-black text-sm tracking-widest gradient-text">SAVORA</span>
            <div class="flex items-center gap-6 text-sm text-white/60">
                <a href="{{ route('license') }}" class="hover:text-white transition-colors">Lisensi</a>
                <a href="{{ route('app.login') }}" class="hover:text-white transition-colors">Masuk</a>
                <a href="{{ route('app.register') }}" class="hover:text-white transition-colors">Daftar</a>
            </div>
            <p class="text-white/50 text-xs">© {{ date('Y') }} Savora. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
