@php
    $isEnglish = ($userSettings['language'] ?? session('user_language', 'en')) === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Settings' : 'Pengaturan' }} - Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2 { font-family: 'Poppins', sans-serif; }
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
                    <h1 class="text-xl font-bold">{{ $isEnglish ? 'Settings' : 'Pengaturan' }}</h1>
                    <p class="text-white/80 text-xs">{{ $isEnglish ? 'Customize your Savora experience' : 'Atur pengalaman Savora kamu' }}</p>
                </div>
            </div>
        </div>

        @if(session('status') || session('error'))
            <div class="mb-4">
                <x-app-theme.info-banner
                    message="{{ session('status') ?? session('error') }}"
                    icon="{{ session('error') ? 'bi bi-exclamation-circle' : 'bi bi-check-circle' }}" />
            </div>
        @endif

        <form method="POST" action="{{ route('app.settings.save') }}" @submit.prevent="handleSubmit" x-data="settingsForm()">
            @csrf

            {{-- Display & Appearance --}}
            <div class="card-savora p-5 mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Display & Appearance' : 'Tampilan'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>' />
                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Theme' : 'Tema' }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center cursor-pointer px-4 py-3 rounded-xl border-2 transition-all"
                                   :class="theme === 'dark' ? 'bg-orange-50' : 'bg-white'"
                                   :style="theme === 'dark' ? 'border-color: var(--color-primary-coral); color: var(--color-primary-coral);' : 'border-color: rgba(107,114,128,.22); color: var(--color-text-primary);'">
                                <input type="radio" name="theme" value="dark" x-model="theme" class="hidden">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 015.646 5.646 9.001 9.001 0 0020.354 15.354z"/>
                                </svg>
                                <span class="text-sm font-medium">{{ $isEnglish ? 'Dark Mode' : 'Mode Gelap' }}</span>
                            </label>
                            <label class="flex items-center cursor-pointer px-4 py-3 rounded-xl border-2 transition-all"
                                   :class="theme === 'light' ? 'bg-orange-50' : 'bg-white'"
                                   :style="theme === 'light' ? 'border-color: var(--color-primary-coral); color: var(--color-primary-coral);' : 'border-color: rgba(107,114,128,.22); color: var(--color-text-primary);'">
                                <input type="radio" name="theme" value="light" x-model="theme" class="hidden">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="text-sm font-medium">{{ $isEnglish ? 'Light Mode' : 'Mode Terang' }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Language' : 'Bahasa' }}</label>
                        <select name="language" x-model="language" class="input-savora">
                            <option value="en">English</option>
                            <option value="id">Bahasa Indonesia</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Font Size' : 'Ukuran Font' }}</label>
                        <div class="flex items-center gap-4">
                            <input type="range" name="font_size" x-model="fontSize" min="12" max="18" step="1" class="flex-1">
                            <span class="text-sm font-semibold w-12 text-center" x-text="fontSize + 'px'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <div class="card-savora p-5 mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Notifications' : 'Notifikasi'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' />
                <div class="space-y-3 mt-4">
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="notify_likes" x-model="settings.notify_likes" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Notify when someone likes my recipe' : 'Beri tahu saat seseorang menyukai resep saya' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="notify_comments" x-model="settings.notify_comments" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Notify when someone comments on my recipe' : 'Beri tahu saat seseorang mengomentari resep saya' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="notify_follows" x-model="settings.notify_follows" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Notify when someone follows me' : 'Beri tahu saat seseorang mengikuti saya' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="notify_email" x-model="settings.notify_email" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Send me email notifications' : 'Kirim notifikasi lewat email' }}</span>
                    </label>
                </div>
            </div>

            {{-- Privacy & Data --}}
            <div class="card-savora p-5 mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Privacy & Data' : 'Privasi & Data'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>' />
                <div class="space-y-3 mt-4">
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="allow_analytics" x-model="settings.allow_analytics" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Allow usage analytics to improve Savora' : 'Izinkan analitik penggunaan untuk meningkatkan Savora' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="profile_public" x-model="settings.profile_public" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Make my profile public' : 'Jadikan profil saya publik' }}</span>
                    </label>
                </div>
            </div>

            {{-- Preferences --}}
            <div class="card-savora p-5 mb-5">
                <x-app-theme.section-header :title="$isEnglish ? 'Other Preferences' : 'Preferensi Lain'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>' />
                <div class="space-y-3 mt-4">
                    <label class="flex items-center cursor-pointer p-3 rounded-lg hover:bg-white/5 transition-colors">
                        <input type="checkbox" name="auto_save_drafts" x-model="settings.auto_save_drafts" class="w-4 h-4 cursor-pointer">
                        <span class="ml-3 text-sm font-medium">{{ $isEnglish ? 'Auto-save recipe drafts' : 'Simpan draft resep otomatis' }}</span>
                    </label>
                </div>
            </div>

            <button type="submit" :disabled="isSubmitting" class="btn-primary-savora w-full py-4 rounded-2xl">
                <span x-show="!isSubmitting">{{ $isEnglish ? 'Save Settings' : 'Simpan Pengaturan' }}</span>
                <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    Saving...
                </span>
            </button>
        </form>
    </div>

    <script>
    function settingsForm() {
        return {
            theme: @js(old('theme', $userSettings['theme'] ?? 'light')),
            language: @js(old('language', $userSettings['language'] ?? 'en')),
            fontSize: {{ (int) old('font_size', $userSettings['font_size'] ?? 14) }},
            isSubmitting: false,
            settings: {
                notify_likes: {{ old('notify_likes', $userSettings['notify_likes'] ?? true) ? 'true' : 'false' }},
                notify_comments: {{ old('notify_comments', $userSettings['notify_comments'] ?? true) ? 'true' : 'false' }},
                notify_follows: {{ old('notify_follows', $userSettings['notify_follows'] ?? true) ? 'true' : 'false' }},
                notify_email: {{ old('notify_email', $userSettings['notify_email'] ?? false) ? 'true' : 'false' }},
                allow_analytics: {{ old('allow_analytics', $userSettings['allow_analytics'] ?? true) ? 'true' : 'false' }},
                profile_public: {{ old('profile_public', $userSettings['profile_public'] ?? true) ? 'true' : 'false' }},
                auto_save_drafts: {{ old('auto_save_drafts', $userSettings['auto_save_drafts'] ?? true) ? 'true' : 'false' }},
            },
            handleSubmit(e) {
                this.isSubmitting = true;
                e.target.submit();
            }
        }
    }
    </script>
</body>
</html>