import 'package:flutter/material.dart';
import '../services/app_settings_service.dart';

class AppTheme {
  // Primary Colors
  static const Color primaryDark = Color(0xFF264653);
  static const Color primaryTeal = Color(0xFF2A9D8F);
  static const Color primaryYellow = Color(0xFFE9C46A);
  static const Color primaryOrange = Color(0xFFF4A261);
  static const Color primaryCoral = Color(0xFFE76F51);

  // Background Colors
  static bool get isDarkMode => AppSettingsService.current.isDarkMode;
  static Color get backgroundLight =>
      isDarkMode ? const Color(0xFF0F1318) : const Color(0xFFF5F7FA);
  static Color get surfaceColor =>
      isDarkMode ? const Color(0xFF1A2330) : Colors.white;
  static Color get subtleSurfaceColor =>
      isDarkMode ? const Color(0xFF222C35) : Colors.grey.shade100;
  static Color get borderColor =>
      isDarkMode ? Colors.white.withValues(alpha: 0.12) : Colors.grey.shade200;
  static Color get cardBackground => surfaceColor;
  static Color get lightPanelColor =>
      isDarkMode ? subtleSurfaceColor : Colors.grey.shade50;
  static Color get lightPanelAccentColor =>
      isDarkMode ? surfaceColor : Colors.grey.shade100;

  // Text Colors
  static Color get textPrimary =>
      isDarkMode ? const Color(0xFFF0F4F8) : const Color(0xFF264653);
  static Color get textSecondary =>
      isDarkMode ? const Color(0xFFA8B8C8) : const Color(0xFF6B7280);
  static Color get textMuted =>
      isDarkMode ? const Color(0xFF6B7A8D) : const Color(0xFF9CA3AF);

  // Extended Colors (untuk modal, tag management, recipe card, app bar)
  static const Color privacyGreen   = Color(0xFF2A9D8F);
  static const Color privacyDark    = Color(0xFF264653);
  static const Color privacyDeep    = Color(0xFF1a5c54);
  static const Color termsRed       = Color(0xFFE76F51);
  static const Color termsAmber     = Color(0xFFF4A261);
  static const Color termsYellow    = Color(0xFFE9C46A);
  static const Color tagBorder      = Color(0xFFE9C46A);
  static const Color recipeCatDark  = Color(0xFF264653);
  static const Color recipeCatTeal  = Color(0xFF2A9D8F);
  static const Color logoBlue       = Color(0xFF2B6CB0);
  static const Color logoOrange     = Color(0xFFFF6B35);
  static const Color badgeRed       = Color(0xFFFF3B30);
  static const Color badgeRedLight  = Color(0xFFFF6B6B);

