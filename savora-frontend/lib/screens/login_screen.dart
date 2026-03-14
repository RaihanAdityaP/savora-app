import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_client.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../widgets/theme.dart';
import '../widgets/privacy_modal.dart';
import '../widgets/terms_modal.dart';
import 'register_screen.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _isGoogleLoading = false;

  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
        duration: const Duration(milliseconds: 1800), vsync: this);
    _fadeAnimation = CurvedAnimation(
        parent: _animationController, curve: Curves.easeInOut);
    _slideAnimation =
        Tween<Offset>(begin: const Offset(0, 0.4), end: Offset.zero).animate(
            CurvedAnimation(
                parent: _animationController, curve: Curves.easeOutCubic));
    _scaleAnimation = Tween<double>(begin: 0.7, end: 1.0).animate(
        CurvedAnimation(
            parent: _animationController, curve: Curves.elasticOut));
    _animationController.forward();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _signInWithGoogle() async {
    setState(() => _isGoogleLoading = true);
    try {
      final response = await AuthClient.signInWithGoogle();
      if (response['success'] == true) {
        final isBanned = response['user']?['is_banned'] == true;
        if (isBanned) {
          ApiService.clearToken();
          final reason =
              response['user']?['banned_reason'] ?? 'Tidak disebutkan';
          final bannedAt = response['user']?['banned_at'];
          if (mounted) _showBannedDialog(reason: reason, bannedAt: bannedAt);
          return;
        }
        if (mounted) {
          Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (_) => const HomeScreen()));
        }
      }
    } on AuthException catch (e) {
      if (mounted) _showSnackBar(e.message, isError: true);
    } catch (e) {
      if (mounted) {
        _showSnackBar('Google Sign In gagal: ${e.toString()}', isError: true);
      }
    } finally {
      if (mounted) setState(() => _isGoogleLoading = false);
    }
  }

  Future<void> _signIn() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      _showSnackBar('Email dan password harus diisi!', isError: true);
      return;
    }
    setState(() => _isLoading = true);
    try {
      final response = await AuthClient.login(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      );
      if (response['success'] == true) {
        final isBanned = response['user']?['is_banned'] == true;
        if (isBanned) {
          ApiService.clearToken();
          final reason =
              response['user']?['banned_reason'] ?? 'Tidak disebutkan';
          final bannedAt = response['user']?['banned_at'];
          if (mounted) _showBannedDialog(reason: reason, bannedAt: bannedAt);
          return;
        }
        if (mounted) {
          Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (_) => const HomeScreen()));
        }
      }
    } on AuthException catch (e) {
      if (!mounted) return;
      String errorMsg = e.message;
      if (errorMsg.contains('Invalid login credentials')) {
        errorMsg = 'Email atau password salah';
      } else if (errorMsg.contains('Email not confirmed')) {
        errorMsg = 'Silakan verifikasi email Anda terlebih dahulu';
      }
      _showSnackBar(errorMsg, isError: true);
    } catch (e) {
      if (!mounted) return;
      String errorMsg = e.toString().replaceFirst('Exception: ', '');
      if (errorMsg.contains('timeout')) {
        errorMsg = 'Koneksi timeout. Cek internet Anda.';
      }
      _showSnackBar(errorMsg, isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _showResendVerificationDialog() async {
    final emailController = TextEditingController();
    await showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.email_rounded,
                  color: Colors.white, size: 24),
            ),
            const SizedBox(width: 14),
            const Expanded(
                child: Text('Verifikasi Email',
                    style: TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold))),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
                'Masukkan email Anda untuk mengirim ulang link verifikasi:'),
            const SizedBox(height: 20),
            Container(
              decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(14),
                  border:
                      Border.all(color: Colors.grey.shade200, width: 1.5)),
              child: TextField(
                controller: emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  hintText: 'Email',
                  prefixIcon: Icon(Icons.email_outlined,
                      color: AppTheme.primaryCoral, size: 22),
                  border: InputBorder.none,
                  contentPadding:
                      EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                ),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: Text('Batal',
                style: TextStyle(color: Colors.grey.shade600)),
          ),
          Container(
            decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(12)),
            child: TextButton(
              onPressed: () async {
                final email = emailController.text.trim();
                if (email.isEmpty) return;
                Navigator.pop(dialogContext);
                try {
                  final success =
                      await AuthClient.resendVerificationEmail(email);
                  if (mounted) {
                    if (success) {
                      _showSnackBar('Email verifikasi dikirim ke $email',
                          isError: false);
                    } else {
                      _showSnackBar('Gagal mengirim email verifikasi',
                          isError: true);
                    }
                  }
                } catch (e) {
                  if (mounted) {
                    _showSnackBar('Gagal: $e', isError: true);
                  }
                }
              },
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.send_rounded, color: Colors.white, size: 18),
                  SizedBox(width: 8),
                  Text('Kirim',
                      style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showBannedDialog({required String reason, String? bannedAt}) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                    colors: [Colors.red.shade400, Colors.red.shade600]),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.block_rounded,
                  color: Colors.white, size: 24),
            ),
            const SizedBox(width: 14),
            const Text('Akun Dinonaktifkan'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Akun Anda telah dinonaktifkan oleh administrator.',
                style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            const Text('Alasan:',
                style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.red.shade200)),
              child: Text(reason,
                  style: TextStyle(color: Colors.red.shade700)),
            ),
          ],
        ),
        actions: [
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                  colors: [Colors.grey.shade400, Colors.grey.shade600]),
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Tutup',
                  style: TextStyle(
                      color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  void _showSnackBar(String message, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(children: [
          Icon(
              isError
                  ? Icons.error_outline_rounded
                  : Icons.check_circle_outline_rounded,
              color: Colors.white),
          const SizedBox(width: 12),
          Expanded(child: Text(message)),
        ]),
        backgroundColor:
            isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        margin: const EdgeInsets.all(16),
        duration: const Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: AppTheme.primaryGradient),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Logo
                    ScaleTransition(
                      scale: _scaleAnimation,
                      child: Container(
                        width: 120,
                        height: 120,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                              colors: [Color(0xFF2B6CB0), Color(0xFFFF6B35)]),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF2B6CB0)
                                  .withValues(alpha: 0.4),
                              blurRadius: 30,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        padding: const EdgeInsets.all(4),
                        child: Container(
                          decoration: const BoxDecoration(
                              color: Colors.white, shape: BoxShape.circle),
                          child: ClipOval(
                            child: Image.asset(
                              'assets/images/logo.png',
                              fit: BoxFit.cover,
                              errorBuilder: (_, _, _) => const Icon(
                                Icons.restaurant_rounded,
                                size: 60,
                                color: Color(0xFF2B6CB0),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 36),

                    SlideTransition(
                      position: _slideAnimation,
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 28, vertical: 14),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(30),
                              border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.4),
                                  width: 2),
                            ),
                            child: const Text('SELAMAT DATANG',
                                style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 4,
                                    color: Colors.white)),
                          ),
                          const SizedBox(height: 18),
                          const Text('Savora',
                              style: TextStyle(
                                  fontSize: 56,
                                  fontWeight: FontWeight.w900,
                                  color: Colors.white,
                                  height: 1.1,
                                  letterSpacing: -1)),
                          const SizedBox(height: 10),
                          Text('Petualangan Kuliner Dimulai Disini',
                              style: TextStyle(
                                  fontSize: 15,
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontWeight: FontWeight.w500)),
                        ],
                      ),
                    ),
                    const SizedBox(height: 50),

                    // Form card
                    SlideTransition(
                      position: _slideAnimation,
                      child: Container(
                        constraints: const BoxConstraints(maxWidth: 460),
                        padding: const EdgeInsets.all(36),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(32),
                          boxShadow: [
                            BoxShadow(
                                color: Colors.black.withValues(alpha: 0.25),
                                blurRadius: 40,
                                offset: const Offset(0, 20))
                          ],
                        ),
                        child: Column(
                          children: [
                            _buildTextField(
                                controller: _emailController,
                                hint: 'Email Address',
                                icon: Icons.email_outlined,
                                color: AppTheme.primaryCoral),
                            const SizedBox(height: 20),
                            _buildTextField(
                                controller: _passwordController,
                                hint: 'Password',
                                icon: Icons.lock_outline_rounded,
                                color: AppTheme.primaryOrange,
                                isPassword: true),
                            const SizedBox(height: 32),

                            // Login button
                            Container(
                              width: double.infinity,
                              height: 58,
                              decoration: BoxDecoration(
                                gradient: AppTheme.orangeGradient,
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(
                                      color: AppTheme.primaryOrange
                                          .withValues(alpha: 0.5),
                                      blurRadius: 20,
                                      offset: const Offset(0, 10))
                                ],
                              ),
                              child: Material(
                                color: Colors.transparent,
                                child: InkWell(
                                  onTap: _isLoading ? null : _signIn,
                                  borderRadius: BorderRadius.circular(16),
                                  child: Center(
                                    child: _isLoading
                                        ? const SizedBox(
                                            height: 26,
                                            width: 26,
                                            child: CircularProgressIndicator(
                                                strokeWidth: 3,
                                                color: Colors.white))
                                        : const Text('MASUK',
                                            style: TextStyle(
                                                fontSize: 17,
                                                fontWeight: FontWeight.bold,
                                                letterSpacing: 2,
                                                color: Colors.white)),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 28),

                            Row(children: [
                              Expanded(
                                  child: Divider(
                                      color: Colors.grey.shade300,
                                      thickness: 1.5)),
                              Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16),
                                child: Text('ATAU',
                                    style: TextStyle(
                                        color: Colors.grey.shade600,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                        letterSpacing: 1.5)),
                              ),
                              Expanded(
                                  child: Divider(
                                      color: Colors.grey.shade300,
                                      thickness: 1.5)),
                            ]),
                            const SizedBox(height: 28),

                            // Google button
                            Container(
                              width: double.infinity,
                              height: 58,
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                    color: Colors.grey.shade300, width: 2),
                              ),
                              child: Material(
                                color: Colors.transparent,
                                child: InkWell(
                                  onTap: _isGoogleLoading
                                      ? null
                                      : _signInWithGoogle,
                                  borderRadius: BorderRadius.circular(16),
                                  child: Center(
                                    child: _isGoogleLoading
                                        ? const SizedBox(
                                            height: 26,
                                            width: 26,
                                            child: CircularProgressIndicator(
                                                strokeWidth: 3,
                                                color: AppTheme.primaryCoral))
                                        : Row(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            children: [
                                              Image.asset(
                                                  'assets/images/googlelogo.png',
                                                  height: 26,
                                                  width: 26,
                                                  errorBuilder: (_, _, _) =>
                                                      const Icon(
                                                          Icons
                                                              .g_mobiledata_rounded,
                                                          size: 26)),
                                              const SizedBox(width: 12),
                                              Text(
                                                  'Lanjutkan dengan Google',
                                                  style: TextStyle(
                                                      fontSize: 15,
                                                      fontWeight:
                                                          FontWeight.w600,
                                                      color: Colors
                                                          .grey.shade800)),
                                            ],
                                          ),
                                  ),
                                ),
                              ),
                            ),

                            // Privacy & Terms links at bottom of card
                            const SizedBox(height: 24),
                            _buildPolicyLinks(),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Resend verification
                    TextButton(
                      onPressed: _showResendVerificationDialog,
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 12),
                        backgroundColor:
                            Colors.white.withValues(alpha: 0.15),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text('Belum verifikasi email?',
                          style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.95),
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              decoration: TextDecoration.underline,
                              decorationColor:
                                  Colors.white.withValues(alpha: 0.95))),
                    ),
                    const SizedBox(height: 18),

                    // Register link
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 22, vertical: 18),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                            color: Colors.white.withValues(alpha: 0.4),
                            width: 2),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('Belum punya akun?',
                              style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontSize: 15,
                                  fontWeight: FontWeight.w500)),
                          const SizedBox(width: 10),
                          GestureDetector(
                            onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                    builder: (_) => const RegisterScreen())),
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 18, vertical: 8),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                      color: AppTheme.primaryCoral
                                          .withValues(alpha: 0.3),
                                      blurRadius: 8,
                                      offset: const Offset(0, 4))
                                ],
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text('Daftar',
                                      style: TextStyle(
                                          color: AppTheme.primaryCoral,
                                          fontSize: 15,
                                          fontWeight: FontWeight.bold)),
                                  SizedBox(width: 6),
                                  Icon(Icons.arrow_forward_rounded,
                                      color: AppTheme.primaryCoral,
                                      size: 18),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────
  // POLICY LINKS — menggunakan RichText agar tidak overflow
  // ─────────────────────────────────────────────

  Widget _buildPolicyLinks() {
    return RichText(
      textAlign: TextAlign.center,
      text: TextSpan(
        style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
        children: [
          const TextSpan(text: 'Dengan masuk, Anda setuju dengan '),
          WidgetSpan(
            alignment: PlaceholderAlignment.middle,
            child: GestureDetector(
              onTap: () => PrivacyModal.show(context),
              child: const Text(
                'Privasi',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFF2A9D8F),
                  fontWeight: FontWeight.bold,
                  decoration: TextDecoration.underline,
                  decorationColor: Color(0xFF2A9D8F),
                ),
              ),
            ),
          ),
          TextSpan(
            text: ' & ',
            style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
          ),
          WidgetSpan(
            alignment: PlaceholderAlignment.middle,
            child: GestureDetector(
              onTap: () => TermsModal.show(context),
              child: const Text(
                'Ketentuan',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFFE76F51),
                  fontWeight: FontWeight.bold,
                  decoration: TextDecoration.underline,
                  decorationColor: Color(0xFFE76F51),
                ),
              ),
            ),
          ),
          TextSpan(
            text: ' kami.',
            style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    required Color color,
    bool isPassword = false,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200, width: 2),
      ),
      child: TextField(
        controller: controller,
        obscureText: isPassword && _obscurePassword,
        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(color: Colors.grey.shade400),
          prefixIcon: Icon(icon, color: color, size: 22),
          suffixIcon: isPassword
              ? IconButton(
                  icon: Icon(
                      _obscurePassword
                          ? Icons.visibility_off_rounded
                          : Icons.visibility_rounded,
                      color: Colors.grey.shade600),
                  onPressed: () =>
                      setState(() => _obscurePassword = !_obscurePassword))
              : null,
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide.none),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(color: color, width: 2)),
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
        ),
      ),
    );
  }
}