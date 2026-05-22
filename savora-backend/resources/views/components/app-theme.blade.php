{{--
    AppTheme Component
    Usage: @include('components.app-theme') — include once in your layout (e.g. inside <head>)

    Exposes:
      - CSS custom properties (--color-*, --gradient-*)
      - Utility classes (.btn-primary, .card-savora, .input-savora, .tag-chip, etc.)
      - Blade slots for section-header, empty-state, info-banner

    For section header  : <x-app-theme.section-header title="..." icon="..." />
    For empty state     : <x-app-theme.empty-state icon="..." title="..." subtitle="..." />
    For info banner     : <x-app-theme.info-banner message="..." />
--}}

@php
    $savoraSettingsEnabled = session()->has('user_id')
        && ! request()->routeIs('app.login', 'app.login.post', 'app.register', 'app.register.post', 'app.logout');

    $savoraTheme = $savoraSettingsEnabled ? session('user_theme', 'light') : 'light';
    $savoraLanguage = $savoraSettingsEnabled ? session('user_language', 'en') : 'en';
    $savoraFontSize = $savoraSettingsEnabled ? (int) session('user_font_size', 14) : 14;
    $savoraFontSize = max(12, min(18, $savoraFontSize));
    $savoraFontScale = round($savoraFontSize / 14, 4);
    $savoraUserSettings = session('user_settings', []);
    $savoraAutoSaveDrafts = (bool) ($savoraUserSettings['auto_save_drafts'] ?? true);
@endphp

