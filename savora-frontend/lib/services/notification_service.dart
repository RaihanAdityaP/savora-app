import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// NotificationService — hanya handle LOCAL notifications di HP.
/// Logic pengiriman notifikasi dari backend ada di Laravel (NotificationService.php).
/// Realtime listener sudah tidak ada karena digantikan FCM push notification.
class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _flutterLocalNotificationsPlugin =
      FlutterLocalNotificationsPlugin();

  bool _isInitialized = false;

  // Callback opsional saat notifikasi di-tap
  // Set ini dari luar jika mau navigasi ke screen tertentu
  static Function(String? payload)? onNotificationTapped;

  // ─────────────────────────────────────────────
  // INITIALIZE
  // ─────────────────────────────────────────────

  Future<void> initialize() async {
    if (_isInitialized) {
      debugPrint('NotificationService already initialized');
      return;
    }

    // Skip untuk web platform
    if (kIsWeb) {
      debugPrint('Web platform — skipping notification initialization.');
      _isInitialized = true;
      return;
    }

    try {
      debugPrint('Starting NotificationService initialization...');

      const AndroidInitializationSettings initializationSettingsAndroid =
          AndroidInitializationSettings('@mipmap/ic_launcher');

      const InitializationSettings initializationSettings =
          InitializationSettings(
        android: initializationSettingsAndroid,
      );

      final bool? initialized =
          await _flutterLocalNotificationsPlugin.initialize(
        initializationSettings,
        onDidReceiveNotificationResponse: _onNotificationTapped,
      );

      debugPrint('Plugin initialization result: $initialized');

      await _requestPermission();
      await _createNotificationChannel();

      _isInitialized = true;
      debugPrint('NotificationService initialized successfully');
    } catch (e) {
      debugPrint('Error initializing NotificationService: $e');
      // Tandai tetap initialized agar tidak loop ulang
      _isInitialized = true;
    }
  }

  // ─────────────────────────────────────────────
  // CREATE ANDROID CHANNEL
  // ─────────────────────────────────────────────

  Future<void> _createNotificationChannel() async {
    if (kIsWeb) return;

    try {
      const AndroidNotificationChannel channel = AndroidNotificationChannel(
        'savora_channel',
        'Savora Notifications',
        description: 'Notifikasi dari aplikasi Savora',
        importance: Importance.max,
        enableVibration: true,
        playSound: true,
        showBadge: true,
      );

      final androidImpl = _flutterLocalNotificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();

      if (androidImpl != null) {
        await androidImpl.createNotificationChannel(channel);
        debugPrint('Notification channel created: ${channel.id}');
      } else {
        debugPrint('Android implementation is NULL');
      }
    } catch (e) {
      debugPrint('Error creating notification channel: $e');
    }
  }

  // ─────────────────────────────────────────────
  // REQUEST PERMISSION (Android 13+)
  // ─────────────────────────────────────────────

  Future<void> _requestPermission() async {
    if (kIsWeb) return;

    try {
      final androidImpl = _flutterLocalNotificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();

      if (androidImpl != null) {
        final bool? granted =
            await androidImpl.requestNotificationsPermission();
        debugPrint('Notification permission granted: $granted');
        if (granted == false) {
          debugPrint('Permission denied. Notifications will not work.');
        }
      }
    } catch (e) {
      debugPrint('Error requesting notification permission: $e');
    }
  }

  // ─────────────────────────────────────────────
  // HANDLE TAP PADA NOTIFIKASI
  // ─────────────────────────────────────────────

  void _onNotificationTapped(NotificationResponse response) {
    final String? payload = response.payload;
    debugPrint('Notification tapped with payload: $payload');

    // Panggil callback jika ada (set dari luar untuk navigasi)
    if (onNotificationTapped != null && payload != null) {
      onNotificationTapped!(payload);
    }
  }

  // ─────────────────────────────────────────────
  // SHOW LOCAL NOTIFICATION
  // Dipanggil saat menerima push notification dari FCM
  // ─────────────────────────────────────────────

  Future<void> showNotification({
    required String title,
    required String body,
    String? payload,
  }) async {
    debugPrint('Attempting to show notification: $title');

    if (kIsWeb) {
      debugPrint('Web platform — notification skipped.');
      return;
    }

    if (!_isInitialized) {
      debugPrint('NotificationService not initialized. Initializing...');
      await initialize();
    }

    try {
      final AndroidNotificationDetails androidDetails =
          AndroidNotificationDetails(
        'savora_channel',
        'Savora Notifications',
        channelDescription: 'Notifikasi dari aplikasi Savora',
        importance: Importance.max,
        priority: Priority.high,
        showWhen: true,
        enableVibration: true,
        playSound: true,
        icon: '@mipmap/ic_launcher',
        color: const Color(0xFFFF6B6B),
        largeIcon: const DrawableResourceAndroidBitmap('@mipmap/ic_launcher'),
        styleInformation: BigTextStyleInformation(
          body,
          htmlFormatBigText: false,
          contentTitle: title,
          htmlFormatContentTitle: false,
          summaryText: 'Savora',
          htmlFormatSummaryText: false,
        ),
        ticker: 'Savora Notification',
        channelShowBadge: true,
        autoCancel: true,
        ongoing: false,
      );

      final NotificationDetails notificationDetails = NotificationDetails(
        android: androidDetails,
      );

      // ID unik tiap notifikasi berdasarkan waktu
      final int id = DateTime.now().millisecondsSinceEpoch.remainder(100000);

      await _flutterLocalNotificationsPlugin.show(
        id,
        title,
        body,
        notificationDetails,
        payload: payload,
      );

      debugPrint('Notification shown successfully');
    } catch (e, stackTrace) {
      debugPrint('Error showing notification: $e');
      debugPrint('Stack trace: $stackTrace');
    }
  }

  // ─────────────────────────────────────────────
  // GETTERS
  // ─────────────────────────────────────────────

  bool get isInitialized => _isInitialized;

  // ─────────────────────────────────────────────
  // DISPOSE
  // ─────────────────────────────────────────────

  void dispose() {
    debugPrint('NotificationService disposed');
    // Tidak ada channel realtime yang perlu di-unsubscribe
    // karena kita sudah tidak pakai Supabase realtime
  }
}