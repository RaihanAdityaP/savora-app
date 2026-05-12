<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #E76F51, #F4A261, #E9C46A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen">

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur-md bg-black/40 border-b border-white/5">
        <div class="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <div class="relative w-9 h-9 rounded-full p-[2px] shadow-lg" style="background: var(--gradient-logo);">
                    <div class="w-full h-full bg-white rounded-full overflow-hidden flex items-center justify-center">
                        <img src="{{ asset('storage/images/logo.png') }}" alt="Savora"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <span style="display:none" class="text-[#E76F51] font-black text-sm w-full h-full items-center justify-center">S</span>
                    </div>
                </div>
                <span class="font-black text-lg tracking-widest gradient-text">SAVORA</span>
            </a>
            <a href="{{ route('landing') }}"
               class="flex items-center gap-2 text-sm text-white/60 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Back
            </a>
        </div>
    </nav>

    {{-- Content --}}
    <main class="max-w-4xl mx-auto px-6 pt-28 pb-20">
        <h1 class="text-4xl font-black mb-2">License <span class="gradient-text">& Terms</span></h1>
        <p class="text-white/50 mb-12">Open-source license information used by the Savora app.</p>

        <div class="space-y-6">

            {{-- Flutter --}}
            <div class="bg-white/4 border border-white/8 rounded-2xl p-6">
                <h2 class="font-bold text-lg mb-1">Flutter</h2>
                <p class="text-white/50 text-sm mb-3">BSD 3-Clause License — Google LLC</p>
                <p class="text-white/60 text-sm leading-relaxed">
                    Framework UI open-source dari Google untuk membangun aplikasi mobile, web, dan desktop dari satu codebase.
                </p>
            </div>

            {{-- Laravel --}}
            <div class="bg-white/4 border border-white/8 rounded-2xl p-6">
                <h2 class="font-bold text-lg mb-1">Laravel</h2>
                <p class="text-white/50 text-sm mb-3">MIT License — Taylor Otwell</p>
                <p class="text-white/60 text-sm leading-relaxed">
                    Framework PHP untuk pengembangan aplikasi web dengan sintaks yang ekspresif dan elegan.
                </p>
            </div>

            {{-- Supabase --}}
            <div class="bg-white/4 border border-white/8 rounded-2xl p-6">
                <h2 class="font-bold text-lg mb-1">Supabase</h2>
                <p class="text-white/50 text-sm mb-3">Apache License 2.0 — Supabase Inc.</p>
                <p class="text-white/60 text-sm leading-relaxed">
                    Platform backend open-source sebagai alternatif Firebase, menyediakan database, autentikasi, dan storage.
                </p>
            </div>

            {{-- Tailwind CSS --}}
            <div class="bg-white/4 border border-white/8 rounded-2xl p-6">
                <h2 class="font-bold text-lg mb-1">Tailwind CSS</h2>
                <p class="text-white/50 text-sm mb-3">MIT License — Tailwind Labs Inc.</p>
                <p class="text-white/60 text-sm leading-relaxed">
                    Framework CSS utility-first untuk membangun antarmuka pengguna secara cepat dan konsisten.
                </p>
            </div>

        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-10 px-6 bg-white/3">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="font-black text-sm tracking-widest gradient-text">SAVORA</span>
            <p class="text-white/50 text-xs">© {{ date('Y') }} Savora. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