<style>
    /* =========================================================
       CSS CUSTOM PROPERTIES  (mirrors AppTheme constants)
       ========================================================= */
    :root {
        /* Primary Colors */
        --color-primary-dark:     #264653;
        --color-primary-teal:     #2A9D8F;
        --color-primary-yellow:   #E9C46A;
        --color-primary-orange:   #F4A261;
        --color-primary-coral:    #E76F51;

        /* Background / Surface */
        --color-bg-light:         #F5F7FA;
        --color-card-bg:          #ffffff;
        --color-card-border:      rgba(231,111,81,0.18);
        --color-chip-bg:          rgba(231,111,81,0.08);
        --color-separator:        rgba(231,111,81,0.12);

        /* Text */
        --color-text-primary:     #264653;
        --color-text-secondary:   #6B7280;
        --color-text-muted:       #9CA3AF;
        --color-on-accent:        #ffffff;

        /* Extended — Modal / Tag / Badge */
        --color-privacy-green:    #2A9D8F;
        --color-privacy-dark:     #264653;
        --color-privacy-deep:     #1a5c54;
        --color-terms-red:        #E76F51;
        --color-terms-amber:      #F4A261;
        --color-terms-yellow:     #E9C46A;
        --color-tag-border:       #E9C46A;
        --color-recipe-cat-dark:  #264653;
        --color-recipe-cat-teal:  #2A9D8F;
        --color-logo-blue:        #2B6CB0;
        --color-logo-orange:      #FF6B35;
        --color-badge-red:        #FF3B30;
        --color-badge-red-light:  #FF6B6B;

        /* Proxy / Warning */
        --color-proxy-orange:     #F97316;
        --color-proxy-purple:     #9333EA;

        /* Gradients (as background shorthand) */
        --gradient-primary:
            linear-gradient(135deg, #264653, #2A9D8F, #E9C46A, #F4A261, #E76F51);
        --gradient-accent:
            linear-gradient(90deg, #E76F51, #F4A261);
        --gradient-teal:
            linear-gradient(90deg, #2A9D8F, #3DB9A9);
        --gradient-orange:
            linear-gradient(90deg, #F4A261, #E9C46A);
        --gradient-logo:
            linear-gradient(90deg, #2B6CB0, #FF6B35);
        --gradient-privacy:
            linear-gradient(135deg, #2A9D8F, #264653, #1a5c54);
        --gradient-terms:
            linear-gradient(135deg, #E76F51, #F4A261, #E9C46A);
        --gradient-badge:
            linear-gradient(90deg, #FF3B30, #FF6B6B);
        --gradient-category:
            linear-gradient(90deg, #264653, #2A9D8F);
        --gradient-admin:
            linear-gradient(90deg, #FFD700, #FFA500);
        --gradient-premium:
            linear-gradient(90deg, #6C63FF, #9F8FFF);
        --gradient-card:
            linear-gradient(135deg, rgba(231,111,81,0.04), rgba(244,162,97,0.08));
        --gradient-input:
            linear-gradient(90deg, rgba(231,111,81,0.04), rgba(244,162,97,0.07));

        /* Shadows */
        --shadow-primary:  0 10px 20px rgba(231,111,81,0.30);
        --shadow-card:     0  2px 12px rgba(231,111,81,0.10), 0 1px 3px rgba(0,0,0,0.04);
        --shadow-button:   0  8px 15px rgba(231,111,81,0.40);
        --shadow-logo:     0  2px  8px rgba(43, 108,176,0.30);
        --shadow-badge:    0  0    8px 1px rgba(255,59,48,0.50);

        /* Border Radius */
        --radius-xs:   8px;
        --radius-sm:   10px;
        --radius-md:   14px;
        --radius-lg:   16px;
        --radius-xl:   20px;
        --radius-2xl:  24px;
        --radius-full: 9999px;

        /* Typography Scale */
        --text-xs:   13px;
        --text-sm:   14px;
        --text-base: 16px;
        --text-lg:   18px;
        --text-xl:   20px;
        --text-2xl:  26px;
        --app-font-scale: {{ $savoraFontScale }};

        /* Welcome card overlay — light mode: subtle dark overlay for text contrast */
        --welcome-overlay: rgba(0,0,0,0.18);
        --welcome-text-shadow: 0 1px 4px rgba(0,0,0,0.30), 0 0 12px rgba(0,0,0,0.12);
        --welcome-chip-bg: rgba(255,255,255,0.25);
        --welcome-chip-border: rgba(255,255,255,0.40);
        --welcome-quote-bg: rgba(255,255,255,0.20);
        --welcome-quote-border: rgba(255,255,255,0.40);
    }

    @if($savoraTheme === 'dark')
    :root {
        --color-bg-light:         #0f1318;
        --color-card-bg:          #1a2330;
        --color-card-border:      rgba(231,111,81,0.20);
        --color-chip-bg:          rgba(231,111,81,0.10);
        --color-separator:        rgba(255,255,255,0.08);
        --color-text-primary:     #F0F4F8;
        --color-text-secondary:   #A8B8C8;
        --color-text-muted:       #6B7A8D;
        --gradient-card:
            linear-gradient(135deg, rgba(231,111,81,0.08), rgba(42,157,143,0.06));
        --gradient-input:
            linear-gradient(90deg, rgba(255,255,255,0.05), rgba(255,255,255,0.03));
        --shadow-card:  0 2px 16px rgba(0,0,0,0.35), 0 1px 4px rgba(0,0,0,0.20);

        /* Welcome card — dark mode: lighter overlay since background already dark */
        --welcome-overlay: rgba(0,0,0,0.12);
        --welcome-text-shadow: 0 1px 4px rgba(0,0,0,0.50), 0 0 16px rgba(0,0,0,0.25);
        --welcome-chip-bg: rgba(255,255,255,0.25);
        --welcome-chip-border: rgba(255,255,255,0.40);
        --welcome-quote-bg: rgba(255,255,255,0.20);
        --welcome-quote-border: rgba(255,255,255,0.40);
    }
    @endif

    body {
        font-size: {{ $savoraFontSize }}px;
        background: var(--color-bg-light);
        color: var(--color-text-primary);
    }

    .text-\[10px\] { font-size: calc(10px * var(--app-font-scale)) !important; }
    .text-\[11px\] { font-size: calc(11px * var(--app-font-scale)) !important; }
    .text-xs       { font-size: calc(12px * var(--app-font-scale)) !important; }
    .text-sm       { font-size: calc(14px * var(--app-font-scale)) !important; }
    .text-base     { font-size: calc(16px * var(--app-font-scale)) !important; }
    .text-lg       { font-size: calc(18px * var(--app-font-scale)) !important; }
    .text-xl       { font-size: calc(20px * var(--app-font-scale)) !important; }
    .text-2xl      { font-size: calc(24px * var(--app-font-scale)) !important; }
    .text-3xl      { font-size: calc(30px * var(--app-font-scale)) !important; }

    @if($savoraTheme === 'dark')
    /* =========================================================
       DARK MODE GLOBAL OVERRIDES
       ========================================================= */
    body,
    .bg-white,
    .bg-gray-50,
    .bg-gray-100,
    .bg-\[#F5F7FA\] {
        background-color: var(--color-bg-light) !important;
        color: var(--color-text-primary) !important;
    }

    /* Cards & surfaces */
    .card-savora,
    nav.bg-white,
    header.bg-white {
        background-color: var(--color-card-bg) !important;
        border-color: var(--color-card-border) !important;
    }

    /* Recipe cards specifically */
    .recipe-card-surface {
        background-color: var(--color-card-bg) !important;
    }

    /* Text overrides */
    .text-gray-900, .text-gray-800, .text-gray-700 { color: var(--color-text-primary)   !important; }
    .text-gray-600, .text-gray-500                  { color: var(--color-text-secondary) !important; }
    .text-gray-400                                  { color: var(--color-text-muted)     !important; }

    /* Border overrides */
    .border-gray-100, .border-gray-200, .border-gray-300 {
        border-color: var(--color-separator) !important;
    }

    /* Chip/pill bg overrides */
    .chip-surface {
        background: var(--color-chip-bg) !important;
    }

    /* Input */
    .input-savora {
        background: var(--gradient-input) !important;
        border-color: rgba(231,111,81,0.25) !important;
        color: var(--color-text-primary) !important;
    }
    @endif

    /* =========================================================
       TYPOGRAPHY
       ========================================================= */
    .app-heading-large  { font-size: var(--text-2xl); font-weight: 700; color: #ffffff; }
    .app-heading-medium { font-size: var(--text-xl);  font-weight: 700; color: var(--color-text-primary); }
    .app-heading-small  { font-size: var(--text-lg);  font-weight: 700; color: var(--color-text-primary); }
    .app-body-large     { font-size: var(--text-base); font-weight: 500; color: var(--color-text-primary); }
    .app-body-medium    { font-size: var(--text-sm);   color: var(--color-text-primary); }
    .app-body-small     { font-size: var(--text-xs);   color: var(--color-text-secondary); }
    .app-btn-text       { color: #ffffff; font-size: var(--text-base); font-weight: 700; }

    /* =========================================================
       WELCOME CARD — text contrast helpers
       ========================================================= */
    .welcome-text {
        text-shadow: var(--welcome-text-shadow);
        color: #ffffff;
    }
    .welcome-chip {
        background: var(--welcome-chip-bg) !important;
        border-color: var(--welcome-chip-border) !important;
    }
    .welcome-quote {
        background: var(--welcome-quote-bg) !important;
        border-color: var(--welcome-quote-border) !important;
    }
    .welcome-overlay-layer {
        position: absolute;
        inset: 0;
        background: var(--welcome-overlay);
        pointer-events: none;
        border-radius: inherit;
    }

    /* =========================================================
       CARD
       ========================================================= */
    .card-savora {
        background: var(--color-card-bg);
        border-radius: var(--radius-xl);
        border: 1.5px solid var(--color-card-border);
        box-shadow: var(--shadow-card);
    }

    /* =========================================================
       BUTTONS
       ========================================================= */
    .btn-primary-savora {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        background: var(--gradient-accent);
        border: none;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-button);
        color: #ffffff;
        font-size: var(--text-base);
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: opacity .2s, transform .15s;
    }
    .btn-primary-savora:hover  { opacity: .9; transform: translateY(-1px); color: #ffffff; text-decoration: none; }
    .btn-primary-savora:active { transform: translateY(0); }

    .btn-outlined-savora {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        background: rgba(255,255,255,0.20);
        border: 1.5px solid rgba(255,255,255,0.30);
        border-radius: var(--radius-lg);
        color: #ffffff;
        font-size: var(--text-base);
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s;
    }
    .btn-outlined-savora:hover { background: rgba(255,255,255,0.30); color: #ffffff; text-decoration: none; }

    .btn-icon-savora {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--color-card-bg);
        border: 1px solid var(--color-separator);
        color: var(--color-text-primary);
        box-shadow: var(--shadow-card);
        transition: opacity .2s, transform .15s, background .2s;
    }
    .btn-icon-savora:hover {
        background: var(--color-card-bg);
        color: var(--color-text-primary);
        opacity: .92;
    }
    .btn-icon-savora:active { transform: scale(.98); }

    .btn-translate-savora {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: var(--radius-full);
        border: 1px solid var(--color-separator);
        background: var(--color-chip-bg);
        color: var(--color-primary-coral);
        font-size: calc(12px * var(--app-font-scale));
        font-weight: 700;
        cursor: pointer;
        transition: opacity .2s, background .2s;
    }
    .btn-translate-savora:hover {
        opacity: .85;
        background: var(--color-chip-bg);
    }
    .btn-translate-savora[disabled] {
        opacity: .6;
        cursor: wait;
    }

    /* =========================================================
       INPUT
       ========================================================= */
    .input-savora {
        background: var(--gradient-input);
        border: 1.5px solid rgba(231,111,81,0.20);
        border-radius: var(--radius-md);
        padding: 14px;
        font-size: var(--text-sm);
        color: var(--color-text-primary);
        color-scheme: light;
        width: 100%;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .input-savora:focus {
        border-color: var(--color-primary-coral);
        box-shadow: 0 0 0 3px rgba(231,111,81,0.15);
    }
    .input-savora::placeholder { color: var(--color-text-muted); font-size: var(--text-sm); }
    .input-savora option {
        background: var(--color-card-bg);
        color: var(--color-text-primary);
    }

    @if($savoraTheme === 'dark')
    .input-savora {
        color-scheme: dark;
    }
    @endif

    .input-wrapper-savora { position: relative; }
    .input-wrapper-savora .input-icon {
        position: absolute; left: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-muted);
        pointer-events: none; font-size: 18px; line-height: 1;
    }
    .input-wrapper-savora .input-savora.has-icon { padding-left: 42px; }

    .range-savora {
        --range-progress: 0%;
        width: 100%;
        height: 24px;
        cursor: pointer;
        accent-color: var(--color-primary-coral);
        background: transparent;
    }
    .range-savora:focus {
        outline: none;
    }
    .range-savora::-webkit-slider-runnable-track {
        height: 8px;
        border-radius: var(--radius-full);
        background: linear-gradient(
            90deg,
            var(--color-primary-coral) 0%,
            var(--color-primary-coral) var(--range-progress),
            var(--color-separator) var(--range-progress),
            var(--color-separator) 100%
        );
    }
    .range-savora::-webkit-slider-thumb {
        appearance: none;
        width: 20px;
        height: 20px;
        margin-top: -6px;
        border-radius: var(--radius-full);
        background: var(--color-primary-coral);
        border: 0;
        box-shadow: var(--shadow-card);
    }
    .range-savora::-moz-range-track {
        height: 8px;
        border-radius: var(--radius-full);
        background: var(--color-separator);
    }
    .range-savora::-moz-range-progress {
        height: 8px;
        border-radius: var(--radius-full);
        background: var(--color-primary-coral);
    }
    .range-savora::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: var(--radius-full);
        background: var(--color-primary-coral);
        border: 0;
        box-shadow: var(--shadow-card);
    }

    /* =========================================================
       TAG CHIPS
       ========================================================= */
    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--color-separator);
        background: var(--color-card-bg);
        font-size: var(--text-xs);
        font-weight: 500;
        color: var(--color-text-primary);
        cursor: pointer;
        transition: all .2s;
    }
    .tag-chip.selected {
        background: var(--gradient-accent);
        border-color: var(--color-primary-coral);
        color: #ffffff;
    }
    .tag-chip-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 14px;
        border-radius: var(--radius-full);
        border: 1.5px solid var(--color-separator);
        background: var(--color-card-bg);
        font-size: var(--text-xs);
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
    }
    .tag-chip-pill.selected {
        background: var(--gradient-accent);
        border-color: transparent;
        color: #ffffff;
    }

    /* =========================================================
       SECTION HEADER
       ========================================================= */
    .section-header-savora { display: flex; align-items: center; gap: 12px; }
    .section-header-savora .accent-bar {
        width: 4px; height: 24px;
        background: var(--gradient-accent);
        border-radius: 2px; flex-shrink: 0;
    }
    .section-header-savora .icon-box {
        width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gradient-accent);
        border-radius: var(--radius-sm); flex-shrink: 0;
    }
    .section-header-savora .icon-box i,
    .section-header-savora .icon-box svg { color: #ffffff; font-size: 18px; }
    .section-header-savora .icon-box svg { width: 22px; height: 22px; display: block; flex-shrink: 0; }
    .section-header-savora .header-title {
        font-size: var(--text-lg); font-weight: 700;
        color: var(--color-text-primary); margin: 0;
    }

    /* =========================================================
       EMPTY STATE
       ========================================================= */
    .empty-state-savora {
        padding: 48px;
        background: var(--gradient-card);
        border: 1.5px solid var(--color-card-border);
        border-radius: var(--radius-xl);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; gap: 16px;
    }
    .empty-state-savora .empty-icon { font-size: 64px; color: var(--color-text-muted); line-height: 1; }
    .empty-state-savora .empty-title { font-size: var(--text-base); font-weight: 600; color: var(--color-text-secondary); margin: 0; }
    .empty-state-savora .empty-subtitle { font-size: var(--text-xs); color: var(--color-text-muted); margin: 0; }

    /* =========================================================
       INFO BANNER
       ========================================================= */
    .info-banner-savora {
        display: flex; align-items: flex-start; gap: 12px; padding: 16px;
        background: linear-gradient(90deg, rgba(231,111,81,0.10), rgba(244,162,97,0.10));
        border: 1px solid rgba(231,111,81,0.30);
        border-radius: var(--radius-md);
    }
    .info-banner-savora .banner-icon { color: var(--color-primary-coral); font-size: 22px; flex-shrink: 0; margin-top: 1px; }
    .info-banner-savora .banner-text { font-size: var(--text-xs); font-weight: 500; color: rgba(231,111,81,0.90); line-height: 1.5; }

    /* =========================================================
       BADGE
       ========================================================= */
    .badge-savora {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 20px; height: 20px; padding: 0 6px;
        background: var(--gradient-badge);
        border-radius: var(--radius-full);
        box-shadow: var(--shadow-badge);
        font-size: 11px; font-weight: 700; color: #ffffff;
    }

    /* =========================================================
       ROLE BADGE
       ========================================================= */
    .role-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: var(--radius-full);
        font-size: 11px; font-weight: 700; color: #ffffff; letter-spacing: 0.05em;
    }
    .role-badge.admin   { background: var(--gradient-admin); }
    .role-badge.premium { background: var(--gradient-premium); }
    .role-badge.user    { background: linear-gradient(90deg, #9CA3AF, #6B7280); }

    /* =========================================================
       GRADIENT BACKGROUNDS (utility)
       ========================================================= */
    .bg-gradient-primary  { background: var(--gradient-primary); }
    .bg-gradient-accent   { background: var(--gradient-accent); }
    .bg-gradient-teal     { background: var(--gradient-teal); }
    .bg-gradient-orange   { background: var(--gradient-orange); }
    .bg-gradient-category { background: var(--gradient-category); }
    .bg-gradient-privacy  { background: var(--gradient-privacy); }
    .bg-gradient-terms    { background: var(--gradient-terms); }
    .bg-gradient-admin    { background: var(--gradient-admin); }
    .bg-gradient-premium  { background: var(--gradient-premium); }
    .bg-gradient-card     { background: var(--gradient-card); }
</style>

@if($savoraSettingsEnabled)
<script>
    window.SavoraSettings = Object.assign(window.SavoraSettings || {}, {
        language: @js($savoraLanguage),
        fontSize: @js($savoraFontSize),
        autoSaveDrafts: @js($savoraAutoSaveDrafts),
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.SavoraSettings.language !== 'en') return;

        const dictionary = {
            'Beranda': 'Home',
            'Home': 'Home',
            'Cari': 'Search',
            'Koleksi': 'Saved',
            'Simpan': 'Save',
            'Profil': 'Profile',
            'Pengaturan': 'Settings',
            'Notifikasi': 'Notifications',
            'Buat Resep': 'Create Recipe',
            'Buat Resep Baru': 'Create New Recipe',
            'Bagikan resep lezatmu ke komunitas': 'Share your tasty recipe with the community',
            'Informasi Dasar': 'Basic Info',
            'Judul Resep *': 'Recipe Title *',
            'Deskripsi *': 'Description *',
            'Kategori *': 'Category *',
            'Kesulitan': 'Difficulty',
            'Waktu (mnt)': 'Time (min)',
            'Porsi': 'Servings',
            'Kalori': 'Calories',
            'Bahan-bahan': 'Ingredients',
            'Langkah-langkah': 'Steps',
            'Tag (opsional)': 'Tags (optional)',
            'Tag Populer': 'Popular Tags',
            'Belum ada bahan': 'No ingredients yet',
            'Belum ada langkah': 'No steps yet',
            'Tambahkan bahan...': 'Add ingredient...',
            'Tambahkan langkah...': 'Add step...',
            'Judul resep yang menarik': 'An interesting recipe title',
            'Ceritakan tentang resep Anda...': 'Tell people about your recipe...',
            'Pilih...': 'Choose...',
            'Mudah': 'Easy',
            'Sedang': 'Medium',
            'Sulit': 'Hard',
            'Edit Resep': 'Edit Recipe',
            'Ganti': 'Change',
            'Upload': 'Upload',
            'Hapus': 'Delete',
            'Selesai': 'Done',
            'Buat tag': 'Create tag',
            'Tag tidak ditemukan. Buat tag baru di atas.': 'Tag not found. Create a new tag above.',
            'Resep Terbaru': 'Latest Recipes',
            'Edit Profil': 'Edit Profile',
            'Pengikut': 'Followers',
            'Mengikuti': 'Following',
            'Bahan': 'Ingredients',
            'Deskripsi': 'Description',
            'Simpan Resep': 'Save Recipe',
            'Simpan ke Koleksi': 'Save to Collection',
            'Tersimpan di Koleksi': 'Saved in Collection',
            'Rating': 'Rating',
            'Komentar': 'Comments',
            'Tulis komentar...': 'Write a comment...',
            'Kirim': 'Send',
            'Belum ada komentar': 'No comments yet',
            'Jadilah yang pertama berkomentar!': 'Be the first to comment!',
            'Hapus komentar?': 'Delete comment?',
            'Resep berhasil dikirim dan menunggu persetujuan admin.': 'Recipe submitted and waiting for admin approval.',
            'Komentar berhasil dikirim.': 'Comment sent.',
            'Berhasil mengikuti.': 'Followed successfully.',
            'Berhenti Mengikuti': 'Unfollow',
            'Ikuti': 'Follow',
            'Keluar': 'Logout',
            'Lihat profil': 'View profile',
            'Untuk Kamu': 'For You',
            'Inspirasi Hari Ini': 'Today\'s Inspiration',
            'Resep Saya': 'My Recipes',
            'Tersimpan': 'Saved',
            'Menyiapkan feed untukmu...': 'Preparing your feed...',
            'Belum Ada Resep': 'No Recipes Yet',
            'Buat Resep Pertama': 'Create First Recipe',
            'Muat Lebih Banyak': 'Load More',
            'Kamu sudah melihat semua resep untukmu': 'You have seen all recipes for you',
            'Buat Koleksi Baru': 'Create New Collection',
            'Koleksi Baru': 'New Collection',
            'Nama Koleksi': 'Collection Name',
            'Deskripsi (opsional)': 'Description (optional)',
            'Batal': 'Cancel',
            'Buat': 'Create',
            'Disukai': 'Liked',
            'Suka': 'Like',
            'Menit': 'Minutes',
            'Tingkat': 'Level',
            'Belum ada rating': 'No rating yet',
            'Rating kamu:': 'Your rating:',
            'Video Tutorial': 'Video Tutorial',
            'Belum ada tag': 'No tags yet',
            'User tidak mengunggah video': 'User did not upload a video',
            'Bagikan': 'Share',
            'Bagikan Resep': 'Share Recipe',
        };

        const skipTags = new Set(['SCRIPT', 'STYLE', 'TEXTAREA', 'INPUT', 'OPTION']);
        const walk = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                if (!node.parentElement || skipTags.has(node.parentElement.tagName)) return NodeFilter.FILTER_REJECT;
                const text = node.nodeValue.trim().replace(/\s+/g, ' ');
                return dictionary[text] ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });

        const nodes = [];
        while (walk.nextNode()) nodes.push(walk.currentNode);
        nodes.forEach(node => {
            const original = node.nodeValue;
            const leading = original.match(/^\s*/)?.[0] ?? '';
            const trailing = original.match(/\s*$/)?.[0] ?? '';
            const key = original.trim().replace(/\s+/g, ' ');
            node.nodeValue = leading + dictionary[key] + trailing;
        });

        document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach((el) => {
            const value = el.getAttribute('placeholder');
            if (dictionary[value]) el.setAttribute('placeholder', dictionary[value]);
        });

        document.querySelectorAll('option').forEach((el) => {
            const value = el.textContent.trim().replace(/\s+/g, ' ');
            if (dictionary[value]) el.textContent = dictionary[value];
        });

        document.querySelectorAll('[data-savora-i18n]').forEach((el) => {
            const value = el.getAttribute('data-savora-i18n');
            if (dictionary[value]) el.textContent = dictionary[value];
        });
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-savora-translate]');
        if (!button) return;
        if (window.SavoraSettings.language !== 'en') return;

        const selector = button.getAttribute('data-savora-translate');
        const scopeSelector = button.getAttribute('data-savora-translate-scope');
        const scope = scopeSelector ? button.closest(scopeSelector) : document;
        const targets = Array.from(scope.querySelectorAll(selector));
        if (targets.length === 0) return;

        const isTranslated = button.getAttribute('data-savora-translated') === 'true';
        const translateLabel = button.getAttribute('data-savora-label') || 'Translate';
        const undoLabel = button.getAttribute('data-savora-undo-label') || 'Undo';

        if (isTranslated) {
            targets.forEach((target) => {
                const originalText = target.getAttribute('data-original-text');
                if (originalText) target.textContent = originalText;
            });
            button.setAttribute('data-savora-translated', 'false');
            button.textContent = translateLabel;
            return;
        }

        const targetLanguage = 'en';
        const sourceLanguage = 'id';
        button.disabled = true;
        button.textContent = 'Translating...';

        for (const target of targets) {
            const originalText = target.getAttribute('data-original-text') || target.textContent.trim();
            if (!originalText) continue;
            target.setAttribute('data-original-text', originalText);

            try {
                const response = await fetch(
                    `https://api.mymemory.translated.net/get?q=${encodeURIComponent(originalText)}&langpair=${sourceLanguage}|${targetLanguage}`
                );
                const json = await response.json();
                const translated = json?.responseData?.translatedText;
                if (translated) target.textContent = translated;
            } catch (error) {
                target.textContent = originalText;
            }
        }

        button.disabled = false;
        button.setAttribute('data-savora-translated', 'true');
        button.textContent = undoLabel;
    });
</script>
@endif
