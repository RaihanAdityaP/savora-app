@php
    $typeLabels = [
        'murid' => 'Murid',
        'guru' => 'Guru',
        'tamu_undangan' => 'Tamu Undangan',
    ];
    $majorLabels = [
        'pplg' => 'PPLG',
        'tjkt' => 'TJKT',
        'dkv' => 'DKV',
        'lk' => 'LK',
        'ps' => 'PS',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kehadiran - Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light dark;
            --page-bg: #f7f8f6;
            --panel-bg: #ffffff;
            --panel-border: #e2e8f0;
            --text-main: #0f172a;
            --text-soft: #475569;
            --text-muted: #64748b;
            --field-bg: #ffffff;
            --field-border: #cbd5e1;
            --accent: #e76f51;
            --accent-soft: #fff2ec;
            --accent-text: #9f341d;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --page-bg: #0f1413;
                --panel-bg: #171f1d;
                --panel-border: #2b3734;
                --text-main: #f8fafc;
                --text-soft: #cbd5e1;
                --text-muted: #94a3b8;
                --field-bg: #111816;
                --field-border: #33413d;
                --accent-soft: #3a211b;
                --accent-text: #ffd1c4;
            }
        }

        html[data-theme="light"] {
            color-scheme: light;
            --page-bg: #f7f8f6;
            --panel-bg: #ffffff;
            --panel-border: #e2e8f0;
            --text-main: #0f172a;
            --text-soft: #475569;
            --text-muted: #64748b;
            --field-bg: #ffffff;
            --field-border: #cbd5e1;
            --accent-soft: #fff2ec;
            --accent-text: #9f341d;
        }

        html[data-theme="dark"] {
            color-scheme: dark;
            --page-bg: #0f1413;
            --panel-bg: #171f1d;
            --panel-border: #2b3734;
            --text-main: #f8fafc;
            --text-soft: #cbd5e1;
            --text-muted: #94a3b8;
            --field-bg: #111816;
            --field-border: #33413d;
            --accent-soft: #3a211b;
            --accent-text: #ffd1c4;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
        }

        [x-cloak] { display: none !important; }
        .attendance-panel { background: var(--panel-bg); border-color: var(--panel-border); }
        .attendance-main { color: var(--text-main); }
        .attendance-soft { color: var(--text-soft); }
        .attendance-muted { color: var(--text-muted); }
        .attendance-input { background: var(--field-bg); border-color: var(--field-border); color: var(--text-main); }
        .attendance-input::placeholder { color: var(--text-muted); }
        input[type="radio"]:checked + span { border-color: var(--accent); background: var(--accent-soft); color: var(--accent-text); }
        .settings-backdrop { background: rgba(0, 0, 0, .55); backdrop-filter: blur(8px); }
    </style>
