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
    $savoraTheme = session('user_theme', 'light');
    $savoraLanguage = session('user_language', 'en');
    $savoraFontSize = (int) session('user_font_size', 14);
    $savoraFontSize = max(12, min(18, $savoraFontSize));
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

        /* Text */
        --color-text-primary:     #264653;
        --color-text-secondary:   #6B7280;

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
            linear-gradient(90deg, rgba(231,111,81,0.05), rgba(244,162,97,0.10));
        --gradient-input:
            linear-gradient(90deg, rgba(231,111,81,0.05), rgba(244,162,97,0.08));

        /* Shadows */
        --shadow-primary:  0 10px 20px rgba(231,111,81,0.30);
        --shadow-card:     0  2px 10px rgba(231,111,81,0.10);
        --shadow-button:   0  8px 15px rgba(231,111,81,0.40);
        --shadow-logo:     0  2px  8px rgba(43, 108,176,0.30);
        --shadow-badge:    0  0    8px 1px rgba(255,59,48,0.50);

        /* Border Radius */
        --radius-xs:   8px;
        --radius-sm:   10px;
        --radius-md:   14px;
        --radius-lg:   16px;
        --radius-xl:   20px;
        --radius-full: 9999px;

        /* Typography Scale */
        --text-xs:   13px;
        --text-sm:   14px;
        --text-base: 16px;
        --text-lg:   18px;
        --text-xl:   20px;
        --text-2xl:  26px;
    }

    @if($savoraTheme === 'dark')
    :root {
        --color-bg-light:       #101418;
        --color-card-bg:        #182027;
        --color-text-primary:   #F8FAFC;
        --color-text-secondary: #CBD5E1;
        --gradient-card:
            linear-gradient(90deg, rgba(231,111,81,0.12), rgba(42,157,143,0.10));
        --gradient-input:
            linear-gradient(90deg, rgba(255,255,255,0.06), rgba(255,255,255,0.04));
        --shadow-card: 0 2px 14px rgba(0,0,0,0.28);
    }
    @endif

    body {
        font-size: {{ $savoraFontSize }}px;
        color: var(--color-text-primary);
    }

    @if($savoraTheme === 'dark')
    body,
    .bg-white,
    .bg-gray-50,
    .bg-gray-100 {
        background-color: var(--color-bg-light) !important;
        color: var(--color-text-primary) !important;
    }

    .card-savora,
    .shadow-lg,
    .shadow-xl,
    .shadow-2xl,
    .input-savora,
    nav.bg-white,
    header.bg-white,
    [class*="hover:bg-gray"]:hover {
        background-color: var(--color-card-bg) !important;
        color: var(--color-text-primary) !important;
        border-color: rgba(255,255,255,0.12) !important;
    }

    .text-gray-900,
    .text-gray-800,
    .text-gray-700 {
        color: var(--color-text-primary) !important;
    }

    .text-gray-600,
    .text-gray-500,
    .text-gray-400 {
        color: var(--color-text-secondary) !important;
    }

    .border-gray-100,
    .border-gray-200,
    .border-gray-300 {
        border-color: rgba(255,255,255,0.12) !important;
    }
    @endif

    /* =========================================================
       TYPOGRAPHY
       ========================================================= */
    .app-heading-large {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: #ffffff;
    }
    .app-heading-medium {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--color-text-primary);
    }
    .app-heading-small {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--color-text-primary);
    }
    .app-body-large {
        font-size: var(--text-base);
        font-weight: 500;
        color: var(--color-text-primary);
    }
    .app-body-medium {
        font-size: var(--text-sm);
        color: var(--color-text-primary);
    }
    .app-body-small {
        font-size: var(--text-xs);
        color: #6B7280;
    }
    .app-btn-text {
        color: #ffffff;
        font-size: var(--text-base);
        font-weight: 700;
    }

    /* =========================================================
       CARD
       ========================================================= */
    .card-savora {
        background: var(--color-card-bg);
        border-radius: var(--radius-xl);
        border: 2px solid rgba(231,111,81,0.20);
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
    .btn-primary-savora:hover {
        opacity: .9;
        transform: translateY(-1px);
        color: #ffffff;
        text-decoration: none;
    }
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
    .btn-outlined-savora:hover {
        background: rgba(255,255,255,0.30);
        color: #ffffff;
        text-decoration: none;
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
        width: 100%;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .input-savora:focus {
        border-color: var(--color-primary-coral);
        box-shadow: 0 0 0 3px rgba(231,111,81,0.15);
    }
    .input-savora::placeholder { color: #9CA3AF; font-size: var(--text-sm); }

    .input-wrapper-savora {
        position: relative;
    }
    .input-wrapper-savora .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        pointer-events: none;
        font-size: 18px;
        line-height: 1;
    }
    .input-wrapper-savora .input-savora.has-icon {
        padding-left: 42px;
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
        border: 1.5px solid #D1D5DB;
        background: #ffffff;
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
        border: 1.5px solid #D1D5DB;
        background: #ffffff;
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
    .section-header-savora {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .section-header-savora .accent-bar {
        width: 4px;
        height: 24px;
        background: var(--gradient-accent);
        border-radius: 2px;
        flex-shrink: 0;
    }
    .section-header-savora .icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gradient-accent);
        border-radius: var(--radius-sm);
        flex-shrink: 0;
    }
    .section-header-savora .icon-box i,
    .section-header-savora .icon-box svg {
        color: #ffffff;
        font-size: 18px;
    }
    .section-header-savora .icon-box svg {
        width: 22px;
        height: 22px;
        display: block;
        flex-shrink: 0;
    }
    .section-header-savora .header-title {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--color-text-primary);
        margin: 0;
    }

    /* =========================================================
       EMPTY STATE
       ========================================================= */
    .empty-state-savora {
        padding: 48px;
        background: linear-gradient(90deg, #F3F4F6, #F9FAFB);
        border-radius: var(--radius-xl);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 16px;
    }
    .empty-state-savora .empty-icon {
        font-size: 64px;
        color: #D1D5DB;
        line-height: 1;
    }
    .empty-state-savora .empty-title {
        font-size: var(--text-base);
        font-weight: 600;
        color: #6B7280;
        margin: 0;
    }
    .empty-state-savora .empty-subtitle {
        font-size: var(--text-xs);
        color: #9CA3AF;
        margin: 0;
    }

    /* =========================================================
       INFO BANNER
       ========================================================= */
    .info-banner-savora {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        background: linear-gradient(90deg, rgba(231,111,81,0.10), rgba(244,162,97,0.10));
        border: 1px solid rgba(231,111,81,0.30);
        border-radius: var(--radius-md);
    }
    .info-banner-savora .banner-icon {
        color: var(--color-primary-coral);
        font-size: 22px;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .info-banner-savora .banner-text {
        font-size: var(--text-xs);
        font-weight: 500;
        color: rgba(231,111,81,0.90);
        line-height: 1.5;
    }

    /* =========================================================
       BADGE
       ========================================================= */
    .badge-savora {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        background: var(--gradient-badge);
        border-radius: var(--radius-full);
        box-shadow: var(--shadow-badge);
        font-size: 11px;
        font-weight: 700;
        color: #ffffff;
    }

    /* =========================================================
       ROLE BADGE
       ========================================================= */
    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.05em;
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
