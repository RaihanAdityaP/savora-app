import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async';

class ApiService {
  // ─────────────────────────────────────────────
  // BASE URL
  //
  // Cara tahu IP untuk device fisik:
  //   Windows: buka CMD → ketik "ipconfig"
  //            cari "IPv4 Address" di bagian Wireless LAN adapter Wi-Fi
  //            contoh hasil: 192.168.1.5
  //   Mac/Linux: buka Terminal → ketik "ifconfig" → cari "inet"
  //
  // Pastikan HP dan laptop terhubung ke WiFi yang SAMA
  //
  // Contoh pengisian:
  //   Android Emulator  → 'http://10.0.2.2:8000/api/v1'
  //   HP via USB/WiFi   → 'http://192.168.1.9:8000/api/v1'  ← ganti IP
  //   Production        → 'https://api.savora.com/api/v1'   ← ganti domain
  // ─────────────────────────────────────────────
  static const String _baseUrlDebug = 'http://192.168.1.9:8000/api/v1';
  static const String _baseUrlProd = 'https://api.savora.com/api/v1';

  static String get _baseUrl => kDebugMode ? _baseUrlDebug : _baseUrlProd;

  static const Duration _timeout = Duration(seconds: 30);

  // Token & userId disimpan di memori
  static String? _authToken;
  static String? _currentUserId;

  // ─────────────────────────────────────────────
  // TOKEN & USER MANAGEMENT
  // ─────────────────────────────────────────────
  static void setToken(String token) => _authToken = token;
  static void clearToken() {
    _authToken = null;
    _currentUserId = null;
  }

  static String? get currentToken => _authToken;
  static bool get hasToken => _authToken != null;

  static void setCurrentUserId(String userId) => _currentUserId = userId;
  static String? get currentUserId => _currentUserId;

  // ─────────────────────────────────────────────
  // HEADERS
  // ─────────────────────────────────────────────
  static Map<String, String> _buildHeaders() {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_authToken != null) {
      headers['Authorization'] = 'Bearer $_authToken';
    }
    return headers;
  }

  // ─────────────────────────────────────────────
  // GET
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      debugPrint('[API] GET $_baseUrl$endpoint');

      final response = await http
          .get(Uri.parse('$_baseUrl$endpoint'), headers: _buildHeaders())
          .timeout(_timeout);

      debugPrint('[API] Status: ${response.statusCode}');
      return _handleResponse(response);
    } on TimeoutException {
      throw Exception('Request timeout. Cek koneksi internet kamu.');
    } on SocketException {
      throw Exception('Tidak ada koneksi internet.');
    } catch (e) {
      debugPrint('[API] GET Error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // POST
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      debugPrint('[API] POST $_baseUrl$endpoint');

      final response = await http
          .post(
            Uri.parse('$_baseUrl$endpoint'),
            headers: _buildHeaders(),
            body: json.encode(data),
          )
          .timeout(_timeout);

      debugPrint('[API] Status: ${response.statusCode}');
      return _handleResponse(response);
    } on TimeoutException {
      throw Exception('Request timeout. Cek koneksi internet kamu.');
    } on SocketException {
      throw Exception('Tidak ada koneksi internet.');
    } catch (e) {
      debugPrint('[API] POST Error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // PUT
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      debugPrint('[API] PUT $_baseUrl$endpoint');

      final response = await http
          .put(
            Uri.parse('$_baseUrl$endpoint'),
            headers: _buildHeaders(),
            body: json.encode(data),
          )
          .timeout(_timeout);

      debugPrint('[API] Status: ${response.statusCode}');
      return _handleResponse(response);
    } on TimeoutException {
      throw Exception('Request timeout. Cek koneksi internet kamu.');
    } on SocketException {
      throw Exception('Tidak ada koneksi internet.');
    } catch (e) {
      debugPrint('[API] PUT Error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // DELETE (support body untuk filter)
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> delete(
    String endpoint, {
    Map<String, dynamic>? body,
  }) async {
    try {
      debugPrint('[API] DELETE $_baseUrl$endpoint');

      final request =
          http.Request('DELETE', Uri.parse('$_baseUrl$endpoint'));
      request.headers.addAll(_buildHeaders());
      if (body != null) request.body = json.encode(body);

      final streamed = await request.send().timeout(_timeout);
      final response = await http.Response.fromStream(streamed);

      debugPrint('[API] Status: ${response.statusCode}');
      return _handleResponse(response);
    } on TimeoutException {
      throw Exception('Request timeout. Cek koneksi internet kamu.');
    } on SocketException {
      throw Exception('Tidak ada koneksi internet.');
    } catch (e) {
      debugPrint('[API] DELETE Error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // UPLOAD IMAGE (multipart)
  // ─────────────────────────────────────────────
  static Future<Map<String, dynamic>> uploadImage(
    String endpoint,
    String filePath, {
    Map<String, String>? fields,
  }) async {
    try {
      debugPrint('[API] UPLOAD $_baseUrl$endpoint');

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$_baseUrl$endpoint'),
      );
      if (_authToken != null) {
        request.headers['Authorization'] = 'Bearer $_authToken';
      }
      request.headers['Accept'] = 'application/json';
      request.files.add(
          await http.MultipartFile.fromPath('image', filePath));
      if (fields != null) request.fields.addAll(fields);

      final streamed = await request.send().timeout(_timeout);
      final response = await http.Response.fromStream(streamed);

      debugPrint('[API] Status: ${response.statusCode}');
      return _handleResponse(response);
    } on TimeoutException {
      throw Exception('Upload timeout. Cek koneksi internet kamu.');
    } on SocketException {
      throw Exception('Tidak ada koneksi internet.');
    } catch (e) {
      debugPrint('[API] UPLOAD Error: $e');
      rethrow;
    }
  }

  // ─────────────────────────────────────────────
  // HEALTH CHECK
  // ─────────────────────────────────────────────
  static Future<bool> healthCheck() async {
    try {
      final response = await get('/health');
      return response['success'] == true;
    } catch (_) {
      return false;
    }
  }

  // ─────────────────────────────────────────────
  // INTERNAL: handle & decode response
  // Mengembalikan body termasuk error response agar bisa dibaca
  // ─────────────────────────────────────────────
  static Map<String, dynamic> _handleResponse(http.Response response) {
    try {
      final decoded = json.decode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return decoded;
      }
      // Untuk 4xx/5xx, kembalikan decoded sehingga caller bisa baca 'message'
      final message = decoded['message'] ?? 'Error ${response.statusCode}';
      throw Exception(message);
    } on FormatException {
      throw Exception(
          'Response bukan JSON valid (status ${response.statusCode})');
    }
  }
}