  // Proxy/Warning Colors
  static const Color proxyOrange    = Color(0xFFF97316);
  static const Color proxyPurple    = Color(0xFF9333EA);

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [
      primaryDark,
      primaryTeal,
      primaryYellow,
      primaryOrange,
      primaryCoral,
    ],
  );

  static const LinearGradient accentGradient = LinearGradient(
    colors: [primaryCoral, primaryOrange],
  );

  static const LinearGradient tealGradient = LinearGradient(
    colors: [primaryTeal, Color(0xFF3DB9A9)],
  );

  static const LinearGradient orangeGradient = LinearGradient(
    colors: [primaryOrange, primaryYellow],
  );

  static const LinearGradient logoGradient = LinearGradient(
    colors: [logoBlue, logoOrange],
  );

  static const LinearGradient privacyGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [privacyGreen, privacyDark, privacyDeep],
  );

  static const LinearGradient termsGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [termsRed, termsAmber, termsYellow],
  );

  static const LinearGradient badgeGradient = LinearGradient(
    colors: [badgeRed, badgeRedLight],
  );

  static LinearGradient get cardGradient => LinearGradient(
        colors: [
          primaryCoral.withValues(alpha: isDarkMode ? 0.08 : 0.05),
          (isDarkMode ? primaryTeal : primaryOrange)
              .withValues(alpha: isDarkMode ? 0.06 : 0.1),
        ],
      );

  static LinearGradient get inputGradient => LinearGradient(
        colors: [
          isDarkMode
              ? Colors.white.withValues(alpha: 0.05)
              : primaryCoral.withValues(alpha: 0.05),
          isDarkMode
              ? Colors.white.withValues(alpha: 0.03)
              : primaryOrange.withValues(alpha: 0.08),
        ],
      );

  // Admin/Premium Gradients
  static const LinearGradient adminGradient = LinearGradient(
    colors: [Color(0xFFFFD700), Color(0xFFFFA500)],
  );

  static const LinearGradient premiumGradient = LinearGradient(
    colors: [Color(0xFF6C63FF), Color(0xFF9F8FFF)],
  );

  // Recipe card category gradient
  static const LinearGradient categoryGradient = LinearGradient(
    colors: [recipeCatDark, recipeCatTeal],
  );

  // Box Shadows
  static List<BoxShadow> get primaryShadow => [
        BoxShadow(
          color: primaryCoral.withValues(alpha: 0.3),
          blurRadius: 20,
          offset: const Offset(0, 10),
        ),
      ];

  static List<BoxShadow> get cardShadow => [
        BoxShadow(
          color: isDarkMode
              ? Colors.black.withValues(alpha: 0.35)
              : primaryCoral.withValues(alpha: 0.1),
          blurRadius: isDarkMode ? 18 : 10,
          offset: const Offset(0, 2),
        ),
      ];

  static List<BoxShadow> get buttonShadow => [
        BoxShadow(
          color: primaryCoral.withValues(alpha: 0.4),
          blurRadius: 15,
          offset: const Offset(0, 8),
        ),
      ];

  static List<BoxShadow> get logoBlueShadow => [
        BoxShadow(
          color: logoBlue.withValues(alpha: 0.3),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ];

  static List<BoxShadow> get badgeShadow => [
        BoxShadow(
          color: badgeRed.withValues(alpha: 0.5),
          blurRadius: 8,
          spreadRadius: 1,
        ),
      ];

  // Border Decorations
  static BoxDecoration get cardDecoration => BoxDecoration(
        color: cardBackground,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: primaryCoral.withValues(alpha: isDarkMode ? 0.2 : 0.18),
          width: 2,
        ),
        boxShadow: cardShadow,
      );

  static BoxDecoration inputDecoration(Color iconColor) => BoxDecoration(
        color: isDarkMode ? subtleSurfaceColor : null,
        gradient: isDarkMode
            ? null
            : LinearGradient(
                colors: [
                  iconColor.withValues(alpha: 0.05),
                  iconColor.withValues(alpha: 0.1),
                ],
              ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: iconColor.withValues(alpha: isDarkMode ? 0.35 : 0.2),
          width: 1.5,
        ),
      );

  // Button Styles
  static BoxDecoration get primaryButtonDecoration => BoxDecoration(
        gradient: accentGradient,
        borderRadius: BorderRadius.circular(16),
        boxShadow: buttonShadow,
      );

  static BoxDecoration get outlinedButtonDecoration => BoxDecoration(
        color: Colors.white.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.3),
          width: 1.5,
        ),
      );

  // Tag chip decoration for tag management screen
  static BoxDecoration tagChipDecoration(Color color, {bool isSelected = false}) => BoxDecoration(
        color: isSelected ? color.withValues(alpha: 0.15) : surfaceColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: isSelected ? color : borderColor,
          width: 1.5,
        ),
      );

  // Section Header Style
  static Widget buildSectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Container(
          width: 4,
          height: 24,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [primaryCoral, primaryOrange],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 12),
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            gradient: accentGradient,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: Colors.white, size: 20),
        ),
        const SizedBox(width: 12),
        Text(
          title,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: textPrimary,
          ),
        ),
      ],
    );
  }

  // Text Styles
  static const TextStyle headingLarge = TextStyle(
    fontSize: 26,
    fontWeight: FontWeight.bold,
    color: Colors.white,
  );

  static TextStyle get headingMedium => TextStyle(
        fontSize: 20,
        fontWeight: FontWeight.bold,
        color: textPrimary,
      );

  static TextStyle get headingSmall => TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.bold,
        color: textPrimary,
      );

  static TextStyle get bodyLarge => TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w500,
        color: textPrimary,
      );

  static TextStyle get bodyMedium => TextStyle(
        fontSize: 14,
        color: textPrimary,
      );

  static TextStyle get bodySmall => TextStyle(
        fontSize: 13,
        color: textSecondary,
      );

  static TextStyle get fieldText => TextStyle(
        color: textPrimary,
        fontWeight: FontWeight.w500,
      );

  static TextStyle get fieldHint => TextStyle(
        color: textMuted,
        fontSize: 14,
      );

  static const TextStyle buttonText = TextStyle(
    color: Colors.white,
    fontSize: 16,
    fontWeight: FontWeight.bold,
  );

  // Input Decoration
  static InputDecoration buildInputDecoration({
    required String hint,
    required IconData icon,
    Color? iconColor,
    int maxLines = 1,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: textMuted, fontSize: 14),
      prefixIcon: Padding(
        padding: EdgeInsets.only(top: maxLines > 1 ? 12 : 0),
        child: Icon(icon, color: iconColor ?? textSecondary, size: 20),
      ),
      suffixIcon: suffixIcon,
      border: InputBorder.none,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
    );
  }

  static BoxDecoration get subtlePanelDecoration => BoxDecoration(
        gradient: LinearGradient(
          colors: [lightPanelAccentColor, lightPanelColor],
        ),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
      );

  // Role-based colors
  static List<Color> getRoleGradient(String role) {
    switch (role) {
      case 'admin':
        return [const Color(0xFFFFD700), const Color(0xFFFFA500)];
      case 'premium':
        return [const Color(0xFF6C63FF), const Color(0xFF9F8FFF)];
      default:
        return [Colors.grey.shade400, Colors.grey.shade500];
    }
  }

  static String getRoleLabel(String role) {
    switch (role) {
      case 'admin':
        return 'ADMIN';
      case 'premium':
        return 'PREMIUM';
      default:
        return 'USER';
    }
  }

  // Tag Chip Decoration
  static BoxDecoration get selectedTagDecoration => BoxDecoration(
        gradient: accentGradient,
        borderRadius: BorderRadius.circular(20),
      );

  static BoxDecoration get unselectedTagDecoration => BoxDecoration(
        color: surfaceColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: borderColor),
      );

  // Empty State Widget
  static Widget buildEmptyState({
    required IconData icon,
    required String title,
    String? subtitle,
  }) {
    return Container(
      padding: const EdgeInsets.all(48),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDarkMode
              ? [surfaceColor, subtleSurfaceColor]
              : [Colors.grey.shade100, Colors.grey.shade50],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Center(
        child: Column(
          children: [
            Icon(icon, size: 64, color: textMuted),
            const SizedBox(height: 16),
            Text(
              title,
              style: TextStyle(
                fontSize: 16,
                color: textSecondary,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 8),
              Text(
                subtitle,
                style: TextStyle(fontSize: 13, color: textMuted),
                textAlign: TextAlign.center,
              ),
            ],
          ],
        ),
      ),
    );
  }

  // Info Banner
  static Widget buildInfoBanner(String message) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            primaryCoral.withValues(alpha: 0.1),
            primaryOrange.withValues(alpha: 0.1),
          ],
        ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: primaryCoral.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.info_outline_rounded, color: primaryCoral, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                fontSize: 13,
                color: primaryCoral.withValues(alpha: 0.9),
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
