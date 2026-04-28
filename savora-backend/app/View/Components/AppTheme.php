<?php

namespace App\View\Components;

/**
 * AppTheme - Konfigurasi tema aplikasi Savora
 * Berisi semua warna, gradien, dan shadow untuk konsistensi desain
 */ 
class AppTheme
{
    // Primary Colors
    public const PRIMARY_DARK = '#264653';
    public const PRIMARY_TEAL = '#2A9D8F';
    public const PRIMARY_YELLOW = '#E9C46A';
    public const PRIMARY_ORANGE = '#F4A261';
    public const PRIMARY_CORAL = '#E76F51';

    // Background Colors
    public const BACKGROUND_LIGHT = '#F5F7FA';
    public const CARD_BACKGROUND = '#FFFFFF';

    // Text Colors
    public const TEXT_PRIMARY = '#264653';
    public const TEXT_SECONDARY = '#6B7280';

    // Extended Colors
    public const PRIVACY_GREEN = '#2A9D8F';
    public const PRIVACY_DARK = '#264653';
    public const PRIVACY_DEEP = '#1a5c54';
    public const TERMS_RED = '#E76F51';
    public const TERMS_AMBER = '#F4A261';
    public const TERMS_YELLOW = '#E9C46A';
    public const TAG_BORDER = '#E9C46A';
    public const RECIPE_CAT_DARK = '#264653';
    public const RECIPE_CAT_TEAL = '#2A9D8F';
    public const LOGO_BLUE = '#2B6CB0';
    public const LOGO_ORANGE = '#FF6B35';
    public const BADGE_RED = '#FF3B30';
    public const BADGE_RED_LIGHT = '#FF6B6B';

    // Proxy/Warning Colors
    public const PROXY_ORANGE = '#F97316';
    public const PROXY_PURPLE = '#9333EA';

    /**
     * Dapatkan CSS untuk gradient utama
     */
    public static function getPrimaryGradient(): string
    {
        return 'background: linear-gradient(135deg, ' . self::PRIMARY_DARK . ', ' . self::PRIMARY_TEAL . ', ' . self::PRIMARY_YELLOW . ', ' . self::PRIMARY_ORANGE . ', ' . self::PRIMARY_CORAL . ')';
    }

    /**
     * Dapatkan CSS untuk accent gradient
     */
    public static function getAccentGradient(): string
    {
        return 'background: linear-gradient(135deg, ' . self::PRIMARY_CORAL . ', ' . self::PRIMARY_ORANGE . ')';
    }

    /**
     * Dapatkan CSS untuk teal gradient
     */
    public static function getTealGradient(): string
    {
        return 'background: linear-gradient(135deg, ' . self::PRIMARY_TEAL . ', #3DB9A9)';
    }

    /**
     * Dapatkan CSS untuk privacy gradient
     */
    public static function getPrivacyGradient(): string
    {
        return 'background: linear-gradient(135deg, ' . self::PRIVACY_GREEN . ', ' . self::PRIVACY_DARK . ', ' . self::PRIVACY_DEEP . ')';
    }

    /**
     * Dapatkan CSS untuk terms gradient
     */
    public static function getTermsGradient(): string
    {
        return 'background: linear-gradient(135deg, ' . self::TERMS_RED . ', ' . self::TERMS_AMBER . ', ' . self::TERMS_YELLOW . ')';
    }

    /**
     * Dapatkan CSS untuk shadow
     */
    public static function getPrimaryShadow(): string
    {
        return 'box-shadow: 0 10px 20px rgba(' . self::hexToRgb(self::PRIMARY_CORAL) . ', 0.3)';
    }

    /**
     * Konversi hex color ke RGB
     */
    public static function hexToRgb(string $hex): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }
}
