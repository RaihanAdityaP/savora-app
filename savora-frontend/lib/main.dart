import 'package:flutter/material.dart';
import 'package:app_links/app_links.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/detail_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/searching_screen.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';

// Global navigator key untuk navigation dari notification dan deep link
final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  debugPrint('Starting Savora app...');

  // Initialize notification service (local notifications only)
  debugPrint('Initializing notification service...');
  await NotificationService().initialize();
  debugPrint('Notification service initialized');

  // Test koneksi ke Laravel backend (opsional, hanya untuk debug)
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
  @override
  void initState() {
    super.initState();
    debugPrint('MyApp initState called');
    _handleInitialDeepLink();
    _handleIncomingDeepLinks();
  }

  // 1. Handle deep link saat app DIBUKA dari nol
  Future<void> _handleInitialDeepLink() async {
    try {
      final appLinks = AppLinks();
      final initialUri = await appLinks.getInitialLink();
      if (initialUri == null) return;
      debugPrint('Initial deep link: $initialUri');
      _navigateByUri(initialUri);
    } catch (e) {
      debugPrint('Error handling initial deep link: $e');
    }
  }

  // 2. Handle deep link saat app sedang berjalan (foreground)
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

  // 3. Router function khusus deep link
  void _navigateByUri(Uri uri) {
    if (uri.scheme != 'savora') return;

    // Delay navigation untuk memastikan context sudah tersedia
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (uri.host == 'recipe' && uri.pathSegments.isNotEmpty) {
        final recipeId = uri.pathSegments[0];
        navigatorKey.currentState?.push(
          MaterialPageRoute(
            builder: (_) => DetailScreen(recipeId: recipeId),
          ),
        );
      } else if (uri.host == 'profile' && uri.pathSegments.isNotEmpty) {
        final userId = uri.pathSegments[0];
        navigatorKey.currentState?.push(
          MaterialPageRoute(
            builder: (_) => ProfileScreen(userId: userId),
          ),
        );
      } else if (uri.host == 'search') {
        navigatorKey.currentState?.push(
          MaterialPageRoute(builder: (_) => const SearchingScreen()),
        );
      } else if (uri.host == 'home') {
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const HomeScreen()),
          (route) => false,
        );
      }
    });
  }

  @override
  void dispose() {
    NotificationService().dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'Savora',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.orange,
        useMaterial3: true,
      ),
      // Untuk cek login: pakai ApiService.hasToken atau mekanisme auth kamu
      // Sementara langsung ke HomeScreen jika sudah ada token, LoginScreen jika belum
      home: ApiService.hasToken ? const HomeScreen() : const LoginScreen(),
      onGenerateRoute: (settings) {
        debugPrint('Route requested: ${settings.name}');

        final uri = Uri.parse(settings.name ?? '');

        // Handle deep link: savora://recipe/RECIPE_ID
        if (uri.scheme == 'savora' && uri.host == 'recipe') {
          final recipeId =
              uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          if (recipeId != null) {
            return MaterialPageRoute(
              builder: (context) => DetailScreen(recipeId: recipeId),
            );
          }
        }

        // Handle deep link: savora://profile/USER_ID
        if (uri.scheme == 'savora' && uri.host == 'profile') {
          final userId =
              uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
          if (userId != null) {
            return MaterialPageRoute(
              builder: (context) => ProfileScreen(userId: userId),
            );
          }
        }

        // Handle deep link: savora://search
        if (uri.scheme == 'savora' && uri.host == 'search') {
          return MaterialPageRoute(
            builder: (context) => const SearchingScreen(),
          );
        }

        // Handle deep link: savora://home
        if (uri.scheme == 'savora' && uri.host == 'home') {
          return MaterialPageRoute(
            builder: (context) => const HomeScreen(),
          );
        }

        // Handle route dari notification
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
        }

        // Default route
        return MaterialPageRoute(builder: (context) => const HomeScreen());
      },
    );
  }
}