import 'package:flutter/foundation.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'api_service.dart';

/// AuthClient - Handle Supabase Auth + Sanctum Token Exchange
class AuthClient {
  static final _supabase = Supabase.instance.client;
  static const _googleServerClientIdFromDartDefine = String.fromEnvironment(
    'GOOGLE_WEB_CLIENT_ID',
    defaultValue: 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
  );

  static String get _googleServerClientId {
    final envClientId = dotenv.env['GOOGLE_WEB_CLIENT_ID'];
    if (envClientId != null && envClientId.isNotEmpty) {
      return envClientId;
    }
    return _googleServerClientIdFromDartDefine;
  }

  static GoogleSignIn? _googleSignInInstance;

  static GoogleSignIn get _googleSignIn {
    _googleSignInInstance ??= GoogleSignIn(
      scopes: const ['email', 'profile', 'openid'],
      serverClientId: _googleServerClientId,
    );
    return _googleSignInInstance!;
  }

  // ─────────────────────────────────────────────
  // REGISTER
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String username,
    required String fullName,
  }) async {
    try {
      debugPrint('AuthClient: Registering user...');

      // 1. Register via Supabase Auth
      final authResponse = await _supabase.auth.signUp(
        email: email,
        password: password,
        data: {
          'username': username,
          'full_name': fullName,
        },
      );

      if (authResponse.user == null) {
        throw Exception('Registration failed');
      }

      return {
        'success': true,
        'message': 'Registration successful. Please verify your email.',
        'user': authResponse.user,
      };
    } catch (e) {
      debugPrint('AuthClient.register error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // LOGIN + EXCHANGE TOKEN
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    try {
      debugPrint('AuthClient: Logging in...');

      // 1. Login via Supabase Auth
      final authResponse = await _supabase.auth.signInWithPassword(
        email: email,
        password: password,
      );

      if (authResponse.session == null) {
        throw Exception('Login failed - no session');
      }

      final supabaseToken = authResponse.session!.accessToken;
      final userId = authResponse.user!.id;

      debugPrint('AuthClient: Got Supabase token, exchanging...');

      // 2. Exchange Supabase JWT for Sanctum token
      Map<String, dynamic> exchangeResponse;
      try {
        exchangeResponse = await ApiService.post('/auth/token', {
          'supabase_token': supabaseToken,
        });
      } catch (e) {
        final detail = e.toString().replaceFirst('Exception: ', '');
        throw Exception(
          'Login to Supabase succeeded, but the Laravel backend cannot be accessed. Details: $detail',
        );
      }

      if (exchangeResponse['success'] != true) {
        throw Exception(exchangeResponse['message'] ?? 'Token exchange failed');
      }

      final sanctumToken = exchangeResponse['data']?['sanctum_token'];
      if (sanctumToken == null) {
        throw Exception('No Sanctum token received');
      }

      // 3. Save Sanctum token to ApiService
      ApiService.setToken(sanctumToken);
      ApiService.setCurrentUserId(userId);

      debugPrint('AuthClient: Login successful');

      return {
        'success': true,
        'token': sanctumToken,
        'user': exchangeResponse['data']?['user'],
      };
    } catch (e) {
      debugPrint('AuthClient.login error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // GOOGLE SIGN IN + EXCHANGE TOKEN
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> signInWithGoogle() async {
    try {
      debugPrint('AuthClient: Google Sign In...');

      // Web uses OAuth popup/redirect by design.
      if (kIsWeb) {
        return _signInWithGoogleOAuth();
      }

      // Native-first flow: use Google Sign-In (Credential Manager on Android)
      // and exchange Google ID token to Supabase session.
      // Always clear cached account first to force a fresh ID token —
      // prevents 401 on re-login after logout.
      await _googleSignIn.signOut();
      final googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        throw Exception('Google Sign In cancelled');
      }

      final googleAuth = await googleUser.authentication;
      final idToken = googleAuth.idToken;
      final accessToken = googleAuth.accessToken;

      if (idToken == null || idToken.isEmpty) {
        throw Exception(
          'Google ID token tidak tersedia. Pastikan konfigurasi OAuth client Android/iOS sudah benar.',
        );
      }

      final authResponse = await _supabase.auth.signInWithIdToken(
        provider: OAuthProvider.google,
        idToken: idToken,
        accessToken: accessToken,
      );

      final session = authResponse.session ?? _supabase.auth.currentSession;
      if (session == null) {
        throw Exception('No Supabase session after native Google Sign In');
      }

      return _exchangeSupabaseSession(session);
    } catch (e) {
      debugPrint('AuthClient.signInWithGoogle error: $e');
      final msg = e.toString();
      if (msg.contains('cancelled') || msg.contains('cancel')) {
        throw Exception('Login Google dibatalkan.');
      } else if (msg.contains('network') || msg.contains('timeout')) {
        throw Exception('Koneksi bermasalah. Periksa internet Anda.');
      } else if (msg.contains('Invalid or expired') || msg.contains('401')) {
        throw Exception('Sesi Google tidak valid. Silakan coba lagi.');
      } else if (msg.contains('ID token')) {
        throw Exception('Gagal mendapatkan token Google. Pastikan Google Play Services aktif.');
      }
      throw Exception('Login Google gagal. Silakan coba beberapa saat lagi.');
    }
  }

  static Future<Map<String, dynamic>> _signInWithGoogleOAuth() async {
    final authResponse = await _supabase.auth.signInWithOAuth(
      OAuthProvider.google,
      redirectTo: 'io.supabase.savora://login-callback/',
    );

    if (!authResponse) {
      throw Exception('Google Sign In cancelled');
    }

    await Future.delayed(const Duration(seconds: 2));

    final session = _supabase.auth.currentSession;
    if (session == null) {
      throw Exception('No session after Google Sign In');
    }

    return _exchangeSupabaseSession(session);
  }

  static Future<Map<String, dynamic>> _exchangeSupabaseSession(
    Session session,
  ) async {
    final supabaseToken = session.accessToken;
    final userId = session.user.id;

    final exchangeResponse = await ApiService.post('/auth/token', {
      'supabase_token': supabaseToken,
    });

    if (exchangeResponse['success'] != true) {
      throw Exception(exchangeResponse['message'] ?? 'Token exchange failed');
    }

    final sanctumToken = exchangeResponse['data']?['sanctum_token'];
    if (sanctumToken == null) {
      throw Exception('No Sanctum token received');
    }

    ApiService.setToken(sanctumToken);
    ApiService.setCurrentUserId(userId);

    return {
      'success': true,
      'token': sanctumToken,
      'user': exchangeResponse['data']?['user'],
    };
  }

  // ─────────────────────────────────────────────
  // LOGOUT
  // ─────────────────────────────────────────────
  static Future<void> logout() async {
    try {
      debugPrint('AuthClient: Logging out...');

      // 1. Revoke Sanctum token
      await ApiService.post('/auth/logout', {});

      // 2. Sign out from Supabase
      await _supabase.auth.signOut();

      if (!kIsWeb) {
        await _googleSignIn.signOut();
      }

      // 3. Clear tokens
      ApiService.clearToken();

      debugPrint('AuthClient: Logout successful');
    } catch (e) {
      debugPrint('AuthClient.logout error: $e');
      // Clear tokens anyway
      ApiService.clearToken();
    }
  }

  // ─────────────────────────────────────────────
  // RESEND VERIFICATION EMAIL
  // ─────────────────────────────────────────────
  static Future<bool> resendVerificationEmail(String email) async {
    try {
      debugPrint('AuthClient: Resending verification email...');
      
      await _supabase.auth.resend(
        type: OtpType.signup,
        email: email,
      );

      return true;
    } catch (e) {
      debugPrint('AuthClient.resendVerificationEmail error: $e');
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // GET CURRENT USER FROM LARAVEL
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>?> getCurrentUser() async {
    try {
      final response = await ApiService.get('/auth/me');
      if (response['success'] == true) {
        return Map<String, dynamic>.from(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('AuthClient.getCurrentUser error: $e');
      return null;
    }
  }
}