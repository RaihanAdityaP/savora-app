import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk Favorites & Recipe Boards operations
/// Taruh di: lib/services/favorite_client.dart
class FavoriteClient {
  // ─────────────────────────────────────────────
  // FAVORITES
  // ─────────────────────────────────────────────

  /// Get semua resep favorit user
  static Future<List<Map<String, dynamic>>> getFavorites(
    String userId,
  ) async {
    try {
      final response = await ApiService.get('/favorites/user/$userId');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('FavoriteClient.getFavorites error: $e');
      return [];
    }
  }

  /// Tambah resep ke favorit
  static Future<bool> addFavorite({
    required String userId,
    required String recipeId,
  }) async {
    try {
      final response = await ApiService.post('/favorites', {
        'user_id': userId,
        'recipe_id': recipeId,
      });
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.addFavorite error: $e');
      return false;
    }
  }

  /// Hapus resep dari favorit
  static Future<bool> removeFavorite({
    required String userId,
    required String recipeId,
  }) async {
    try {
      final response = await ApiService.delete(
        '/favorites',
        body: {'user_id': userId, 'recipe_id': recipeId},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.removeFavorite error: $e');
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // RECIPE BOARDS (Koleksi)
  // ─────────────────────────────────────────────

  /// Get semua board milik user
  static Future<List<Map<String, dynamic>>> getBoards(String userId) async {
    try {
      final response = await ApiService.get('/favorites/boards/$userId');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('FavoriteClient.getBoards error: $e');
      return [];
    }
  }

  /// Buat board baru
  static Future<Map<String, dynamic>?> createBoard({
    required String userId,
    required String name,
    String? description,
  }) async {
    try {
      final response = await ApiService.post('/favorites/boards', {
        'user_id': userId,
        'name': name,
        if (description != null) 'description': description,
      });
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('FavoriteClient.createBoard error: $e');
      return null;
    }
  }

  /// Update board
  static Future<bool> updateBoard({
    required String boardId,
    String? name,
    String? description,
  }) async {
    try {
      final response = await ApiService.put(
        '/favorites/boards/$boardId',
        {
          if (name != null) 'name': name,
          if (description != null) 'description': description,
        },
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.updateBoard error: $e');
      return false;
    }
  }

  /// Hapus board
  static Future<bool> deleteBoard(String boardId) async {
    try {
      final response = await ApiService.delete('/favorites/boards/$boardId');
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.deleteBoard error: $e');
      return false;
    }
  }

  /// Get resep dalam board
  static Future<List<Map<String, dynamic>>> getBoardRecipes(
    String boardId,
  ) async {
    try {
      final response =
          await ApiService.get('/favorites/boards/$boardId/recipes');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('FavoriteClient.getBoardRecipes error: $e');
      return [];
    }
  }

  /// Tambah resep ke board
  static Future<bool> addRecipeToBoard({
    required String boardId,
    required String recipeId,
  }) async {
    try {
      final response = await ApiService.post(
        '/favorites/boards/$boardId/recipes',
        {'recipe_id': recipeId},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.addRecipeToBoard error: $e');
      return false;
    }
  }

  /// Hapus resep dari board
  static Future<bool> removeRecipeFromBoard({
    required String boardId,
    required String recipeId,
  }) async {
    try {
      final response = await ApiService.delete(
        '/favorites/boards/$boardId/recipes/$recipeId',
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('FavoriteClient.removeRecipeFromBoard error: $e');
      return false;
    }
  }
}