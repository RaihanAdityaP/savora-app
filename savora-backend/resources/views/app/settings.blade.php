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
        input[type="checkbox"] { accent-color: var(--color-primary-coral); }
        .settings-row {
            background: color-mix(in srgb, var(--color-card-bg) 82%, transparent);
            border: 1px solid var(--color-separator);
            border-radius: 12px;
        }
        html[data-theme="dark"] .settings-row {
            background: rgba(255,255,255,.02);
            border-color: rgba(255,255,255,.06);
        }
        .settings-choice {
            background: color-mix(in srgb, var(--color-text-primary) 6%, transparent);
            border: 2px solid transparent;
            color: var(--color-text-secondary);
        }
        .settings-choice-active {
            background: rgba(231,111,81,.16);
            border-color: var(--color-primary-coral);
            color: var(--color-primary-coral);
        }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('app.home') }}"
               class="p-2.5 rounded-xl shadow-sm border transition-all"
               style="background: var(--color-card-bg); border-color: var(--color-separator); color: var(--color-text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ $isEnglish ? 'Settings' : 'Pengaturan' }}</h1>
                <p class="text-sm" style="color: var(--color-text-secondary)">{{ $isEnglish ? 'Customize your Savora experience' : 'Atur pengalaman Savora kamu' }}</p>
            </div>
        </div>

        @if(session('status') || session('error'))
            <div class="mb-4">
                <x-app-theme.info-banner
                    message="{{ session('status') ?? session('error') }}"
                    icon="{{ session('error') ? 'bi bi-exclamation-circle' : 'bi bi-check-circle' }}" />
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 rounded-2xl text-sm font-medium" style="background: rgba(231,111,81,.12); color: var(--color-primary-coral); border: 1px solid rgba(231,111,81,.25);">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('app.settings.save') }}" x-data="settingsForm()" x-on:submit="isSubmitting = true">
            @csrf

            <div class="mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Display & Appearance' : 'Tampilan'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>' />
                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Theme' : 'Tema' }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="settings-choice flex items-center justify-center cursor-pointer px-4 py-3 rounded-xl transition-all"
                                   :class="theme === 'dark' ? 'settings-choice-active' : ''">
                                <input type="radio" name="theme" value="dark" x-model="theme" class="hidden">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 015.646 5.646 9.001 9.001 0 0020.354 15.354z"/>
                                </svg>
                                <span class="text-sm font-semibold">{{ $isEnglish ? 'Dark' : 'Gelap' }}</span>
                            </label>
                            <label class="settings-choice flex items-center justify-center cursor-pointer px-4 py-3 rounded-xl transition-all"
                                   :class="theme === 'light' ? 'settings-choice-active' : ''">
                                <input type="radio" name="theme" value="light" x-model="theme" class="hidden">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="text-sm font-semibold">{{ $isEnglish ? 'Light' : 'Terang' }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Language' : 'Bahasa' }}</label>
                        <select name="language" x-model="language" class="input-savora settings-row">
                            <option value="en">English</option>
                            <option value="id">Bahasa Indonesia</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Font Size' : 'Ukuran Font' }}</label>
                        <div class="flex items-center gap-4">
                            <input type="range" name="font_size" x-model="fontSize" min="12" max="18" step="1" class="range-savora flex-1"
                                   :style="`--range-progress: ${((fontSize - 12) / 6) * 100}%`">
                            <span class="settings-row text-sm font-semibold w-12 text-center py-1.5" style="color: var(--color-text-primary);" x-text="fontSize + 'px'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Notifications' : 'Notifikasi'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' />
                <p class="mt-3 text-sm" style="color: var(--color-text-secondary);">
                    {{ $isEnglish
                        ? 'Turn off automatic alerts you do not want. Admin announcements will still be delivered.'
                        : 'Nonaktifkan notifikasi otomatis yang tidak Anda inginkan. Pengumuman admin tetap akan dikirim.' }}
                </p>
                <div class="space-y-3 mt-4">
                    @foreach([
                        'notify_likes' => $isEnglish ? 'Notify when someone likes my recipe' : 'Beri tahu saat seseorang menyukai resep saya',
                        'notify_comments' => $isEnglish ? 'Notify when someone comments on my recipe' : 'Beri tahu saat seseorang mengomentari resep saya',
                        'notify_follows' => $isEnglish ? 'Notify when someone follows me' : 'Beri tahu saat seseorang mengikuti saya',
                    ] as $key => $label)
                        <label class="settings-row flex items-center justify-between gap-4 cursor-pointer px-3 py-3 transition-colors" style="color: var(--color-text-primary);">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <span class="text-sm font-semibold">{{ $label }}</span>
                            <input type="checkbox" name="{{ $key }}" value="1" x-model="settings.{{ $key }}" class="w-4 h-4 cursor-pointer">
                        </label>
                    @endforeach
                    <div class="settings-row flex items-start justify-between gap-4 px-3 py-3 opacity-80" style="color: var(--color-text-primary);">
                        <div>
                            <div class="text-sm font-medium">{{ $isEnglish ? 'Admin announcements' : 'Pengumuman admin' }}</div>
                            <div class="text-xs mt-1" style="color: var(--color-text-secondary);">
                                {{ $isEnglish ? 'Always active for important Savora updates.' : 'Selalu aktif untuk pembaruan penting Savora.' }}
                            </div>
                        </div>
                        <svg class="w-5 h-5 flex-shrink-0" style="color: var(--color-text-secondary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Privacy & Data' : 'Privasi & Data'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>' />
                <div class="space-y-3 mt-4">
                    @foreach([
                        'allow_analytics' => $isEnglish ? 'Allow usage analytics to improve Savora' : 'Izinkan analitik penggunaan untuk meningkatkan Savora',
                        'profile_public' => $isEnglish ? 'Make my profile public' : 'Jadikan profil saya publik',
                    ] as $key => $label)
                        <label class="settings-row flex items-center justify-between gap-4 cursor-pointer px-3 py-3 transition-colors" style="color: var(--color-text-primary);">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <span class="text-sm font-semibold">{{ $label }}</span>
                            <input type="checkbox" name="{{ $key }}" value="1" x-model="settings.{{ $key }}" class="w-4 h-4 cursor-pointer">
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-5">
                <x-app-theme.section-header :title="$isEnglish ? 'Other Preferences' : 'Preferensi Lain'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>' />
                <div class="space-y-3 mt-4">
                    <label class="settings-row flex items-center justify-between gap-4 cursor-pointer px-3 py-3 transition-colors" style="color: var(--color-text-primary);">
                        <input type="hidden" name="auto_save_drafts" value="0">
                        <span class="text-sm font-semibold">{{ $isEnglish ? 'Auto-save recipe drafts' : 'Simpan draft resep otomatis' }}</span>
                        <input type="checkbox" name="auto_save_drafts" value="1" x-model="settings.auto_save_drafts" class="w-4 h-4 cursor-pointer">
                    </label>
                </div>
            </div>

            <button type="submit" :disabled="isSubmitting" class="btn-primary-savora w-full py-4 rounded-2xl disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!isSubmitting">{{ $isEnglish ? 'Save Settings' : 'Simpan Pengaturan' }}</span>
                <span x-show="isSubmitting" class="flex items-center justify-center gap-2" x-cloak>
                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    {{ $isEnglish ? 'Saving...' : 'Menyimpan...' }}
                </span>
            </button>
        </form>
    </div>

    <script>
    function settingsForm() {
        return {
            theme: @js(old('theme', $userSettings['theme'] ?? 'light')),
            language: @js(old('language', $userSettings['language'] ?? 'en')),
            fontSize: @js((int) old('font_size', $userSettings['font_size'] ?? 14)),
            isSubmitting: false,
            settings: {
                notify_likes: @js((bool) old('notify_likes', $userSettings['notify_likes'] ?? true)),
                notify_comments: @js((bool) old('notify_comments', $userSettings['notify_comments'] ?? true)),
                notify_follows: @js((bool) old('notify_follows', $userSettings['notify_follows'] ?? true)),
                allow_analytics: @js((bool) old('allow_analytics', $userSettings['allow_analytics'] ?? true)),
                profile_public: @js((bool) old('profile_public', $userSettings['profile_public'] ?? true)),
                auto_save_drafts: @js((bool) old('auto_save_drafts', $userSettings['auto_save_drafts'] ?? true)),
            },
        }
    }
    </script>

</body>
</html>
