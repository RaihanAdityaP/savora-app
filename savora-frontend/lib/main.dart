import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'dart:convert';
import 'firebase_options.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:app_links/app_links.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'screens/auth/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/recipes/detail_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/searching_screen.dart';
import 'screens/settings_screen.dart';
import 'services/api_service.dart';
import 'services/app_settings_service.dart';
import 'services/auth_client.dart';
import 'services/auth_storage.dart';
import 'services/notification_service.dart';
import 'services/recipe_client.dart';

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  debugPrint('Starting Savora app...');

  await dotenv.load(fileName: '.env');

  final supabaseUrl     = dotenv.env['SUPABASE_URL'];
  final supabaseAnonKey = dotenv.env['SUPABASE_ANON_KEY'];

  if (supabaseUrl == null ||
      supabaseUrl.isEmpty ||
      supabaseAnonKey == null ||
      supabaseAnonKey.isEmpty) {
    throw Exception(
      'SUPABASE_URL / SUPABASE_ANON_KEY belum di-set di file .env',
    );
  }

  await Supabase.initialize(url: supabaseUrl, anonKey: supabaseAnonKey);
  debugPrint('Supabase initialized');

  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    FirebaseMessaging.onBackgroundMessage(
      savoraFirebaseMessagingBackgroundHandler,
    );
    debugPrint('Firebase initialized');
  } catch (e) {
    debugPrint('Firebase init skipped/failed: $e');
  }

  // ── RESTORE SESSION ──────────────────────────────────────
  final saved = await AuthStorage.load();
  if (saved.token != null && saved.userId != null) {
    ApiService.setToken(saved.token!);
    ApiService.setCurrentUserId(saved.userId!);
    debugPrint('Session restored for user: ${saved.userId}');

    try {
      await ApiService.get('/auth/me');
      debugPrint('Restored Sanctum token is valid');
    } catch (e) {
      final message = e.toString().toLowerCase();
      final isUnauthorized = message.contains('unauthenticated') ||
          message.contains('401') ||
          message.contains('invalid');

      if (isUnauthorized) {
        debugPrint('Restored token invalid. Trying silent Supabase restore...');
        final restored = await AuthClient.restoreFromSupabaseSession();
        if (!restored) {
          debugPrint('Silent restore failed. Clearing local auth...');
          ApiService.clearToken();
          await AuthStorage.clear();
        }
      } else {
        debugPrint('Token validation skipped due to non-auth error: $e');
      }
    }
  } else {
    final restored = await AuthClient.restoreFromSupabaseSession();
    if (restored) {
      debugPrint('Session restored from Supabase without saved Sanctum token');
    }
  }
  // ─────────────────────────────────────────────────────────

  debugPrint('Initializing notification service...');
  await NotificationService().initialize();
  debugPrint('Notification service initialized');

  await AppSettingsService.load();

  if (const bool.fromEnvironment('dart.vm.product') == false) {
    final connected = await ApiService.healthCheck();
    debugPrint('Laravel backend connected: $connected');
  }

  runApp(const MyApp());
}

class _DeepLinkParser {
  /// Kembalikan [_DeepLinkTarget] dari URI apapun, atau null kalau tidak dikenal.
  static _DeepLinkTarget? parse(Uri uri) {
    // ── 1. Custom scheme: savora:// ──────────────────────
    if (uri.scheme == 'savora') {
      switch (uri.host) {
        case 'recipe':
        case 'recipes':
          final id = uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          const blockedRecipeSlugs = {'create', 'new', 'edit'};
          if (id != null && id.isNotEmpty && !blockedRecipeSlugs.contains(id.toLowerCase())) {
            return _DeepLinkTarget.recipe(id);
          }
          break;
        case 'profile':
          final id = uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          if (id != null && id.isNotEmpty) {
            return _DeepLinkTarget.profile(id);
          }
          break;
        case 'search':
          return _DeepLinkTarget.search();
        case 'home':
          return _DeepLinkTarget.home();
        case 'settings':
          return _DeepLinkTarget.settings();
      }
      return null;
    }

    // ── 2. HTTPS App Links ───────────────────────────────
    const railwayHost = 'savora-app.up.railway.app';
    if ((uri.scheme == 'https' || uri.scheme == 'http') &&
        uri.host == railwayHost) {
      final segments = uri.pathSegments;

      // /r/{id} — share link resep (path khusus, tidak bentrok dengan /recipes/create dll)
      if (segments.length >= 2 && segments[0] == 'r') {
        final id = segments[1];
        if (id.isNotEmpty) return _DeepLinkTarget.recipe(id);
      }

      // /p/{id} adalah share link profile; /profile/{id} tetap diterima sebagai fallback lama.
      if (segments.length >= 2 && (segments[0] == 'p' || segments[0] == 'profile')) {
        final id = segments[1];
        if (id.isNotEmpty) return _DeepLinkTarget.profile(id);
      }

      // /s adalah app-link search; /search tetap diterima sebagai fallback lama.
      if (segments.isNotEmpty && (segments[0] == 's' || segments[0] == 'search')) {
        return _DeepLinkTarget.search();
      }
    }

    return null;
  }
}

