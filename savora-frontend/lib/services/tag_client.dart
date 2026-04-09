import 'package:flutter/foundation.dart';
import 'api_service.dart';

class TagClient {
  static Future<List<Map<String, dynamic>>> searchTags(String query, {int limit = 20}) async {
    try {
      final response = await ApiService.get('/tags/search?q=${Uri.encodeComponent(query)}&limit=$limit');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('TagClient.searchTags error: $e');
      return [];
    }
  }

  static Future<List<Map<String, dynamic>>> popularTags({int limit = 30}) async {
    try {
      final response = await ApiService.get('/tags/popular?limit=$limit');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('TagClient.popularTags error: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>?> createTag(String name) async {
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) throw Exception('User belum login');

      final response = await ApiService.post('/tags', {
        'name': name,
        'created_by': userId,
      });

      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      throw Exception(response['message'] ?? 'Gagal membuat tag');
    } catch (e) {
      debugPrint('TagClient.createTag error: $e');
      return null;
    }
  }
}