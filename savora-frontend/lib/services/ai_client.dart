import 'api_service.dart';
import 'package:flutter/foundation.dart';

/// Client untuk berkomunikasi dengan Laravel AI Service
/// Menggantikan lib/services/ai_service.dart yang lama
class AIClient {
  /// 1. Ask cooking question
  static Future<String> askCookingQuestion(
    String question,
    String recipeContext,
  ) async {
    try {
      debugPrint('AIClient: Asking cooking question...');

      final response = await ApiService.post('/ai/ask', {
        'question': question,
        'recipe_context': recipeContext,
      });

      if (response['success'] == true) {
        return response['data']['answer'] ?? 'No answer received';
      } else {
        throw Exception(response['message'] ?? 'Failed to get answer');
      }
    } catch (e) {
      debugPrint('AIClient Error (ask): $e');
      rethrow;
    }
  }

  /// 2. Analyze recipe from image
  static Future<String> analyzeRecipeFromImage(String imagePath) async {
    try {
      debugPrint('AIClient: Analyzing image...');

      final response = await ApiService.uploadImage(
        '/ai/analyze-image',
        imagePath,
      );

      if (response['success'] == true) {
        return response['data']['analysis'] ?? 'No analysis received';
      } else {
        throw Exception(response['message'] ?? 'Failed to analyze image');
      }
    } catch (e) {
      debugPrint('AIClient Error (analyze): $e');
      rethrow;
    }
  }

  /// 3. Suggest recipes based on ingredients
  static Future<List<Map<String, dynamic>>> suggestRecipes({
    required List<String> ingredients,
    String? cuisine,
    String? difficulty,
  }) async {
    try {
      debugPrint('AIClient: Suggesting recipes...');

      final response = await ApiService.post('/ai/suggest-recipes', {
        'ingredients': ingredients,
        if (cuisine != null) 'cuisine': cuisine,
        if (difficulty != null) 'difficulty': difficulty,
      });

      if (response['success'] == true) {
        final suggestions = response['data']['suggestions'] as List;
        return suggestions.map((e) => Map<String, dynamic>.from(e)).toList();
      } else {
        throw Exception(response['message'] ?? 'Failed to get suggestions');
      }
    } catch (e) {
      debugPrint('AIClient Error (suggest): $e');
      return [];
    }
  }

  /// 4. Generate recipe from description
  static Future<Map<String, dynamic>> generateRecipe(String description) async {
    try {
      debugPrint('AIClient: Generating recipe...');

      final response = await ApiService.post('/ai/generate-recipe', {
        'description': description,
      });

      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']['recipe']);
      } else {
        throw Exception(response['message'] ?? 'Failed to generate recipe');
      }
    } catch (e) {
      debugPrint('AIClient Error (generate): $e');
      return {
        'title': 'Error',
        'description': 'Gagal generate resep',
        'ingredients': [],
        'steps': [],
      };
    }
  }

  /// 5. Suggest recipe variations
  static Future<List<String>> suggestVariations(String recipeTitle) async {
    try {
      debugPrint('AIClient: Suggesting variations...');

      final response = await ApiService.post('/ai/suggest-variations', {
        'recipe_title': recipeTitle,
      });

      if (response['success'] == true) {
        final variations = response['data']['variations'] as List;
        return variations.map((e) => e.toString()).toList();
      } else {
        throw Exception(response['message'] ?? 'Failed to get variations');
      }
    } catch (e) {
      debugPrint('AIClient Error (variations): $e');
      return [];
    }
  }

  /// Test AI connection
  static Future<bool> testConnection() async {
    try {
      debugPrint('AIClient: Testing connection...');

      final response = await ApiService.get('/ai/test');
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIClient connection test failed: $e');
      return false;
    }
  }
}