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
        h1, h2 { font-family: 'Poppins', sans-serif; }
        
        .gradient-primary {
            background: linear-gradient(135deg, #264653 0%, #2A9D8F 25%, #E9C46A 50%, #F4A261 75%, #E76F51 100%);
        }
        
        .gradient-accent {
            background: linear-gradient(135deg, #E76F51 0%, #F4A261 100%);
        }
        
        .input-focus:focus {
            @apply outline-none ring-2 ring-orange-400 ring-opacity-50;
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        .slide-up {
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="gradient-primary min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md slide-up">
        <!-- Card Container -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header Section -->
            <div class="gradient-accent h-24 flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute -top-16 -right-16 w-32 h-32 bg-white rounded-full"></div>
                    <div class="absolute -bottom-8 -left-8 w-20 h-20 bg-white rounded-full"></div>
                </div>
                <div class="relative text-white text-center">
                    <h1 class="text-3xl font-900">Savora</h1>
                    <p class="text-sm font-500 opacity-90">Resep Terbaik untuk Mu</p>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-8 fade-in">
                <h2 class="text-2xl font-700 text-gray-900 mb-2">Selamat Datang</h2>
                <p class="text-gray-600 text-sm mb-6">Masuk dengan akun Anda untuk melanjutkan</p>

                <!-- Error Alert -->
                @if($errors->any() || session('error'))
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg fade-in">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-red-700 text-sm font-500">
                                {{ $errors->first() ?? session('error') }}
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Success Alert -->
                @if(session('status'))
                    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg fade-in">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-green-700 text-sm font-500">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Login Form -->
                    <form method="POST" action="{{ route('app.login.post') }}" x-data="{ loading: false, agreeTerms: false }" @submit="loading = true">
                    @csrf

                    <!-- Email Field -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-600 text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <svg class="absolute left-4 top-3.5 w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nama@email.com"
                                autocomplete="email"
                                autofocus
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl transition-all input-focus bg-gray-50 hover:bg-white"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-600 text-gray-700 mb-2">Password</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <svg class="absolute left-4 top-3.5 w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                id="password" 
                                name="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                class="w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl transition-all input-focus bg-gray-50 hover:bg-white"
                                required
                            >
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-3.5 text-gray-500 hover:text-orange-500 transition-colors"
                            >
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        :disabled="!agreeTerms || loading"
                        class="w-full gradient-accent text-white font-700 py-3 rounded-xl transition-all transform hover:shadow-lg hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span x-show="!loading">Masuk ke Akun</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Memproses...
                        </span>
                    </button>

                    <label class="flex items-start gap-3 mt-6 mb-6 cursor-pointer text-sm text-gray-600 leading-relaxed">
                        <input
                            type="checkbox"
                            x-model="agreeTerms"
                            class="w-5 h-5 mt-0.5 text-orange-500 border-2 border-gray-300 rounded-lg cursor-pointer focus:ring-orange-400"
                        >
                        <span>
                            Saya setuju dengan
                            <button type="button" @click.prevent="TermsModal.show()" class="text-orange-500 font-semibold hover:underline">Syarat & Ketentuan</button>
                            dan
                            <button type="button" @click.prevent="PrivacyModal.show()" class="text-orange-500 font-semibold hover:underline">Kebijakan Privasi</button>
                            Savora
                        </span>
                    </label>

                    <!-- Register Navigation -->
                    <div class="mt-6 px-5 py-4 bg-gray-50 rounded-3xl border border-gray-200 text-center">
                        <p class="text-gray-600 text-sm">
                            Belum punya akun? <a href="{{ route('app.register') }}" class="text-orange-500 font-semibold hover:underline">Daftar di sini</a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 text-center border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    Masuk untuk menikmati resep dan rekomendasi terbaik.
                </p>
            </div>
        </div>

        <!-- Decoration -->
        <div class="text-center text-white text-sm mt-6 opacity-75">
            © 2026 Savora
        </div>
    </div>

    @include('components.privacy-modal')
    @include('components.terms-modal')
</body>
</html>