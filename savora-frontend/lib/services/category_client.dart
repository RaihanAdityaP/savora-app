import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk Category operations
/// Taruh di: lib/services/category_client.dart
class CategoryClient {
  /// Get semua kategori
  static Future<List<Map<String, dynamic>>> getCategories() async {
    try {
      final response = await ApiService.get('/categories');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('CategoryClient.getCategories error: $e');
      return [];
    }
  }

  /// Get detail kategori + resep di dalamnya
  static Future<Map<String, dynamic>?> getCategory(int categoryId) async {
    try {
      final response = await ApiService.get('/categories/$categoryId');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('CategoryClient.getCategory error: $e');
      return null;
    }
  }

  /// Get resep berdasarkan kategori
  static Future<List<Map<String, dynamic>>> getRecipesByCategory(
    int categoryId, {
    int limit = 10,
    int offset = 0,
    String orderBy = 'created_at',
    String orderDirection = 'desc',
  }) async {
    try {
      final response = await ApiService.get(
        '/categories/$categoryId/recipes'
        '?limit=$limit&offset=$offset'
        '&order_by=$orderBy&order_direction=$orderDirection',
      );
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('CategoryClient.getRecipesByCategory error: $e');
      return [];
    }
  }
}