// Simple sealed-class-like target
class _DeepLinkTarget {
  final _DeepLinkType type;
  final String? id;

  const _DeepLinkTarget._(this.type, [this.id]);

  factory _DeepLinkTarget.recipe(String id)  => _DeepLinkTarget._(_DeepLinkType.recipe, id);
  factory _DeepLinkTarget.profile(String id) => _DeepLinkTarget._(_DeepLinkType.profile, id);
  factory _DeepLinkTarget.search()           => const _DeepLinkTarget._(_DeepLinkType.search);
  factory _DeepLinkTarget.home()             => const _DeepLinkTarget._(_DeepLinkType.home);
  factory _DeepLinkTarget.settings()        => const _DeepLinkTarget._(_DeepLinkType.settings);
}

enum _DeepLinkType { recipe, profile, search, home, settings }

// ══════════════════════════════════════════════════════════════════════════════
// APP
// ══════════════════════════════════════════════════════════════════════════════
class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  static const int _fallbackBuildNumber = 20260205;

  /// Link yang diterima saat app belum berjalan (cold start).
  _DeepLinkTarget? _initialTarget;
  bool _isCheckingVersion = true;
  bool _forceUpdate = false;
  String _updateMessage = 'A new Savora update is available. Please update to continue.';
  String _updateUrl = 'https://savora-app.up.railway.app/';

  @override
  void initState() {
    super.initState();
    NotificationService.onNotificationTapped = _handleNotificationPayload;
    _checkAppVersion();
    _loadInitialDeepLink();
    _listenIncomingDeepLinks();
  }

  int get _currentBuildNumber {
    const dartDefineBuild = String.fromEnvironment('APP_BUILD_NUMBER');
    final raw = dartDefineBuild.isNotEmpty
        ? dartDefineBuild
        : (dotenv.env['APP_BUILD_NUMBER'] ?? '$_fallbackBuildNumber');
    return int.tryParse(raw) ?? _fallbackBuildNumber;
  }

  Future<void> _checkAppVersion() async {
    try {
      final response = await ApiService.get('/app-version');
      final data = Map<String, dynamic>.from(response['data'] ?? {});
      final minSupportedBuild = (data['min_supported_build'] as num?)?.toInt() ?? 0;
      final forceUpdate = data['force_update'] == true;
      final shouldBlock = forceUpdate && _currentBuildNumber < minSupportedBuild;

      if (!mounted) return;
      setState(() {
        _forceUpdate = shouldBlock;
        _updateMessage = data['message']?.toString() ?? _updateMessage;
        _updateUrl = data['download_url']?.toString() ??
            data['landing_url']?.toString() ??
            _updateUrl;
        _isCheckingVersion = false;
      });
    } catch (e) {
      debugPrint('App version check skipped: $e');
      if (mounted) setState(() => _isCheckingVersion = false);
    }
  }

  void _handleNotificationPayload(String? payload, {String? actionId}) {
    if (payload == null || payload.isEmpty) return;

    try {
      final data = jsonDecode(payload);
      if (data is! Map) return;

      final route = data['route']?.toString();
      final id = data['id']?.toString();

      if (actionId == NotificationService.actionLikeRecipe &&
          route == 'recipe' &&
          id != null &&
          id.isNotEmpty) {
        RecipeClient.toggleLike(id);
        return;
      }

      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (route == 'recipe' && id != null && id.isNotEmpty) {
          _navigateTo(_DeepLinkTarget.recipe(id));
        } else if (route == 'profile' && id != null && id.isNotEmpty) {
          _navigateTo(_DeepLinkTarget.profile(id));
        } else if (route == 'home') {
          _navigateTo(_DeepLinkTarget.home());
        }
      });
    } catch (e) {
      debugPrint('Invalid notification payload: $e');
    }
  }

  // ── Cold start ───────────────────────────────────────────
  Future<void> _loadInitialDeepLink() async {
    try {
      final appLinks = AppLinks();
      final uri      = await appLinks.getInitialLink();
      if (uri == null) return;

      debugPrint('Initial deep link: $uri');
      final target = _DeepLinkParser.parse(uri);
      if (target != null) {
        setState(() => _initialTarget = target);
      }
    } catch (e) {
      debugPrint('Error reading initial deep link: $e');
    }
  }

  // ── Hot / warm start (app sudah buka) ────────────────────
  void _listenIncomingDeepLinks() {
    AppLinks().uriLinkStream.listen(
      (uri) {
        debugPrint('Incoming deep link: $uri');
        final target = _DeepLinkParser.parse(uri);
        if (target == null) return;

        // Tunggu sampai frame siap baru navigate
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _navigateTo(target);
        });
      },
      onError: (e) => debugPrint('Deep link stream error: $e'),
    );
  }

  // ── Navigator helper ─────────────────────────────────────
  void _navigateTo(_DeepLinkTarget target) {
    final nav = navigatorKey.currentState;
    if (nav == null) return;

    Widget screen;
    switch (target.type) {
      case _DeepLinkType.recipe:
        screen = DetailScreen(recipeId: target.id!);
        break;
      case _DeepLinkType.profile:
        screen = ProfileScreen(userId: target.id!);
        break;
      case _DeepLinkType.search:
        screen = const SearchingScreen();
        break;
      case _DeepLinkType.settings:
        screen = const SettingsScreen();
        break;
      case _DeepLinkType.home:
        screen = const HomeScreen();
        break;
    }

    nav.push(MaterialPageRoute(builder: (_) => screen));
  }

  // ── Initial screen ───────────────────────────────────────
  Widget _buildInitialScreen() {
    if (_isCheckingVersion) {
      return const _StartupLoadingScreen();
    }

    if (_forceUpdate) {
      return _ForceUpdateScreen(message: _updateMessage, updateUrl: _updateUrl);
    }

    if (_initialTarget != null) {
      switch (_initialTarget!.type) {
        case _DeepLinkType.recipe:
          return DetailScreen(recipeId: _initialTarget!.id!);
        case _DeepLinkType.profile:
          return ProfileScreen(userId: _initialTarget!.id!);
        case _DeepLinkType.search:
          return const SearchingScreen();
        case _DeepLinkType.settings:
          return const SettingsScreen();
        case _DeepLinkType.home:
          break;
      }
    }

    return ApiService.hasToken ? const HomeScreen() : const LoginScreen();
  }

  @override
  void dispose() {
    NotificationService().dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<AppSettings>(
      valueListenable: AppSettingsService.notifier,
      builder: (context, appSettings, _) {
        final textScale = (appSettings.fontSize / 14).clamp(0.85, 1.3);
        final textColor = appSettings.isDarkMode
            ? Colors.white
            : const Color(0xFF264653);
        final secondaryTextColor = appSettings.isDarkMode
            ? Colors.white70
            : const Color(0xFF6B7280);
        final backgroundColor = appSettings.isDarkMode
            ? const Color(0xFF0F1318)
            : const Color(0xFFF5F7FA);
        final surfaceColor = appSettings.isDarkMode
            ? const Color(0xFF1A2330)
            : Colors.white;
        final subtleSurfaceColor = appSettings.isDarkMode
            ? const Color(0xFF222C35)
            : Colors.grey.shade100;
        final borderColor = appSettings.isDarkMode
            ? Colors.white.withValues(alpha: 0.12)
            : Colors.grey.shade200;

        return MaterialApp(
          navigatorKey: navigatorKey,
          title: 'Savora',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            brightness:
                appSettings.isDarkMode ? Brightness.dark : Brightness.light,
            scaffoldBackgroundColor: backgroundColor,
            canvasColor: backgroundColor,
            cardColor: surfaceColor,
            dividerColor: borderColor,
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xFFE76F51),
              brightness:
                  appSettings.isDarkMode ? Brightness.dark : Brightness.light,
              surface: surfaceColor,
            ),
            appBarTheme: AppBarTheme(
              backgroundColor: surfaceColor,
              foregroundColor: appSettings.isDarkMode
                  ? Colors.white
                  : const Color(0xFF264653),
              surfaceTintColor: Colors.transparent,
            ),
            textTheme: ThemeData(
              brightness:
                  appSettings.isDarkMode ? Brightness.dark : Brightness.light,
            ).textTheme.apply(
                  bodyColor: textColor,
                  displayColor: textColor,
                ),
            inputDecorationTheme: InputDecorationTheme(
              hintStyle: TextStyle(color: secondaryTextColor),
              filled: true,
              fillColor: subtleSurfaceColor,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: BorderSide(color: borderColor),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: BorderSide(color: borderColor),
              ),
            ),
            dialogTheme: DialogThemeData(
              backgroundColor: surfaceColor,
              surfaceTintColor: Colors.transparent,
              titleTextStyle: TextStyle(
                color: textColor,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
              contentTextStyle: TextStyle(
                color: secondaryTextColor,
                fontSize: 14,
              ),
            ),
            popupMenuTheme: PopupMenuThemeData(
              color: surfaceColor,
              surfaceTintColor: Colors.transparent,
              textStyle: TextStyle(color: textColor),
            ),
            bottomSheetTheme: BottomSheetThemeData(
              backgroundColor: surfaceColor,
              surfaceTintColor: Colors.transparent,
              modalBackgroundColor: surfaceColor,
              modalBarrierColor: Colors.black.withValues(alpha: 0.55),
            ),
            snackBarTheme: SnackBarThemeData(
              backgroundColor: appSettings.isDarkMode
                  ? const Color(0xFF222C35)
                  : const Color(0xFF264653),
              contentTextStyle: const TextStyle(color: Colors.white),
              behavior: SnackBarBehavior.floating,
            ),
            textButtonTheme: TextButtonThemeData(
              style: TextButton.styleFrom(
                foregroundColor: const Color(0xFFE76F51),
              ),
            ),
            primarySwatch: Colors.orange,
            useMaterial3: true,
          ),
          builder: (context, child) {
            return MediaQuery(
              data: MediaQuery.of(context).copyWith(
                textScaler: TextScaler.linear(textScale.toDouble()),
              ),
              child: child ?? const SizedBox.shrink(),
            );
          },
          home: _buildInitialScreen(),
          onGenerateRoute: (settings) {
            if (settings.name == '/recipe') {
              final id = settings.arguments as String?;
              if (id != null) {
                return MaterialPageRoute(
                  builder: (_) => DetailScreen(recipeId: id),
                );
              }
            }
            if (settings.name == '/profile') {
              final id = settings.arguments as String?;
              if (id != null) {
                return MaterialPageRoute(
                  builder: (_) => ProfileScreen(userId: id),
                );
              }
            }
            if (settings.name == '/settings') {
              return MaterialPageRoute(builder: (_) => const SettingsScreen());
            }
            if (settings.name == '/search') {
              return MaterialPageRoute(builder: (_) => const SearchingScreen());
            }
            return MaterialPageRoute(builder: (_) => const HomeScreen());
          },
        );
      },
    );
  }
}

