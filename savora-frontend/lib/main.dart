import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'firebase_options.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:app_links/app_links.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'screens/auth/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/recipes/detail_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/searching_screen.dart';
import 'screens/settings_screen.dart';
import 'services/api_service.dart';
import 'services/app_settings_service.dart';
import 'services/auth_storage.dart';
import 'services/notification_service.dart';

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
        debugPrint('Restored token invalid. Clearing local auth...');
        ApiService.clearToken();
        await AuthStorage.clear();
      } else {
        debugPrint('Token validation skipped due to non-auth error: $e');
      }
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
    const railwayHost = 'savora-app-productions.up.railway.app';
    if ((uri.scheme == 'https' || uri.scheme == 'http') &&
        uri.host == railwayHost) {
      final segments = uri.pathSegments;

      // /r/{id} — share link resep (path khusus, tidak bentrok dengan /recipes/create dll)
      if (segments.length >= 2 && segments[0] == 'r') {
        final id = segments[1];
        if (id.isNotEmpty) return _DeepLinkTarget.recipe(id);
      }

      // /profile/{id}
      if (segments.length >= 2 && segments[0] == 'profile') {
        final id = segments[1];
        if (id.isNotEmpty) return _DeepLinkTarget.profile(id);
      }

      // /search
      if (segments.isNotEmpty && segments[0] == 'search') {
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
  /// Link yang diterima saat app belum berjalan (cold start).
  _DeepLinkTarget? _initialTarget;

  @override
  void initState() {
    super.initState();
    _loadInitialDeepLink();
    _listenIncomingDeepLinks();
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

        return MaterialApp(
          navigatorKey: navigatorKey,
          title: 'Savora',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            brightness:
                appSettings.isDarkMode ? Brightness.dark : Brightness.light,
            scaffoldBackgroundColor: appSettings.isDarkMode
                ? const Color(0xFF101418)
                : const Color(0xFFF5F7FA),
            cardColor:
                appSettings.isDarkMode ? const Color(0xFF182027) : Colors.white,
            appBarTheme: AppBarTheme(
              backgroundColor: appSettings.isDarkMode
                  ? const Color(0xFF182027)
                  : Colors.white,
              foregroundColor: appSettings.isDarkMode
                  ? Colors.white
                  : const Color(0xFF264653),
              surfaceTintColor: Colors.transparent,
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