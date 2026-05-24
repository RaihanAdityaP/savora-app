import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_service.dart';
import '../firebase_options.dart';
import 'notification_client.dart';

@pragma('vm:entry-point')
Future<void> savoraFirebaseMessagingBackgroundHandler(
  RemoteMessage message,
) async {
  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
  } catch (_) {}
  await NotificationService.showBackgroundMessage(message);
}

@pragma('vm:entry-point')
void savoraNotificationTapBackground(NotificationResponse response) {
  debugPrint(
    'Background notification action: ${response.actionId} payload: ${response.payload}',
  );
}

/// NotificationService — handle FCM + local notifications di device.
class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _flutterLocalNotificationsPlugin =
      FlutterLocalNotificationsPlugin();

  bool _isInitialized = false;

  static const String androidChannelId = 'savora_high_channel';
  static const String androidChannelName = 'Savora Alerts';
  static const String androidChannelDescription =
      'Important notifications from Savora';
  static const String actionLikeRecipe = 'like_recipe';
  static const String actionReplyRecipe = 'reply_recipe';
  static const String _lastRegisteredTokenKey =
      'savora_last_registered_fcm_token';
  static const String _lastRegisteredUserKey =
      'savora_last_registered_fcm_user';

  // Callback opsional saat notifikasi atau action button di-tap.
  static void Function(String? payload, {String? actionId})?
      onNotificationTapped;

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
        onDidReceiveBackgroundNotificationResponse:
            savoraNotificationTapBackground,
      );

      debugPrint('Plugin initialization result: $initialized');

      await _requestPermission();
      await _createNotificationChannel();
      await _setupFcmListeners();
      await _registerCurrentFcmToken();

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
        androidChannelId,
        androidChannelName,
        description: androidChannelDescription,
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
  // REQUEST PERMISSION (Android 13+ / iOS)
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

      final fcmSettings = await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );
      debugPrint('FCM permission status: ${fcmSettings.authorizationStatus}');
    } catch (e) {
      debugPrint('Error requesting notification permission: $e');
    }
  }

  Future<void> _setupFcmListeners() async {
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final title =
          message.notification?.title ?? message.data['title']?.toString();
      final body =
          message.notification?.body ?? message.data['body']?.toString();

      if (title != null && body != null) {
        showNotification(
          title: title,
          body: body,
          payload: _payloadFromMessage(message),
        );
      }
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _triggerTapCallback(_payloadFromMessage(message));
    });

    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      _triggerTapCallback(_payloadFromMessage(initialMessage));
    }

    FirebaseMessaging.instance.onTokenRefresh.listen((String token) {
      _registerDeviceToken(token);
    });
  }

  String? _payloadFromMessage(RemoteMessage message) {
    if (message.data.isEmpty) return null;
    try {
      return jsonEncode(message.data);
    } catch (_) {
      return null;
    }
  }

  // ─────────────────────────────────────────────
  // HANDLE TAP PADA NOTIFIKASI
  // ─────────────────────────────────────────────

  void _onNotificationTapped(NotificationResponse response) {
    final String? payload = response.payload;
    debugPrint(
      'Notification tapped with payload: $payload action: ${response.actionId}',
    );
    _triggerTapCallback(payload, actionId: response.actionId);
  }

  Future<void> syncDeviceTokenForCurrentUser() async {
    if (kIsWeb) return;

    if (!_isInitialized) {
      await initialize();
      return;
    }

    await _registerCurrentFcmToken();
  }

  void _triggerTapCallback(String? payload, {String? actionId}) {
    if (onNotificationTapped != null && payload != null) {
      onNotificationTapped!(payload, actionId: actionId);
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
      final bool canActOnRecipe = _isRecipePayload(payload);
      final AndroidNotificationDetails androidDetails =
          AndroidNotificationDetails(
        androidChannelId,
        androidChannelName,
        channelDescription: androidChannelDescription,
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
        actions: canActOnRecipe
            ? const <AndroidNotificationAction>[
                AndroidNotificationAction(
                  actionLikeRecipe,
                  'Like',
                  showsUserInterface: true,
                  cancelNotification: true,
                ),
                AndroidNotificationAction(
                  actionReplyRecipe,
                  'Reply',
                  showsUserInterface: true,
                  cancelNotification: true,
                ),
              ]
            : null,
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

  bool _isRecipePayload(String? payload) {
    if (payload == null || payload.isEmpty) return false;
    try {
      final decoded = jsonDecode(payload);
      if (decoded is! Map) return false;
      final route = decoded['route']?.toString();
      final id = decoded['id']?.toString();
      return route == 'recipe' && id != null && id.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  static Future<void> showBackgroundMessage(RemoteMessage message) async {
    if (kIsWeb) return;
    if (message.notification != null) return;

    final title =
        message.notification?.title ?? message.data['title']?.toString();
    final body = message.notification?.body ?? message.data['body']?.toString();

    if (title == null || body == null) return;

    final plugin = FlutterLocalNotificationsPlugin();
    const initializationSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    );

    await plugin.initialize(
      initializationSettings,
      onDidReceiveBackgroundNotificationResponse:
          savoraNotificationTapBackground,
    );

    const channel = AndroidNotificationChannel(
      androidChannelId,
      androidChannelName,
      description: androidChannelDescription,
      importance: Importance.max,
      enableVibration: true,
      playSound: true,
      showBadge: true,
    );

    final androidImpl = plugin.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await androidImpl?.createNotificationChannel(channel);

    final payload = _payloadFromData(message.data);
    final canActOnRecipe = _isRecipePayloadData(message.data);
    final androidDetails = AndroidNotificationDetails(
      androidChannelId,
      androidChannelName,
      channelDescription: androidChannelDescription,
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
        contentTitle: title,
        summaryText: 'Savora',
      ),
      ticker: 'Savora Notification',
      channelShowBadge: true,
      autoCancel: true,
      actions: canActOnRecipe
          ? const <AndroidNotificationAction>[
              AndroidNotificationAction(
                actionLikeRecipe,
                'Like',
                showsUserInterface: true,
                cancelNotification: true,
              ),
              AndroidNotificationAction(
                actionReplyRecipe,
                'Reply',
                showsUserInterface: true,
                cancelNotification: true,
              ),
            ]
          : null,
    );

    await plugin.show(
      DateTime.now().millisecondsSinceEpoch.remainder(100000),
      title,
      body,
      NotificationDetails(android: androidDetails),
      payload: payload,
    );
  }

  static String? _payloadFromData(Map<String, dynamic> data) {
    if (data.isEmpty) return null;
    try {
      return jsonEncode(data);
    } catch (_) {
      return null;
    }
  }

  static bool _isRecipePayloadData(Map<String, dynamic> data) {
    final route = data['route']?.toString();
    final id = data['id']?.toString();
    return route == 'recipe' && id != null && id.isNotEmpty;
  }

  Future<void> _registerCurrentFcmToken() async {
    if (kIsWeb || !ApiService.hasToken) return;

    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null || token.isEmpty) {
        debugPrint('FCM token unavailable, skipping register device.');
        return;
      }

      await _registerDeviceToken(token);
    } catch (e) {
      debugPrint('Failed to register current FCM token: $e');
    }
  }

  Future<void> _registerDeviceToken(String token) async {
    final userId = ApiService.currentUserId;
    if (userId == null || userId.isEmpty) {
      debugPrint('No logged-in user, skip register FCM token.');
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    if (prefs.getString(_lastRegisteredTokenKey) == token &&
        prefs.getString(_lastRegisteredUserKey) == userId) {
      debugPrint('FCM token already registered for this user, skipping.');
      return;
    }

    final success = await NotificationClient.registerDevice(
      userId: userId,
      deviceToken: token,
      deviceType: defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android',
    );

    if (success) {
      await prefs.setString(_lastRegisteredTokenKey, token);
      await prefs.setString(_lastRegisteredUserKey, userId);
    }

    debugPrint('Register FCM token result: $success');
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
  }
}
