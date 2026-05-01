<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Settings — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    @php
        $activeProvider  = ($settings['is_active_provider'] ?? 'groq') === 'groq' ? 'default' : ($settings['is_active_provider'] ?? 'default');
        $hasOpenRouterKey = !empty($settings['openrouter_api_key']) && $settings['openrouter_api_key'] !== '***SAVED***' ? false : (!empty($settings['openrouter_api_key']));
        $savedModel      = $settings['openrouter_model'] ?? '';
    @endphp

    <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10"
         x-data="{ provider: '{{ $activeProvider }}', showKey: false, hasKey: {{ $hasOpenRouterKey ? 'true' : 'false' }}, testResult: '', testOk: false, testing: false }">

        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('app.ai') }}" class="p-2.5 bg-white rounded-xl shadow-sm border border-gray-200 text-gray-600 hover:text-[#E76F51] transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI Settings</h1>
                <p class="text-gray-500 text-sm">Konfigurasi provider AI</p>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        <form action="{{ route('app.ai.settings.save') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Provider selector --}}
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    Provider AI
                </h2>

                {{-- Default (Groq) --}}
                <label class="block mb-3 cursor-pointer">
                    <div class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all"
                         :class="provider === 'default' ? 'border-[#2A9D8F] bg-teal-50' : 'border-gray-200 bg-gray-50'">
                        <input type="radio" name="is_active_provider" value="groq" class="hidden"
                               @change="provider = 'default'" :checked="provider === 'default'">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="provider === 'default' ? 'bg-[#2A9D8F]' : 'bg-gray-200'">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-gray-900">Default (Groq)</p>
                            <p class="text-xs text-gray-500">Gratis — API key dikelola server</p>
                        </div>
                        <div x-show="provider === 'default'" class="w-5 h-5 rounded-full bg-[#2A9D8F] flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </label>

                {{-- OpenRouter --}}
                <label class="block cursor-pointer">
                    <div class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all"
                         :class="provider === 'openrouter' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 bg-gray-50'">
                        <input type="radio" name="is_active_provider" value="openrouter" class="hidden"
                               @change="provider = 'openrouter'">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="provider === 'openrouter' ? 'bg-purple-500' : 'bg-gray-200'">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-gray-900">OpenRouter</p>
                            <p class="text-xs text-gray-500">API key & model konfigurasi sendiri</p>
                        </div>
                        <div x-show="provider === 'openrouter'" class="w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </label>
            </div>

            {{-- Default info --}}
            <div x-show="provider === 'default'" class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <div class="w-1 h-5 bg-[#2A9D8F] rounded-full"></div>
                    Konfigurasi Default
                </h2>
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold">Chat Teks</p>
                            <p class="text-sm font-medium text-gray-800">Groq — llama-3.3-70b-versatile</p>
                        </div>
                    </div>
                    <div class="p-3 bg-teal-50 rounded-xl border border-teal-200 text-sm text-teal-800">
                        ✅ Semua API key dikelola server — tidak perlu konfigurasi apapun.
                    </div>
                </div>
            </div>

            {{-- OpenRouter settings --}}
            <div x-show="provider === 'openrouter'" class="space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-800">
                    ⚠️ Model berbayar memerlukan saldo di akun openrouter.ai. Model berlabel :free bisa digunakan gratis.
                </div>

                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                        API Key
                    </h2>
                    @if($hasOpenRouterKey)
                        <div class="flex items-center gap-3 p-3 bg-green-50 rounded-xl border border-green-200 mb-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-semibold text-green-800 flex-1">API key tersimpan di server</span>
                            <button type="button" @click="hasKey = false" class="text-xs text-green-700 font-bold hover:underline">Ganti</button>
                        </div>
                    @endif
                    <div x-show="!hasKey">
                        <div class="relative">
                            <input :type="showKey ? 'text' : 'password'" name="openrouter_api_key"
                                   placeholder="sk-or-v1-..."
                                   class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-purple-400 text-sm pr-12">
                            <button type="button" @click="showKey = !showKey"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path x-show="!showKey" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    <path x-show="showKey" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                        Model Name
                    </h2>
                    <input type="text" name="openrouter_model" value="{{ $savedModel }}"
                           placeholder="meta-llama/llama-3.3-70b-instruct:free"
                           class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-purple-400 text-sm mb-3">
                    <p class="text-xs text-gray-500 mb-2 font-semibold">Contoh model gratis:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['meta-llama/llama-3.3-70b-instruct:free', 'deepseek/deepseek-chat:free', 'google/gemma-3-27b-it:free', 'mistralai/mistral-7b-instruct:free'] as $model)
                            <button type="button"
                                    onclick="document.querySelector('[name=openrouter_model]').value = '{{ $model }}'"
                                    class="px-2.5 py-1 bg-green-50 border border-green-200 text-green-800 text-xs font-medium rounded-lg hover:bg-green-100 transition-colors">
                                {{ $model }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <button type="submit"
                    class="w-full py-4 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all text-base">
                Simpan Settings
            </button>
        </form>

    </div>
</body>
</html>
