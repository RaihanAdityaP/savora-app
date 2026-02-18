import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk Rating operations
/// Taruh di: lib/services/rating_client.dart
class RatingClient {
  /// Get semua rating untuk sebuah resep
  static Future<Map<String, dynamic>> getRecipeRatings(
    String recipeId,
  ) async {
    try {
      final response = await ApiService.get('/ratings/recipe/$recipeId');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
        // response['data'] berisi:
        //  - ratings       : List<Map> (daftar rating + komentar)
        //  - average_rating: double
        //  - total_ratings : int
      }
      return {'ratings': [], 'average_rating': 0.0, 'total_ratings': 0};
    } catch (e) {
      debugPrint('RatingClient.getRecipeRatings error: $e');
      return {'ratings': [], 'average_rating': 0.0, 'total_ratings': 0};
    }
  }

  /// Get average rating saja (ringan, tanpa daftar)
  static Future<double> getAverageRating(String recipeId) async {
    try {
      final response =
          await ApiService.get('/ratings/recipe/$recipeId/average');
      if (response['success'] == true) {
        final avg = response['data']?['average_rating'];
        return (avg as num?)?.toDouble() ?? 0.0;
      }
      return 0.0;
    } catch (e) {
      debugPrint('RatingClient.getAverageRating error: $e');
      return 0.0;
    }
  }

  /// Get rating statistik (distribusi 1–5 bintang)
  static Future<Map<String, dynamic>> getRatingStats(String recipeId) async {
    try {
      final response =
          await ApiService.get('/ratings/recipe/$recipeId/stats');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return {
        'total': 0,
        'average': 0.0,
        'distribution': {'5': 0, '4': 0, '3': 0, '2': 0, '1': 0},
      };
    } catch (e) {
      debugPrint('RatingClient.getRatingStats error: $e');
      return {
        'total': 0,
        'average': 0.0,
        'distribution': {'5': 0, '4': 0, '3': 0, '2': 0, '1': 0},
      };
    }
  }

  /// Get rating user untuk resep tertentu
  /// Berguna untuk tahu apakah user sudah pernah rating
  static Future<Map<String, dynamic>?> getUserRatingForRecipe({
    required String userId,
    required String recipeId,
  }) async {
    try {
      final response = await ApiService.get(
        '/ratings/user/$userId/recipe/$recipeId',
      );
      if (response['success'] == true) {
        final data = response['data'];
        if (data == null) return null;
        return Map<String, dynamic>.from(data);
      }
      return null;
    } catch (e) {
      debugPrint('RatingClient.getUserRatingForRecipe error: $e');
      return null;
    }
  }

  /// Tambah atau update rating
  /// Jika user sudah pernah rating resep ini, backend akan auto-update
  static Future<bool> rateRecipe({
    required String userId,
    required String recipeId,
    required int rating, // 1–5
    String? comment,
  }) async {
    try {
      final response = await ApiService.post('/ratings', {
        'user_id': userId,
        'recipe_id': recipeId,
        'rating': rating,
        if (comment != null) 'comment': comment,
      });
      return response['success'] == true;
    } catch (e) {
      debugPrint('RatingClient.rateRecipe error: $e');
      return false;
    }
  }

  /// Hapus rating
  static Future<bool> deleteRating(String ratingId) async {
    try {
      final response = await ApiService.delete('/ratings/$ratingId');
      return response['success'] == true;
    } catch (e) {
      debugPrint('RatingClient.deleteRating error: $e');
      return false;
    }
  }

  /// Get semua rating yang pernah dibuat user
  static Future<List<Map<String, dynamic>>> getUserRatings(
    String userId,
  ) async {
    try {
      final response = await ApiService.get('/ratings/user/$userId');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('RatingClient.getUserRatings error: $e');
      return [];
    }
  }
}