class _StartupLoadingScreen extends StatelessWidget {
  const _StartupLoadingScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppSettingsService.current.isDarkMode
          ? const Color(0xFF0F1318)
          : const Color(0xFFF5F7FA),
      body: Center(
        child: CircularProgressIndicator(color: Color(0xFFE76F51)),
      ),
    );
  }
}

class _ForceUpdateScreen extends StatelessWidget {
  final String message;
  final String updateUrl;

  const _ForceUpdateScreen({
    required this.message,
    required this.updateUrl,
  });

  Future<void> _openUpdateUrl() async {
    final uri = Uri.tryParse(updateUrl);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = AppSettingsService.current.isDarkMode;
    return Scaffold(
      backgroundColor:
          isDark ? const Color(0xFF0F1318) : const Color(0xFFF5F7FA),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 86,
                    height: 86,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: [Color(0xFFE76F51), Color(0xFFF4A261)],
                      ),
                    ),
                    child: const Icon(
                      Icons.system_update_alt_rounded,
                      color: Colors.white,
                      size: 42,
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'Update Required',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    message,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: isDark ? Colors.white70 : const Color(0xFF6B7280),
                      fontSize: 15,
                      height: 1.5,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 28),
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: ElevatedButton.icon(
                      onPressed: _openUpdateUrl,
                      icon: const Icon(Icons.open_in_new_rounded),
                      label: const Text('Update Savora'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE76F51),
                        foregroundColor: Colors.white,
                        textStyle: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
