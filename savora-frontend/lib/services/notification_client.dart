import 'package:flutter/foundation.dart';
import 'api_service.dart';

/// Client untuk Notification operations
/// Taruh di: lib/services/notification_client.dart
class NotificationClient {
  /// Get semua notifikasi user
  static Future<List<Map<String, dynamic>>> getNotifications(
    String userId,
  ) async {
    try {
      final response =
          await ApiService.get('/notifications');
      if (response['success'] == true) {
        final list = response['data'] as List;
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      debugPrint('NotificationClient.getNotifications error: $e');
      return [];
    }
  }

  /// Hitung notifikasi yang belum dibaca
  static Future<int> getUnreadCount(String userId) async {
    try {
      final response = await ApiService.get(
        '/notifications/unread-count',
      );
      if (response['success'] == true) {
        return response['data']?['count'] ?? 0;
      }
      return 0;
    } catch (e) {
      debugPrint('NotificationClient.getUnreadCount error: $e');
      return 0;
    }
  }

  /// Tandai satu notifikasi sudah dibaca
  static Future<bool> markAsRead(String notificationId) async {
    try {
      final response =
          await ApiService.post('/notifications/$notificationId/read', {});
      return response['success'] == true;
    } catch (e) {
      debugPrint('NotificationClient.markAsRead error: $e');
      return false;
    }
  }

  /// Tandai semua notifikasi sudah dibaca
  static Future<bool> markAllAsRead(String userId) async {
    try {
      final response = await ApiService.post(
        '/notifications/user/read-all',
        {},
      );
      return response['success'] == true;
    } catch (e) {
      debugPrint('NotificationClient.markAllAsRead error: $e');
      return false;
    }
  }

  /// Daftarkan device token FCM ke backend
  /// Panggil ini saat user login atau setiap app dibuka
  static Future<bool> registerDevice({
    required String userId,
    required String deviceToken,
    String deviceType = 'android', // 'android' atau 'ios'
  }) async {
    try {
      final response = await ApiService.post('/notifications/register-device', {
        'user_id': userId,
        'device_token': deviceToken,
        'device_type': deviceType,
      });
      return response['success'] == true;
    } catch (e) {
      debugPrint('NotificationClient.registerDevice error: $e');
      return false;
    }
  }

  /// Hapus notifikasi
  static Future<bool> deleteNotification(String notificationId) async {
    try {
      final response =
          await ApiService.delete('/notifications/$notificationId');
      return response['success'] == true;
    } catch (e) {
      debugPrint('NotificationClient.deleteNotification error: $e');
      return false;
    }
  }
}