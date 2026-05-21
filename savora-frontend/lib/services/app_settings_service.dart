import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class AppSettings {
  final String theme;
  final String language;
  final int fontSize;
  final bool notifyLikes;
  final bool notifyComments;
  final bool notifyFollows;
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
      allowAnalytics: allowAnalytics ?? this.allowAnalytics,
      profilePublic: profilePublic ?? this.profilePublic,
      autoSaveDrafts: autoSaveDrafts ?? this.autoSaveDrafts,
    );
  }

  Map<String, dynamic> toMap() => {
        'theme': theme,
        'language': language,
        'fontSize': fontSize,
        'font_size': fontSize,
        'notify_likes': notifyLikes,
        'notify_comments': notifyComments,
        'notify_follows': notifyFollows,
        'allow_analytics': allowAnalytics,
        'profile_public': profilePublic,
        'auto_save_drafts': autoSaveDrafts,
      };

  Map<String, dynamic> toApiMap() => {
        'theme': theme,
        'language': language,
        'font_size': fontSize,
        'notify_likes': notifyLikes,
        'notify_comments': notifyComments,
        'notify_follows': notifyFollows,
        'allow_analytics': allowAnalytics,
        'profile_public': profilePublic,
        'auto_save_drafts': autoSaveDrafts,
      };

  factory AppSettings.fromMap(Map<String, dynamic> map) {
    return AppSettings(
      theme: map['theme']?.toString() ?? 'light',
      language: map['language']?.toString() ?? 'en',
      fontSize: _toInt(map['fontSize'] ?? map['font_size'], fallback: 14),
      notifyLikes: _toBool(map['notify_likes'], fallback: true),
      notifyComments: _toBool(map['notify_comments'], fallback: true),
      notifyFollows: _toBool(map['notify_follows'], fallback: true),
      allowAnalytics: _toBool(map['allow_analytics'], fallback: true),
      profilePublic: _toBool(map['profile_public'], fallback: true),
      autoSaveDrafts: _toBool(map['auto_save_drafts'], fallback: true),
    );
  }

  static int _toInt(dynamic value, {required int fallback}) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? fallback;
  }

  static bool _toBool(dynamic value, {bool fallback = false}) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    final text = value?.toString().toLowerCase();
    if (text == 'true' || text == '1') return true;
    if (text == 'false' || text == '0') return false;
    return fallback;
  }
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
    var settings = AppSettings(
      theme: prefs.getString('user_theme') ?? 'light',
      language: prefs.getString('user_language') ?? 'en',
      fontSize: prefs.getInt('user_font_size') ?? 14,
      notifyLikes: prefs.getBool('notify_likes') ?? true,
      notifyComments: prefs.getBool('notify_comments') ?? true,
      notifyFollows: prefs.getBool('notify_follows') ?? true,
      allowAnalytics: prefs.getBool('allow_analytics') ?? true,
      profilePublic: prefs.getBool('profile_public') ?? true,
      autoSaveDrafts: prefs.getBool('auto_save_drafts') ?? true,
    );

    if (ApiService.hasToken) {
      try {
        final response = await ApiService.get('/settings');
        if (response['success'] == true && response['data'] is Map) {
          settings = AppSettings.fromMap(
            Map<String, dynamic>.from(response['data'] as Map),
          );
          await _persistLocal(settings);
        }
      } catch (e) {
        debugPrint('AppSettingsService remote load skipped: $e');
      }
    }

    notifier.value = settings;
    return settings;
  }

  static Future<void> save(AppSettings settings) async {
    await _persistLocal(settings);
    notifier.value = settings;

    if (ApiService.hasToken) {
      try {
        await ApiService.post('/settings', settings.toApiMap());
      } catch (e) {
        debugPrint('AppSettingsService remote save skipped: $e');
      }
    }
  }

  static Future<void> _persistLocal(AppSettings settings) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user_theme', settings.theme);
    await prefs.setString('user_language', settings.language);
    await prefs.setInt('user_font_size', settings.fontSize);
    await prefs.setBool('notify_likes', settings.notifyLikes);
    await prefs.setBool('notify_comments', settings.notifyComments);
    await prefs.setBool('notify_follows', settings.notifyFollows);
    await prefs.setBool('allow_analytics', settings.allowAnalytics);
    await prefs.setBool('profile_public', settings.profilePublic);
    await prefs.setBool('auto_save_drafts', settings.autoSaveDrafts);
  }
}
