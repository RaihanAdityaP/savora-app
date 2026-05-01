<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');

        body { font-family: 'Inter', sans-serif; }
        h1   { font-family: 'Poppins', sans-serif; }

        .gradient-bg {
            background: linear-gradient(135deg, #264653 0%, #2A9D8F 25%, #E9C46A 50%, #F4A261 75%, #E76F51 100%);
        }
        .gradient-btn {
            background: linear-gradient(135deg, #E76F51 0%, #F4A261 100%);
        }
        .logo-ring {
            background: linear-gradient(135deg, #2B6CB0, #FF6B35);
            padding: 3px;
            border-radius: 9999px;
            display: inline-flex;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes scalePop {
            0%   { opacity: 0; transform: scale(0.75); }
            70%  { transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }

        .anim-logo   { animation: scalePop 0.7s cubic-bezier(.34,1.56,.64,1) both; }
        .anim-title  { animation: fadeSlideUp 0.6s ease both 0.15s; }
        .anim-card   { animation: fadeSlideUp 0.6s ease both 0.3s; }
        .anim-footer { animation: fadeSlideUp 0.5s ease both 0.45s; }
    </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-sm flex flex-col items-center gap-6">

        {{-- ── Logo ── --}}
        <div class="anim-logo flex flex-col items-center gap-3">
            <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-[#2B6CB0] to-[#FF6B35] p-[3px] shadow-2xl">
                <div class="w-full h-full bg-white rounded-full overflow-hidden flex items-center justify-center">
                    <img src="{{ asset('storage/images/logo.png') }}" alt="Savora"
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span style="display:none"
                        class="text-[#E76F51] font-black text-2xl">S</span>
                </div>
            </div>
        </div>

        {{-- ── Title ── --}}
        <div class="anim-title flex flex-col items-center gap-2">
            <div class="px-4 py-1.5 bg-white/20 border border-white/40 rounded-full">
                <span class="text-white text-xs font-bold tracking-widest">SELAMAT DATANG</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Savora</h1>
            <p class="text-white/80 text-sm text-center">Petualangan Kuliner Dimulai Disini</p>
        </div>

        {{-- ── White card ── --}}
        <div class="anim-card w-full bg-white rounded-3xl shadow-2xl p-6">

            {{-- Banned alert --}}
            @if(session('banned'))
                <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-2xl">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524L13.476 14.89zm1.414-1.414L6.524 5.11A6 6 0 0114.89 13.476zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-red-700 font-bold text-sm">Akun Dinonaktifkan</p>
                    </div>
                    <p class="text-red-600 text-xs leading-relaxed ml-12">
                        Akun Anda telah dinonaktifkan oleh administrator.
                    </p>
                    <div class="mt-2 ml-12 p-2.5 bg-red-100 rounded-xl">
                        <p class="text-red-500 text-xs font-semibold mb-0.5">Alasan:</p>
                        <p class="text-red-700 text-xs">{{ session('banned_reason', 'Tidak disebutkan') }}</p>
                    </div>
                </div>

            {{-- Generic error --}}
            @elseif($errors->any() || !empty(session('error')))
                <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-700 text-sm font-medium">
                        {{ $errors->first() ?: session('error') }}
                    </span>
                </div>
            @endif

            {{-- Success --}}
            @if(session('status'))
                <div class="mb-5 p-3 bg-green-50 border border-green-200 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-700 text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('app.login.post') }}"
                  x-data="{ loading: false, showPassword: false }"
                  @submit="loading = true">
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#E76F51]"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="Email" autocomplete="email" autofocus required
                               class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#F4A261]"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input :type="showPassword ? 'text' : 'password'" name="password"
                               placeholder="Password" autocomplete="current-password" required
                               class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-[#F4A261] focus:bg-white transition-all">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#F4A261] transition-colors">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- MASUK button --}}
                <button type="submit" :disabled="loading"
                        class="w-full gradient-btn text-white font-black py-4 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.01] active:scale-[0.99] transition-all text-base tracking-widest disabled:opacity-60 flex items-center justify-center gap-2">
                    <span x-show="!loading">MASUK</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Memproses...
                    </span>
                </button>

                {{-- OR divider --}}
                <div class="flex items-center gap-3 my-4">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-gray-400 text-xs font-semibold">ATAU</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                {{-- Policy links --}}
                <p class="text-center text-xs text-gray-500 leading-relaxed">
                    Dengan masuk, Anda setuju dengan
                    <button type="button" @click.prevent="PrivacyModal.show()"
                            class="text-[#2A9D8F] font-semibold hover:underline">Privasi</button>
                    &amp;
                    <button type="button" @click.prevent="TermsModal.show()"
                            class="text-[#E76F51] font-semibold hover:underline">Syarat</button>
                    kami
                </p>
            </form>
        </div>

        {{-- ── Resend verification ── --}}
        <div class="anim-footer w-full">
            <button onclick="document.getElementById('resend-modal').classList.remove('hidden')"
                    class="w-full text-center text-white/75 text-sm hover:text-white underline underline-offset-2 transition-colors">
                Belum verifikasi email?
            </button>
        </div>

        {{-- ── Register pill ── --}}
        <div class="anim-footer flex items-center gap-3 px-5 py-3 bg-white/15 border border-white/30 rounded-full">
            <span class="text-white/80 text-sm">Belum punya akun?</span>
            <a href="{{ route('app.register') }}"
               class="px-4 py-1.5 bg-white rounded-full text-[#E76F51] font-bold text-sm hover:bg-white/90 transition-all shadow">
                Daftar →
            </a>
        </div>

    </div>

    {{-- ── Resend verification modal ── --}}
    <div id="resend-modal"
         class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 items-center justify-center p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-6" onclick="event.stopPropagation()">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #E76F51, #F4A261)">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-gray-900 text-sm">Verifikasi Email</h3>
                    <p class="text-xs text-gray-500">Kirim ulang link verifikasi</p>
                </div>
                <button onclick="document.getElementById('resend-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('app.register') }}"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="relative mb-4">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#E76F51]"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" name="resend_email" required
                           placeholder="Email Anda"
                           class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all">
                </div>

                <div class="flex gap-2">
                    <button type="button"
                            onclick="document.getElementById('resend-modal').classList.add('hidden')"
                            class="flex-1 py-3 rounded-2xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition-all">
                        Batal
                    </button>
                    <button type="submit" :disabled="loading"
                            class="flex-1 py-3 rounded-2xl gradient-btn text-white font-bold text-sm flex items-center justify-center gap-2 hover:shadow-lg transition-all disabled:opacity-60">
                        <span x-show="!loading" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim
                        </span>
                        <span x-show="loading">
                            <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('components.privacy-modal')
    @include('components.terms-modal')
</body>
</html>
