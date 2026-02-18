import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk User & Profile operations
/// Taruh di: lib/services/user_client.dart
class UserClient {
  /// Get profile user (bisa user lain juga)
  static Future<Map<String, dynamic>?> getProfile(String userId) async {
    try {
      final response = await ApiService.get('/users/$userId');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('UserClient.getProfile error: $e');
      return null;
    }
  }

  /// Update profile sendiri
  static Future<Map<String, dynamic>?> updateProfile({
    required String userId,
    String? username,
    String? fullName,
    String? bio,
    String? avatarPath, // path file lokal jika ada foto baru
  }) async {
    try {
      if (avatarPath != null) {
        // Upload avatar sekalian
        final fields = <String, String>{
          if (username != null) 'username': username,
          if (fullName != null) 'full_name': fullName,
          if (bio != null) 'bio': bio,
        };
        final response = await ApiService.uploadImage(
          '/users/$userId',
          avatarPath,
          fields: fields,
        );
        if (response['success'] == true) {
          return Map<String, dynamic>.from(response['data']);
        }
        return null;
      }

      final data = <String, dynamic>{
        if (username != null) 'username': username,
        if (fullName != null) 'full_name': fullName,
        if (bio != null) 'bio': bio,
      };
      final response = await ApiService.put('/users/$userId', data);
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('UserClient.updateProfile error: $e');
      return null;
    }
  }

  /// Follow user lain
  static Future<bool> follow({
    required String targetUserId, // user yang mau di-follow
    required String followerId,   // user yang sedang login
  }) async {
    try {
      final response = await ApiService.post(
        '/users/$targetUserId/follow',
        {'follower_id': followerId},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('UserClient.follow error: $e');
      return false;
    }
  }

  /// Unfollow user
  static Future<bool> unfollow({
    required String targetUserId,
    required String followerId,
  }) async {
    try {
      final response = await ApiService.post(
        '/users/$targetUserId/unfollow',
        {'follower_id': followerId},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('UserClient.unfollow error: $e');
      return false;
    }
  }

  /// Cek apakah kita sudah follow user tertentu
  static Future<bool> isFollowing({
    required String targetUserId,
    required String myUserId,
  }) async {
    try {
      final response = await ApiService.get(
        '/users/$targetUserId/is-following?follower_id=$myUserId',
      );
      return response['data']?['is_following'] == true;
    } catch (e) {
      debugPrint('UserClient.isFollowing error: $e');
      return false;
    }
  }

  /// Get daftar followers user
  static Future<List<Map<String, dynamic>>> getFollowers(String userId) async {
    try {
      final response = await ApiService.get('/users/$userId/followers');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('UserClient.getFollowers error: $e');
      return [];
    }
  }

  /// Get daftar user yang diikuti
  static Future<List<Map<String, dynamic>>> getFollowing(String userId) async {
    try {
      final response = await ApiService.get('/users/$userId/following');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('UserClient.getFollowing error: $e');
      return [];
    }
  }

  /// Get resep milik user
  static Future<List<Map<String, dynamic>>> getUserRecipes(
    String userId, {
    String status = 'approved',
    int limit = 10,
    int offset = 0,
  }) async {
    try {
      final response = await ApiService.get(
        '/users/$userId/recipes?status=$status&limit=$limit&offset=$offset',
      );
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('UserClient.getUserRecipes error: $e');
      return [];
    }
  }
}