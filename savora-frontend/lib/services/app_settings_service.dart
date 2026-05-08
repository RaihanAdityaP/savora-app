import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppSettings {
  final String theme;
  final String language;
  final int fontSize;
  final bool notifyLikes;
  final bool notifyComments;
  final bool notifyFollows;
  final bool notifyEmail;
  final bool allowAnalytics;
  final bool profilePublic;
  final bool autoSaveDrafts;

  const AppSettings({
    this.theme = 'light',
    this.language = 'en',
    this.fontSize = 14,
    this.notifyLikes = true,
    this.notifyComments = true,
    this.notifyFollows = true,
    this.notifyEmail = false,
    this.allowAnalytics = true,
    this.profilePublic = true,
    this.autoSaveDrafts = true,
  });

  bool get isDarkMode => theme == 'dark';

  AppSettings copyWith({
    String? theme,
    String? language,
    int? fontSize,
    bool? notifyLikes,
    bool? notifyComments,
    bool? notifyFollows,
    bool? notifyEmail,
    bool? allowAnalytics,
    bool? profilePublic,
    bool? autoSaveDrafts,
  }) {
    return AppSettings(
      theme: theme ?? this.theme,
      language: language ?? this.language,
      fontSize: fontSize ?? this.fontSize,
      notifyLikes: notifyLikes ?? this.notifyLikes,
      notifyComments: notifyComments ?? this.notifyComments,
      notifyFollows: notifyFollows ?? this.notifyFollows,
      notifyEmail: notifyEmail ?? this.notifyEmail,
      allowAnalytics: allowAnalytics ?? this.allowAnalytics,
      profilePublic: profilePublic ?? this.profilePublic,
      autoSaveDrafts: autoSaveDrafts ?? this.autoSaveDrafts,
    );
  }

  Map<String, dynamic> toMap() => {
        'theme': theme,
        'language': language,
        'fontSize': fontSize,
        'notify_likes': notifyLikes,
        'notify_comments': notifyComments,
        'notify_follows': notifyFollows,
        'notify_email': notifyEmail,
        'allow_analytics': allowAnalytics,
        'profile_public': profilePublic,
        'auto_save_drafts': autoSaveDrafts,
      };
}

class AppSettingsService {
  static final ValueNotifier<AppSettings> notifier =
      ValueNotifier<AppSettings>(const AppSettings());

  static AppSettings get current => notifier.value;

  static bool get isEnglish => current.language == 'en';

  static String t(String id, String en, String idText) {
    return isEnglish ? en : idText;
  }

  static Future<AppSettings> load() async {
    final prefs = await SharedPreferences.getInstance();
    final settings = AppSettings(
      theme: prefs.getString('user_theme') ?? 'light',
      language: prefs.getString('user_language') ?? 'en',
      fontSize: prefs.getInt('user_font_size') ?? 14,
      notifyLikes: prefs.getBool('notify_likes') ?? true,
      notifyComments: prefs.getBool('notify_comments') ?? true,
      notifyFollows: prefs.getBool('notify_follows') ?? true,
      notifyEmail: prefs.getBool('notify_email') ?? false,
      allowAnalytics: prefs.getBool('allow_analytics') ?? true,
      profilePublic: prefs.getBool('profile_public') ?? true,
      autoSaveDrafts: prefs.getBool('auto_save_drafts') ?? true,
    );
    notifier.value = settings;
    return settings;
  }

  static Future<void> save(AppSettings settings) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user_theme', settings.theme);
    await prefs.setString('user_language', settings.language);
    await prefs.setInt('user_font_size', settings.fontSize);
    await prefs.setBool('notify_likes', settings.notifyLikes);
    await prefs.setBool('notify_comments', settings.notifyComments);
    await prefs.setBool('notify_follows', settings.notifyFollows);
    await prefs.setBool('notify_email', settings.notifyEmail);
    await prefs.setBool('allow_analytics', settings.allowAnalytics);
    await prefs.setBool('profile_public', settings.profilePublic);
    await prefs.setBool('auto_save_drafts', settings.autoSaveDrafts);
    notifier.value = settings;
  }
}
