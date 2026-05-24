<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
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

    <div class="w-full max-w-sm flex flex-col items-center gap-6">

        {{-- Logo --}}
        <div class="anim-logo flex flex-col items-center gap-3">
            <div class="relative w-20 h-20 rounded-full p-[3px] shadow-2xl" style="background: var(--gradient-logo)">
                <div class="w-full h-full bg-white rounded-full overflow-hidden flex items-center justify-center">
                    <img src="{{ asset('storage/images/logo.png') }}" alt="Savora"
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span style="display:none; color: var(--color-primary-coral)"
                        class="font-black text-2xl">S</span>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="anim-title flex flex-col items-center gap-2">
            <div class="px-4 py-1.5 bg-white/20 border border-white/40 rounded-full">
                <span class="text-white text-xs font-bold tracking-widest">WELCOME</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Savora</h1>
            <p class="text-white/80 text-sm text-center">Your culinary journey starts here</p>
        </div>

        {{-- White card --}}
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
                        <p class="text-red-700 font-bold text-sm">Account Disabled</p>
                    </div>
                    <p class="text-red-600 text-xs leading-relaxed ml-12">
                        Your account has been disabled by an administrator.
                    </p>
                    <div class="mt-2 ml-12 p-2.5 bg-red-100 rounded-xl">
                        <p class="text-red-500 text-xs font-semibold mb-0.5">Reason:</p>
                        <p class="text-red-700 text-xs">{{ session('banned_reason', 'Not specified') }}</p>
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
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-coral) width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="Email" autocomplete="email" autofocus required
                               class="input-savora has-icon py-3.5"
                               style="border-radius: 1rem">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <div class="input-wrapper-savora">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color: var(--color-primary-orange) width:18px; height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input :type="showPassword ? 'text' : 'password'" name="password"
                               placeholder="Password" autocomplete="current-password" required
                               class="input-savora has-icon pr-12 py-3.5"
                               style="border-radius: 1rem">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition-colors"
                                style="hover:color: var(--color-primary-orange)">
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

                <div class="mb-6 flex items-center justify-between gap-3">
                    <button type="button"
                            onclick="document.getElementById('resend-modal').classList.remove('hidden')"
                            class="text-xs font-semibold hover:underline"
                            style="color: var(--color-primary-coral)">
                        Email not verified?
                    </button>
                    <button type="button"
                            onclick="document.getElementById('reset-modal').classList.remove('hidden')"
                            class="text-xs font-semibold hover:underline"
                            style="color: var(--color-primary-teal)">
                        Forgot password?
                    </button>
                </div>

                {{-- Login button --}}
                <button type="submit" :disabled="loading"
                        class="btn-primary-savora w-full py-4 rounded-2xl text-base tracking-widest disabled:opacity-60 hover:scale-[1.01] active:scale-[0.99]">
                    <span x-show="!loading">LOG IN</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Processing...
                    </span>
                </button>

                {{-- OR divider --}}
                <div class="flex items-center gap-3 my-4">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-gray-400 text-xs font-semibold">OR</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <button type="button" id="google-login-button"
                        class="w-full py-3.5 rounded-2xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 transition-all flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9C6.71 7.3 9.14 5.38 12 5.38z"/>
                    </svg>
                    <span>Continue with Google</span>
                </button>

                {{-- Policy links --}}
                <p class="text-center text-xs text-gray-500 leading-relaxed mt-4">
                    By logging in, you agree to our
                    <button type="button" @click.prevent="PrivacyModal.show()"
                            class="font-semibold hover:underline" style="color: var(--color-primary-teal)">Privacy</button>
                    &amp;
                    <button type="button" @click.prevent="TermsModal.show()"
                            class="font-semibold hover:underline" style="color: var(--color-primary-coral)">Terms</button>
                </p>
            </form>
        </div>

        {{-- Register pill --}}
        <div class="anim-footer flex items-center gap-3 px-5 py-3 bg-white/15 border border-white/30 rounded-full">
            <span class="text-white/80 text-sm">Do not have an account?</span>
            <a href="{{ route('app.register') }}"
               class="px-4 py-1.5 bg-white rounded-full font-bold text-sm hover:bg-white/90 transition-all shadow"
               style="color: var(--color-primary-coral)">
                Register →
            </a>
        </div>

    </div>

    <form id="supabase-token-form" method="POST" action="{{ route('app.login.supabase-token') }}" class="hidden">
        @csrf
        <input type="hidden" name="supabase_token" id="supabase-token-input">
    </form>

    {{-- Resend verification modal --}}
    <div id="resend-modal"
         class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-6" onclick="event.stopPropagation()">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 bg-gradient-accent">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-gray-900 text-sm">Email Verification</h3>
                    <p class="text-xs text-gray-500">Resend account verification</p>
                </div>
                <button onclick="document.getElementById('resend-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('app.email.resend-verification') }}"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="input-wrapper-savora mb-4">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color: var(--color-primary-coral) width:18px; height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="Your email"
                           class="input-savora has-icon py-3.5"
                           style="border-radius: 1rem">
                </div>

                <div class="flex gap-2">
                    <button type="button"
                            onclick="document.getElementById('resend-modal').classList.add('hidden')"
                            class="flex-1 py-3 rounded-2xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="loading"
                            class="btn-primary-savora flex-1 py-3 rounded-2xl text-sm disabled:opacity-60">
                        <span x-show="!loading" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Send
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

    {{-- Reset password modal --}}
    <div id="reset-modal"
         class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-6" onclick="event.stopPropagation()">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 bg-gradient-accent">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H3v-4l5.257-5.257A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-gray-900 text-sm">Reset Password</h3>
                    <p class="text-xs text-gray-500">Receive a password reset link</p>
                </div>
                <button onclick="document.getElementById('reset-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('app.password.reset-email') }}"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="input-wrapper-savora mb-4">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color: var(--color-primary-teal) width:18px; height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" name="email" value="{{ old('reset_email') }}" required
                           placeholder="Your email"
                           class="input-savora has-icon py-3.5"
                           style="border-radius: 1rem">
                </div>

                <div class="flex gap-2">
                    <button type="button"
                            onclick="document.getElementById('reset-modal').classList.add('hidden')"
                            class="flex-1 py-3 rounded-2xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="loading"
                            class="btn-primary-savora flex-1 py-3 rounded-2xl text-sm disabled:opacity-60">
                        <span x-show="!loading">Send Link</span>
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

    {{-- New password modal after Supabase recovery link --}}
    <div id="new-password-modal"
         class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 bg-gradient-accent">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-gray-900 text-sm">New Password</h3>
                    <p class="text-xs text-gray-500">Create your new password</p>
                </div>
            </div>

            <div id="new-password-error" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-xs font-semibold"></div>

            <div class="input-wrapper-savora mb-3">
                <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color: var(--color-primary-orange) width:18px; height:18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input type="password" id="new-password" required
                       placeholder="New password"
                       class="input-savora has-icon py-3.5"
                       style="border-radius: 1rem">
            </div>

            <div class="input-wrapper-savora mb-5">
                <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color: var(--color-primary-orange) width:18px; height:18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <input type="password" id="new-password-confirm" required
                       placeholder="Confirm password"
                       class="input-savora has-icon py-3.5"
                       style="border-radius: 1rem">
            </div>

            <button type="button" id="new-password-button"
                    class="btn-primary-savora w-full py-3 rounded-2xl text-sm disabled:opacity-60">
                Update Password
            </button>
        </div>
    </div>

    @include('components.privacy-modal')
    @include('components.terms-modal')
    <script>
        const supabaseUrl = @json($supabaseUrl);
        const supabaseAnonKey = @json($supabaseAnonKey);
        const supabaseOAuthRedirectUrl = @json($supabaseOAuthRedirectUrl);
        const supabaseClient = window.supabase.createClient(supabaseUrl, supabaseAnonKey, {
            auth: {
                detectSessionInUrl: true,
                persistSession: true,
            },
        });

        async function submitSupabaseSession() {
            const hashParams = new URLSearchParams(window.location.hash.replace(/^#/, ''));
            if (hashParams.get('type') === 'recovery') {
                document.getElementById('new-password-modal').classList.remove('hidden');
                return;
            }

            const { data } = await supabaseClient.auth.getSession();
            const token = data?.session?.access_token;
            if (!token) return;

            document.getElementById('supabase-token-input').value = token;
            document.getElementById('supabase-token-form').submit();
        }

        document.getElementById('google-login-button')?.addEventListener('click', async () => {
            await supabaseClient.auth.signInWithOAuth({
                provider: 'google',
                options: {
                    redirectTo: supabaseOAuthRedirectUrl,
                },
            });
        });

        document.getElementById('new-password-button')?.addEventListener('click', async () => {
            const button = document.getElementById('new-password-button');
            const errorBox = document.getElementById('new-password-error');
            const password = document.getElementById('new-password').value;
            const confirm = document.getElementById('new-password-confirm').value;

            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            if (password.length < 6) {
                errorBox.textContent = 'Password must be at least 6 characters.';
                errorBox.classList.remove('hidden');
                return;
            }

            if (password !== confirm) {
                errorBox.textContent = 'Password confirmation does not match.';
                errorBox.classList.remove('hidden');
                return;
            }

            button.disabled = true;
            button.textContent = 'Updating...';

            const { error } = await supabaseClient.auth.updateUser({ password });
            if (error) {
                errorBox.textContent = error.message || 'Failed to update password.';
                errorBox.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Update Password';
                return;
            }

            await supabaseClient.auth.signOut();
            window.location.href = @json(route('app.login')) + '?password_reset=success';
        });

        submitSupabaseSession();
    </script>
</body>
</html>
