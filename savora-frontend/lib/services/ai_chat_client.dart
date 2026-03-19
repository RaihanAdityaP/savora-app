import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk AI Chat, History, dan Settings
/// Menggantikan AIClient lama
class AIChatClient {
  // CONVERSATIONS

  /// List semua conversations milik user
  static Future<List<Map<String, dynamic>>> listConversations({
    int limit = 20,
    int offset = 0,
    String search = '',
  }) async {
    try {
      String endpoint = '/ai/conversations?limit=$limit&offset=$offset';
      if (search.trim().isNotEmpty) {
        endpoint += '&search=${Uri.encodeComponent(search.trim())}';
      }
      final response = await ApiService.get(endpoint);
      if (response['success'] == true) {
        return List<Map<String, dynamic>>.from(response['data'] ?? []);
      }
      return [];
    } catch (e) {
      debugPrint('AIChatClient.listConversations error: $e');
      return [];
    }
  }

  /// Buat conversation baru
  static Future<Map<String, dynamic>?> createConversation({
    String title = 'New Chat',
    String? provider,
    String? model,
  }) async {
    try {
      final body = <String, dynamic>{'title': title};
      if (provider != null) body['provider'] = provider;
      if (model != null) body['model'] = model;

      final response = await ApiService.post('/ai/conversations', body);
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('AIChatClient.createConversation error: $e');
      return null;
    }
  }

  /// Get conversation detail + messages
  static Future<Map<String, dynamic>?> getConversation(String id) async {
    try {
      final response = await ApiService.get('/ai/conversations/$id');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('AIChatClient.getConversation error: $e');
      return null;
    }
  }

  /// Update judul conversation
  static Future<bool> updateConversationTitle(String id, String title) async {
    try {
      final response = await ApiService.put(
        '/ai/conversations/$id',
        {'title': title},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIChatClient.updateConversationTitle error: $e');
      return false;
    }
  }

  /// Hapus satu conversation
  static Future<bool> deleteConversation(String id) async {
    try {
      final response = await ApiService.delete('/ai/conversations/$id');
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIChatClient.deleteConversation error: $e');
      return false;
    }
  }

  /// Hapus semua conversations
  static Future<bool> deleteAllConversations() async {
    try {
      final response = await ApiService.delete('/ai/conversations');
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIChatClient.deleteAllConversations error: $e');
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // MESSAGES
  // ─────────────────────────────────────────────

  /// Kirim pesan dan dapatkan balasan AI
  /// Returns map berisi user_message dan assistant_message
  static Future<Map<String, dynamic>?> sendMessage({
    required String conversationId,
    required String content,
    String? imageUrl,
  }) async {
    try {
      final body = <String, dynamic>{'content': content};
      if (imageUrl != null) body['image_url'] = imageUrl;

      final response = await ApiService.post(
        '/ai/conversations/$conversationId/messages',
        body,
      );
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      throw Exception(response['message'] ?? 'Failed to send message');
    } catch (e) {
      debugPrint('AIChatClient.sendMessage error: $e');
      rethrow;
    }
  }

  /// Get semua messages dari conversation
  static Future<List<Map<String, dynamic>>> getMessages(
    String conversationId,
  ) async {
    try {
      final response =
          await ApiService.get('/ai/conversations/$conversationId/messages');
      if (response['success'] == true) {
        return List<Map<String, dynamic>>.from(response['data'] ?? []);
      }
      return [];
    } catch (e) {
      debugPrint('AIChatClient.getMessages error: $e');
      return [];
    }
  }

  // ─────────────────────────────────────────────
  // SETTINGS
  // ─────────────────────────────────────────────

  /// Get AI settings user
  static Future<Map<String, dynamic>?> getSettings() async {
    try {
      final response = await ApiService.get('/ai/settings');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('AIChatClient.getSettings error: $e');
      return null;
    }
  }

  /// Simpan AI settings
  static Future<bool> saveSettings({
    required String activeProvider,
    String groqModel = 'llama-3.3-70b-versatile',
    String openRouterModel = 'meta-llama/llama-3.3-70b-instruct:free',
    String? openRouterApiKey,
  }) async {
    try {
      final body = <String, dynamic>{
        'is_active_provider': activeProvider,
        'groq_model': groqModel,
        'openrouter_model': openRouterModel,
      };
      if (openRouterApiKey != null && openRouterApiKey.isNotEmpty) {
        body['openrouter_api_key'] = openRouterApiKey;
      }

      final response = await ApiService.post('/ai/settings', body);
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIChatClient.saveSettings error: $e');
      return false;
    }
  }

  /// Test koneksi ke provider
  static Future<Map<String, dynamic>> testConnection({
    required String provider,
    required String model,
    String? openRouterApiKey,
  }) async {
    try {
      final body = <String, dynamic>{
        'provider': provider,
        'model': model,
      };
      if (openRouterApiKey != null && openRouterApiKey.isNotEmpty) {
        body['openrouter_api_key'] = openRouterApiKey;
      }

      final response = await ApiService.post('/ai/settings/test', body);
      return {
        'success': response['success'] == true,
        'message': response['message'] ?? '',
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Reset settings ke default
  static Future<bool> resetSettings() async {
    try {
      final response = await ApiService.delete('/ai/settings');
      return response['success'] == true;
    } catch (e) {
      debugPrint('AIChatClient.resetSettings error: $e');
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // MODELS
  // ─────────────────────────────────────────────

  /// Get daftar model tersedia
  static Future<List<Map<String, dynamic>>> getAvailableModels({
    String? provider,
  }) async {
    try {
      String endpoint = '/ai/models';
      if (provider != null) endpoint += '?provider=$provider';
      final response = await ApiService.get(endpoint);
      if (response['success'] == true) {
        return List<Map<String, dynamic>>.from(response['data'] ?? []);
      }
      return [];
    } catch (e) {
      debugPrint('AIChatClient.getAvailableModels error: $e');
      return [];
    }
  }

  // ─────────────────────────────────────────────
  // LEGACY - backward compat dengan AIClient lama
  // Dipakai oleh AIController endpoint lama (/api/ai/ask dll)
  // ─────────────────────────────────────────────

  static Future<String> askCookingQuestion(
    String question,
    String recipeContext,
  ) async {
    try {
      final response = await ApiService.post('/ai/ask', {
        'question': question,
        'recipe_context': recipeContext,
      });
      if (response['success'] == true) {
        return response['data']['answer'] ?? 'No answer received';
      }
      throw Exception(response['message'] ?? 'Failed to get answer');
    } catch (e) {
      debugPrint('AIChatClient.askCookingQuestion error: $e');
      rethrow;
    }
  }

  static Future<String> analyzeRecipeFromImage(String imagePath) async {
    try {
      final response = await ApiService.uploadImage(
        '/ai/analyze-image',
        imagePath,
      );
      if (response['success'] == true) {
        return response['data']['analysis'] ?? 'No analysis received';
      }
      throw Exception(response['message'] ?? 'Failed to analyze image');
    } catch (e) {
      debugPrint('AIChatClient.analyzeRecipeFromImage error: $e');
      rethrow;
    }
  }
}