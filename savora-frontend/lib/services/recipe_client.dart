import 'api_service.dart';
import 'package:flutter/foundation.dart';
import 'dart:convert';

/// Client untuk operasi Recipe via REST API
/// Menggantikan direct Supabase access
class RecipeClient {
  /// Get all recipes with filters
  static Future<List<Map<String, dynamic>>> getRecipes({
    String status = 'approved',
    int? categoryId,
    String? userId,
    int limit = 10,
    int offset = 0,
    String orderBy = 'created_at',
    String orderDirection = 'desc',
  }) async {
    try {
      String endpoint = '/recipes?status=$status&limit=$limit&offset=$offset';
      endpoint += '&order_by=$orderBy&order_direction=$orderDirection';

      if (categoryId != null) {
        endpoint += '&category_id=$categoryId';
      }

      if (userId != null) {
        endpoint += '&user_id=$userId';
      }

      final response = await ApiService.get(endpoint);

      if (response['success'] == true) {
        final recipes = response['data'] as List;
        return recipes.map((e) => Map<String, dynamic>.from(e)).toList();
      } else {
        throw Exception(response['message'] ?? 'Failed to load recipes');
      }
    } catch (e) {
      debugPrint('RecipeClient Error (getRecipes): $e');
      return [];
    }
  }

  /// Get single recipe by ID
  static Future<Map<String, dynamic>?> getRecipe(String id) async {
    try {
      final response = await ApiService.get('/recipes/$id');

      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Failed to load recipe');
      }
    } catch (e) {
      debugPrint('RecipeClient Error (getRecipe): $e');
      return null;
    }
  }

  /// Search recipes
  static Future<List<Map<String, dynamic>>> searchRecipes(String query) async {
    return searchRecipesAdvanced(query: query);
  }

  /// Advanced search recipes (feature 3)
  static Future<List<Map<String, dynamic>>> searchRecipesAdvanced({
    String query = '',
    int? minCalories,
    int? maxCalories,
    List<String> ingredients = const [],
    int? categoryId,
    String? difficulty,
    int limit = 20,
    int offset = 0,
  }) async {
    try {
      final params = <String>['limit=$limit', 'offset=$offset'];

      if (query.trim().isNotEmpty) {
        params.add('q=${Uri.encodeComponent(query.trim())}');
      }
      if (minCalories != null) {
        params.add('min_calories=$minCalories');
      }
      if (maxCalories != null) {
        params.add('max_calories=$maxCalories');
      }
      if (ingredients.isNotEmpty) {
        final cleanIngredients = ingredients
            .map((e) => e.trim())
            .where((e) => e.isNotEmpty)
            .toList();
        if (cleanIngredients.isNotEmpty) {
          params.add(
              'ingredients=${Uri.encodeComponent(cleanIngredients.join(','))}');
        }
      }
      if (categoryId != null) {
        params.add('category_id=$categoryId');
      }
      if (difficulty != null && difficulty.trim().isNotEmpty) {
        params.add('difficulty=${Uri.encodeComponent(difficulty.trim())}');
      }

      final endpoint = '/recipes/search?${params.join('&')}';
      final response = await ApiService.get(endpoint);

      if (response['success'] == true) {
        final recipes = response['data'] as List;
        return recipes.map((e) => Map<String, dynamic>.from(e)).toList();
      } else {
        throw Exception(response['message'] ?? 'Failed to search recipes');
      }
    } catch (e) {
      debugPrint('RecipeClient Error (searchRecipes): $e');
      return [];
    }
  }

  /// Create new recipe
  static Future<Map<String, dynamic>?> createRecipe({
    required String userId,
    required String title,
    required String description,
    required int categoryId,
    required List<String> ingredients,
    required List<String> steps,
    int? cookingTime,
    int? servings,
    String? difficulty,
    List<int>? tags,
    String? imagePath,
  }) async {
    try {
      final data = {
        'user_id': userId,
        'title': title,
        'description': description,
        'category_id': categoryId,
        'ingredients': ingredients,
        'steps': steps,
        if (cookingTime != null) 'cooking_time': cookingTime,
        if (servings != null) 'servings': servings,
        if (difficulty != null) 'difficulty': difficulty,
        if (tags != null) 'tags': tags,
      };

      Map<String, dynamic> response;

      if (imagePath != null) {
        // Upload with image
        response = await ApiService.uploadImage(
          '/recipes',
          imagePath,
          fields: {
            'user_id': userId,
            'title': title,
            'description': description,
            'category_id': categoryId.toString(),
            'ingredients': jsonEncode(ingredients),
            'steps': jsonEncode(steps),
            if (tags != null) 'tags': jsonEncode(tags),
            if (cookingTime != null) 'cooking_time': cookingTime.toString(),
            if (servings != null) 'servings': servings.toString(),
            if (difficulty != null) 'difficulty': difficulty,
          },
        );
      } else {
        // Upload without image
        response = await ApiService.post('/recipes', data);
      }

      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Failed to create recipe');
      }
    } catch (e) {
      debugPrint('RecipeClient Error (createRecipe): $e');
      return null;
    }
  }

  /// Update recipe
  static Future<Map<String, dynamic>?> updateRecipe({
    required String id,
    String? title,
    String? description,
    int? categoryId,
    List<String>? ingredients,
    List<String>? steps,
    int? cookingTime,
    int? servings,
    String? difficulty,
    List<int>? tags,
    String? imagePath,
  }) async {
    try {
      final data = <String, dynamic>{};

      if (title != null) data['title'] = title;
      if (description != null) data['description'] = description;
      if (categoryId != null) data['category_id'] = categoryId;
      if (ingredients != null) data['ingredients'] = ingredients;
      if (steps != null) data['steps'] = steps;
      if (cookingTime != null) data['cooking_time'] = cookingTime;
      if (servings != null) data['servings'] = servings;
      if (difficulty != null) data['difficulty'] = difficulty;
      if (tags != null) data['tags'] = tags;

      final response = await ApiService.put('/recipes/$id', data);

      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Failed to update recipe');
      }
    } catch (e) {
      debugPrint('RecipeClient Error (updateRecipe): $e');
      return null;
    }
  }

  /// Delete recipe
  static Future<bool> deleteRecipe(String id) async {
    try {
      final response = await ApiService.delete('/recipes/$id');
      return response['success'] == true;
    } catch (e) {
      debugPrint('RecipeClient Error (deleteRecipe): $e');
      return false;
    }
  }

  /// Approve recipe (admin only)
  static Future<bool> approveRecipe(String id) async {
    try {
      final response = await ApiService.post('/recipes/$id/approve', {});
      return response['success'] == true;
    } catch (e) {
      debugPrint('RecipeClient Error (approveRecipe): $e');
      return false;
    }
  }

  /// Reject recipe (admin only)
  static Future<bool> rejectRecipe(String id, String reason) async {
    try {
      final response = await ApiService.post('/recipes/$id/reject', {
        'reason': reason,
      });
      return response['success'] == true;
    } catch (e) {
      debugPrint('RecipeClient Error (rejectRecipe): $e');
      return false;
    }
  }
}