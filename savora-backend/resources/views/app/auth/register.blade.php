<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');

        body { font-family: 'Inter', sans-serif; }
        h1   { font-family: 'Poppins', sans-serif; }

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
<body class="bg-gradient-primary min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-sm flex flex-col items-center gap-6"
         x-data="{ loading: false, showPassword: false, agreeTerms: false }">

        {{-- Back button --}}
        <div class="w-full flex items-start anim-title">
            <a href="{{ route('app.login') }}"
               class="p-2.5 bg-white/25 rounded-2xl text-white hover:bg-white/35 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
        </div>

        {{-- Logo --}}
        <div class="anim-logo">
            <div class="relative w-20 h-20 rounded-full p-[3px] shadow-2xl" style="background: var(--gradient-logo)">
                <div class="w-full h-full bg-white rounded-full overflow-hidden flex items-center justify-center">
                    <img src="{{ asset('storage/images/logo.png') }}" alt="Savora"
                         class="w-full h-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span style="display:none; color: var(--color-primary-coral)" class="font-black text-2xl">S</span>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="anim-title flex flex-col items-center gap-2">
            <div class="px-4 py-1.5 bg-white/20 border border-white/40 rounded-full">
                <span class="text-white text-xs font-bold tracking-widest">JOIN</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Create Account</h1>
            <p class="text-white/80 text-sm text-center">Start your culinary journey</p>
        </div>

        {{-- White card --}}
        <div class="anim-card w-full bg-white rounded-3xl shadow-2xl p-6">

            {{-- Error alert --}}
            @if($errors->any() || !empty(session('error')))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-700 text-sm font-medium">
                        {{ $errors->first() ?: session('error') }}
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('app.register.post') }}" @submit="loading = true">
                @csrf

                {{-- Username --}}
                <div class="mb-4">
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-coral); width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" name="username" value="{{ old('username') }}"
                               placeholder="Username" maxlength="50" required
                               class="input-savora has-icon py-3.5"
                               style="border-radius: 1rem; --tw-border-opacity:1">
                    </div>
                </div>

                {{-- Nama Lengkap --}}
                <div class="mb-4">
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-teal); width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                               placeholder="Full Name" maxlength="100" required
                               class="input-savora has-icon py-3.5"
                               style="border-radius: 1rem">
                    </div>
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-yellow); width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="Email" autocomplete="email" required
                               class="input-savora has-icon py-3.5"
                               style="border-radius: 1rem">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-orange); width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input :type="showPassword ? 'text' : 'password'" name="password"
                               placeholder="Password (min. 6 characters)" minlength="6"
                               autocomplete="new-password" required
                               class="input-savora has-icon pr-12 py-3.5"
                               style="border-radius: 1rem">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition-colors">
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

                {{-- Consent checkbox --}}
                <label class="flex items-start gap-3 mb-5 cursor-pointer select-none">
                <div class="relative flex-shrink-0 mt-0.5">
                    <input type="checkbox" name="agree_terms" x-model="agreeTerms" required class="sr-only">
                    <div class="w-5 h-5 rounded-md border-2 transition-all flex items-center justify-center pointer-events-none"
                        :class="agreeTerms ? 'border-[#E76F51]' : 'bg-white border-gray-300'"
                        :style="agreeTerms ? 'background: var(--gradient-accent)' : ''">
                        <svg x-show="agreeTerms" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                    <span class="text-sm text-gray-600 leading-relaxed">
                        By registering, you agree to our
                        <button type="button" @click.prevent="TermsModal.show()"
                                class="font-semibold hover:underline" style="color: var(--color-primary-coral)">Terms &amp; Conditions</button>
                        and
                        <button type="button" @click.prevent="PrivacyModal.show()"
                                class="font-semibold hover:underline" style="color: var(--color-primary-teal)">Privacy Policy</button>.
                    </span>
                </label>

                {{-- DAFTAR button --}}
                <button type="submit" :disabled="!agreeTerms || loading"
                        class="btn-primary-savora w-full py-4 rounded-2xl text-base tracking-widest hover:scale-[1.01] active:scale-[0.99]"
                        :class="(!agreeTerms || loading) ? 'opacity-50 cursor-not-allowed !scale-100' : ''">
                    <span x-show="!loading">REGISTER NOW</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Processing...
                    </span>
                </button>
            </form>
        </div>

        {{-- Login pill --}}
        <div class="anim-footer w-full max-w-sm flex items-center gap-3 p-3 bg-white/15 border border-white/30 rounded-3xl">
            <span class="flex-1 text-center text-white/80 text-sm leading-snug">Already have an account?</span>
            <a href="{{ route('app.login') }}"
               class="shrink-0 px-4 py-2 bg-white rounded-2xl font-bold text-sm hover:bg-white/90 transition-all shadow"
               style="color: var(--color-primary-coral)">
                Log In &rarr;
            </a>
        </div>

    </div>

    @include('components.privacy-modal')
    @include('components.terms-modal')
</body>
</html>