</head>
<body class="min-h-screen">
    <button
        type="button"
        onclick="openSettings()"
        class="fixed right-4 top-4 z-20 inline-flex h-11 w-11 items-center justify-center rounded-full border shadow-sm transition hover:scale-105"
        style="background: var(--panel-bg); border-color: var(--panel-border); color: var(--text-main)"
        aria-label="Settings"
    >
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 2v3"></path>
            <path d="M12 19v3"></path>
            <path d="M4.93 4.93l2.12 2.12"></path>
            <path d="M16.95 16.95l2.12 2.12"></path>
            <path d="M2 12h3"></path>
            <path d="M19 12h3"></path>
            <path d="M4.93 19.07l2.12-2.12"></path>
            <path d="M16.95 7.05l2.12-2.12"></path>
        </svg>
    </button>

    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-4 py-10">
        <section class="attendance-panel w-full rounded-lg border p-5 shadow-sm">
            <div class="mb-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-[#e76f51]">Savora</p>
                <h1 class="attendance-main mt-2 text-3xl font-extrabold tracking-tight" data-i18n="title">Form Kehadiran</h1>
                <p class="attendance-soft mt-2 text-sm leading-6" data-i18n="subtitle">Isi data kehadiran dengan benar. Data presensi hanya bisa dilihat admin.</p>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="attendanceForm" action="{{ route('attendance.store') }}" method="POST" class="space-y-5" autocomplete="off" x-data="{ type: '{{ old('attendee_type', 'murid') }}' }">
                @csrf
                <div>
                    <label for="name" class="attendance-main mb-2 block text-sm font-semibold" data-i18n="name">Nama</label>
                    <input id="name" name="name" type="text" value="{{ session('success') ? '' : old('name') }}" maxlength="120" autocomplete="off" class="attendance-input w-full rounded-md border px-4 py-3 text-base outline-none transition focus:border-[#e76f51] focus:ring-4 focus:ring-[#e76f51]/10" placeholder="Masukkan nama" data-i18n-placeholder="namePlaceholder" required>
                </div>

                <div>
                    <label for="contact_number" class="attendance-main mb-2 block text-sm font-semibold" data-i18n="contactNumber">Nomor</label>
                    <input id="contact_number" name="contact_number" type="tel" value="{{ session('success') ? '' : old('contact_number') }}" maxlength="30" inputmode="numeric" pattern="[0-9]+" autocomplete="off" class="attendance-input w-full rounded-md border px-4 py-3 text-base outline-none transition focus:border-[#e76f51] focus:ring-4 focus:ring-[#e76f51]/10" placeholder="Masukkan nomor" data-i18n-placeholder="contactNumberPlaceholder" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>

                <div>
                    <p class="attendance-main mb-2 text-sm font-semibold" data-i18n="from">Asal dari</p>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($attendeeTypes as $type)
                            <label class="cursor-pointer">
                                <input type="radio" name="attendee_type" value="{{ $type }}" class="sr-only" x-model="type" required>
                                <span class="attendance-soft block rounded-md border px-3 py-3 text-center text-sm font-bold transition" style="border-color: var(--field-border)" data-i18n="type_{{ $type }}">{{ $typeLabels[$type] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="type === 'murid'" x-cloak>
                    <p class="attendance-main mb-2 text-sm font-semibold" data-i18n="major">Jurusan murid</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach ($majors as $major)
                            <label class="cursor-pointer">
                                <input type="radio" name="major" value="{{ $major }}" class="sr-only" @checked(!session('success') && old('major') === $major)>
                                <span class="attendance-soft block rounded-md border px-3 py-3 text-center text-sm font-bold transition" style="border-color: var(--field-border)">{{ $majorLabels[$major] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="impression" class="attendance-main mb-2 block text-sm font-semibold" data-i18n="impression">Kesan saat mengunjungi stand Savora</label>
                    <textarea id="impression" name="impression" maxlength="1000" rows="3" autocomplete="off" class="attendance-input w-full resize-y rounded-md border px-4 py-3 text-base outline-none transition focus:border-[#e76f51] focus:ring-4 focus:ring-[#e76f51]/10" placeholder="Tulis kesanmu" data-i18n-placeholder="impressionPlaceholder" required>{{ session('success') ? '' : old('impression') }}</textarea>
                </div>

                <div>
                    <label for="feedback" class="attendance-main mb-2 block text-sm font-semibold" data-i18n="feedback">Saran atau kritik</label>
                    <textarea id="feedback" name="feedback" maxlength="1000" rows="3" autocomplete="off" class="attendance-input w-full resize-y rounded-md border px-4 py-3 text-base outline-none transition focus:border-[#e76f51] focus:ring-4 focus:ring-[#e76f51]/10" placeholder="Opsional" data-i18n-placeholder="feedbackPlaceholder">{{ session('success') ? '' : old('feedback') }}</textarea>
                </div>

                <button type="submit" class="w-full rounded-md bg-[#e76f51] px-5 py-3 text-base font-extrabold text-white shadow-sm transition hover:bg-[#d95f43] focus:outline-none focus:ring-4 focus:ring-[#e76f51]/25" data-i18n="submit">
                    Simpan Kehadiran
                </button>
            </form>
        </section>
    </main>

    <div id="settingsModal" class="settings-backdrop fixed inset-0 z-30 hidden items-center justify-center px-4">
        <section class="attendance-panel w-full max-w-sm rounded-lg border p-5 shadow-xl">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="attendance-main text-lg font-extrabold" data-i18n="settings">Settings</h2>
                    <p class="attendance-muted mt-1 text-sm" data-i18n="settingsDesc">Preferensi ini tersimpan di browser ini.</p>
                </div>
                <button type="button" onclick="closeSettings()" class="attendance-muted text-2xl leading-none">&times;</button>
            </div>

            <div class="space-y-5">
                <div>
                    <p class="attendance-main mb-2 text-sm font-semibold" data-i18n="theme">Tema</p>
                    <select id="themeSelect" class="attendance-input w-full rounded-md border px-3 py-3">
                        <option value="system" data-i18n="themeSystem">Ikuti perangkat</option>
                        <option value="light" data-i18n="themeLight">Terang</option>
                        <option value="dark" data-i18n="themeDark">Gelap</option>
                    </select>
                </div>
                <div>
                    <p class="attendance-main mb-2 text-sm font-semibold" data-i18n="language">Bahasa</p>
                    <select id="languageSelect" class="attendance-input w-full rounded-md border px-3 py-3">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>
        </section>
    </div>

    <script>
        const translations = {
            id: {
                title: 'Form Kehadiran',
                subtitle: 'Isi data kehadiran dengan benar. Data presensi hanya bisa dilihat admin.',
                name: 'Nama',
                namePlaceholder: 'Masukkan nama',
                contactNumber: 'Nomor',
                contactNumberPlaceholder: 'Masukkan nomor',
                from: 'Asal dari',
                type_murid: 'Murid',
                type_guru: 'Guru',
                type_tamu_undangan: 'Tamu Undangan',
                major: 'Jurusan murid',
                impression: 'Kesan saat mengunjungi stand Savora',
                impressionPlaceholder: 'Tulis kesanmu',
                feedback: 'Saran atau kritik',
                feedbackPlaceholder: 'Opsional',
                submit: 'Simpan Kehadiran',
                settings: 'Settings',
                settingsDesc: 'Preferensi ini tersimpan di browser ini.',
                theme: 'Tema',
                themeSystem: 'Ikuti perangkat',
                themeLight: 'Terang',
                themeDark: 'Gelap',
                language: 'Bahasa',
            },
            en: {
                title: 'Attendance Form',
                subtitle: 'Submit your attendance details. Attendance records are visible to admins only.',
                name: 'Name',
                namePlaceholder: 'Enter your name',
                contactNumber: 'Number',
                contactNumberPlaceholder: 'Enter your number',
                from: 'From',
                type_murid: 'Student',
                type_guru: 'Teacher',
                type_tamu_undangan: 'Guest',
                major: 'Student major',
                impression: 'Impression after visiting the Savora stand',
                impressionPlaceholder: 'Write your impression',
                feedback: 'Suggestion or criticism',
                feedbackPlaceholder: 'Optional',
                submit: 'Submit Attendance',
                settings: 'Settings',
                settingsDesc: 'These preferences are saved in this browser.',
                theme: 'Theme',
                themeSystem: 'Use device setting',
                themeLight: 'Light',
                themeDark: 'Dark',
                language: 'Language',
            },
        };

        function applyTheme(theme) {
            document.documentElement.dataset.theme = theme === 'system' ? '' : theme;
            localStorage.setItem('attendance_theme', theme);
        }

        function applyLanguage(language) {
            const dict = translations[language] || translations.id;
            document.documentElement.lang = language;
            document.querySelectorAll('[data-i18n]').forEach((node) => {
                const key = node.dataset.i18n;
                if (dict[key]) node.textContent = dict[key];
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach((node) => {
                const key = node.dataset.i18nPlaceholder;
                if (dict[key]) node.placeholder = dict[key];
            });
            localStorage.setItem('attendance_language', language);
        }

        function openSettings() {
            document.getElementById('settingsModal').classList.remove('hidden');
            document.getElementById('settingsModal').classList.add('flex');
        }

        function closeSettings() {
            document.getElementById('settingsModal').classList.add('hidden');
            document.getElementById('settingsModal').classList.remove('flex');
        }

        const themeSelect = document.getElementById('themeSelect');
        const languageSelect = document.getElementById('languageSelect');
        themeSelect.value = localStorage.getItem('attendance_theme') || 'system';
        languageSelect.value = localStorage.getItem('attendance_language') || 'id';
        applyTheme(themeSelect.value);
        applyLanguage(languageSelect.value);
        themeSelect.addEventListener('change', (event) => applyTheme(event.target.value));
        languageSelect.addEventListener('change', (event) => applyLanguage(event.target.value));
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
