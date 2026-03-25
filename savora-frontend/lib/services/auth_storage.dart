import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// AuthStorage — persist Sanctum token & userId ke SharedPreferences
/// Dipanggil dari AuthClient setelah login berhasil, dan dari main() saat app buka
class AuthStorage {
  static const String _tokenKey  = 'auth_sanctum_token';
  static const String _userIdKey = 'auth_user_id';

  /// Simpan token + userId setelah login sukses
  static Future<void> save({
    required String token,
    required String userId,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_tokenKey, token);
      await prefs.setString(_userIdKey, userId);
      debugPrint('[AuthStorage] Token saved');
    } catch (e) {
      debugPrint('[AuthStorage] save error: $e');
    }
  }

  /// Load token + userId yang tersimpan
  static Future<({String? token, String? userId})> load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token  = prefs.getString(_tokenKey);
      final userId = prefs.getString(_userIdKey);
      debugPrint('[AuthStorage] Loaded token: ${token != null ? "found" : "null"}');
      return (token: token, userId: userId);
    } catch (e) {
      debugPrint('[AuthStorage] load error: $e');
      return (token: null, userId: null);
    }
  }

  /// Hapus saat logout
  static Future<void> clear() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_tokenKey);
      await prefs.remove(_userIdKey);
      debugPrint('[AuthStorage] Token cleared');
    } catch (e) {
      debugPrint('[AuthStorage] clear error: $e');
    }
  }

  /// Cek apakah ada token tersimpan
  static Future<bool> hasToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(_tokenKey);
      return token != null && token.isNotEmpty;
    } catch (e) {
      return false;
    }
  }
}