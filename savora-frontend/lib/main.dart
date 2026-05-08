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

  // Load environment variables
  await dotenv.load(fileName: '.env');

  // Initialize Supabase
  final supabaseUrl    = dotenv.env['SUPABASE_URL'];
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
  // Muat token yang tersimpan dari SharedPreferences
  final saved = await AuthStorage.load();
  if (saved.token != null && saved.userId != null) {
    ApiService.setToken(saved.token!);
    ApiService.setCurrentUserId(saved.userId!);
    debugPrint('Session restored for user: ${saved.userId}');

    // Validasi token yang dipulihkan dengan melakukan request ke endpoint yang memerlukan autentikasi
    try {
      await ApiService.get('/auth/me');
      debugPrint('Restored Sanctum token is valid');
    } catch (e) {
      final message = e.toString().toLowerCase();
      final isUnauthorized = message.contains('unauthenticated') ||
          message.contains('401') ||
          message.contains('invalid');

      if (isUnauthorized) {
        debugPrint('Restored token invalid on current backend. Clearing local auth...');
        ApiService.clearToken();
        await AuthStorage.clear();
      } else {
        debugPrint('Token validation skipped due to non-auth error: $e');
      }
    }
  }
  // ─────────────────────────────────────────────────────────

  // Initialize notification service
  debugPrint('Initializing notification service...');
  await NotificationService().initialize();
  debugPrint('Notification service initialized');

  await AppSettingsService.load();

  // Test koneksi ke Laravel backend (hanya untuk debug)
  if (const bool.fromEnvironment('dart.vm.product') == false) {
    final connected = await ApiService.healthCheck();
    debugPrint('Laravel backend connected: $connected');
  }

  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  Uri? _initialDeepLink;

  @override
  void initState() {
    super.initState();
    debugPrint('MyApp initState called');
    _loadInitialDeepLink();
    _handleIncomingDeepLinks();
  }

  Future<void> _loadInitialDeepLink() async {
    try {
      final appLinks     = AppLinks();
      final initialUri   = await appLinks.getInitialLink();
      if (initialUri != null) {
        debugPrint('Initial deep link found: $initialUri');
        setState(() {
          _initialDeepLink = initialUri;
        });
      }
    } catch (e) {
      debugPrint('Error handling initial deep link: $e');
    }
  }

  void _handleIncomingDeepLinks() {
    final appLinks = AppLinks();
    appLinks.uriLinkStream.listen(
      (Uri uri) {
        debugPrint('Incoming deep link: $uri');
        _navigateByUri(uri);
      },
      onError: (err) {
        debugPrint('Error on deep link stream: $err');
      },
    );
  }

  void _navigateByUri(Uri uri) {
    if (uri.scheme != 'savora') return;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (uri.host == 'recipe' && uri.pathSegments.isNotEmpty) {
        final recipeId = uri.pathSegments[0];
        debugPrint('Navigating to recipe: $recipeId');
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => DetailScreen(recipeId: recipeId)),
          (route) => false,
        );
      } else if (uri.host == 'profile' && uri.pathSegments.isNotEmpty) {
        final userId = uri.pathSegments[0];
        debugPrint('Navigating to profile: $userId');
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => ProfileScreen(userId: userId)),
          (route) => false,
        );
      } else if (uri.host == 'search') {
        debugPrint('Navigating to search');
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const SearchingScreen()),
          (route) => false,
        );
      } else if (uri.host == 'home') {
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const HomeScreen()),
          (route) => false,
        );
      } else if (uri.host == 'settings') {
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const SettingsScreen()),
          (route) => false,
        );
      }
    });
  }

  Widget _getInitialScreen() {
    // Jika ada deep link, abaikan home screen biasa
    if (_initialDeepLink != null) {
      if (_initialDeepLink!.host == 'recipe' && _initialDeepLink!.pathSegments.isNotEmpty) {
        return DetailScreen(recipeId: _initialDeepLink!.pathSegments[0]);
      } else if (_initialDeepLink!.host == 'profile' && _initialDeepLink!.pathSegments.isNotEmpty) {
        return ProfileScreen(userId: _initialDeepLink!.pathSegments[0]);
      } else if (_initialDeepLink!.host == 'search') {
        return const SearchingScreen();
      } else if (_initialDeepLink!.host == 'settings') {
        return const SettingsScreen();
      }
    }
    // Fallback ke home/login screen
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
        final textScale = appSettings.fontSize / 14;

        return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'Savora',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: appSettings.isDarkMode ? Brightness.dark : Brightness.light,
        scaffoldBackgroundColor:
            appSettings.isDarkMode ? const Color(0xFF101418) : const Color(0xFFF5F7FA),
        cardColor: appSettings.isDarkMode ? const Color(0xFF182027) : Colors.white,
        appBarTheme: AppBarTheme(
          backgroundColor: appSettings.isDarkMode ? const Color(0xFF182027) : Colors.white,
          foregroundColor: appSettings.isDarkMode ? Colors.white : const Color(0xFF264653),
          surfaceTintColor: Colors.transparent,
        ),
        primarySwatch: Colors.orange,
        useMaterial3: true,
      ),
      builder: (context, child) {
        return MediaQuery(
          data: MediaQuery.of(context).copyWith(
            textScaler: TextScaler.linear(textScale.clamp(0.85, 1.3).toDouble()),
          ),
          child: child ?? const SizedBox.shrink(),
        );
      },
      // ── Gunakan deep link untuk initial screen jika ada ──
      home: _getInitialScreen(),
      onGenerateRoute: (settings) {
        debugPrint('Route requested: ${settings.name}');

        final uri = Uri.parse(settings.name ?? '');

        if (uri.scheme == 'savora' && uri.host == 'recipe') {
          final recipeId =
              uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          if (recipeId != null) {
            return MaterialPageRoute(
              builder: (context) => DetailScreen(recipeId: recipeId),
            );
          }
        }

        if (uri.scheme == 'savora' && uri.host == 'profile') {
          final userId =
              uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          if (userId != null) {
            return MaterialPageRoute(
              builder: (context) => ProfileScreen(userId: userId),
            );
          }
        }

        if (uri.scheme == 'savora' && uri.host == 'search') {
          return MaterialPageRoute(
            builder: (context) => const SearchingScreen(),
          );
        }

        if (uri.scheme == 'savora' && uri.host == 'home') {
          return MaterialPageRoute(
            builder: (context) => const HomeScreen(),
          );
        }

        if (uri.scheme == 'savora' && uri.host == 'settings') {
          return MaterialPageRoute(
            builder: (context) => const SettingsScreen(),
          );
        }

        if (settings.name == '/recipe') {
          final recipeId = settings.arguments as String?;
          if (recipeId != null) {
            return MaterialPageRoute(
              builder: (context) => DetailScreen(recipeId: recipeId),
            );
          }
        } else if (settings.name == '/profile') {
          final userId = settings.arguments as String?;
          if (userId != null) {
            return MaterialPageRoute(
              builder: (context) => ProfileScreen(userId: userId),
            );
          }
        } else if (settings.name == '/settings') {
          return MaterialPageRoute(
            builder: (context) => const SettingsScreen(),
          );
        }

            return MaterialPageRoute(builder: (context) => const HomeScreen());
          },
        );
      },
    );
  }
}
