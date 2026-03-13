import 'package:flutter/foundation.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'api_service.dart';

/// AuthClient - Handle Supabase Auth + Sanctum Token Exchange
class AuthClient {
  static final _supabase = Supabase.instance.client;

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

      // 1. Sign in with Google via Supabase
      final authResponse = await _supabase.auth.signInWithOAuth(
        OAuthProvider.google,
        redirectTo: 'io.supabase.savora://login-callback/',
      );

      if (!authResponse) {
        throw Exception('Google Sign In cancelled');
      }

      // 2. Wait for session
      await Future.delayed(const Duration(seconds: 2));
      
      final session = _supabase.auth.currentSession;
      if (session == null) {
        throw Exception('No session after Google Sign In');
      }

      final supabaseToken = session.accessToken;
      final userId = session.user.id;

      // 3. Exchange token
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

      // 4. Save tokens
      ApiService.setToken(sanctumToken);
      ApiService.setCurrentUserId(userId);

      return {
        'success': true,
        'token': sanctumToken,
        'user': exchangeResponse['data']?['user'],
      };
    } catch (e) {
      debugPrint('AuthClient.signInWithGoogle error: $e');
      rethrow;
    }
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