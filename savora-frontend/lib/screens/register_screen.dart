import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../services/auth_client.dart';
import '../widgets/theme.dart';
import '../widgets/privacy_modal.dart';
import '../widgets/terms_modal.dart';
import 'login_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen>
    with SingleTickerProviderStateMixin {
  final _usernameController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;

  // Single consent checkbox — matches Next.js version
  bool _agreedToTerms = false;

  bool get _canRegister => _agreedToTerms;

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
    _usernameController.dispose();
    _fullNameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  // ─────────────────────────────────────────────
  // OPEN MODALS
  // ─────────────────────────────────────────────

  Future<void> _openPrivacyModal() async {
    await PrivacyModal.show(context);
  }

  Future<void> _openTermsModal() async {
    await TermsModal.show(context);
  }

  // ─────────────────────────────────────────────
  // REGISTER
  // ─────────────────────────────────────────────

  Future<void> _signUp() async {
    if (!_canRegister) {
      _showSnackBar(
        'Anda harus menyetujui Syarat & Ketentuan dan Kebijakan Privasi terlebih dahulu.',
        isError: true,
      );
      return;
    }
    if (_usernameController.text.isEmpty ||
        _fullNameController.text.isEmpty ||
        _emailController.text.isEmpty ||
        _passwordController.text.isEmpty) {
      _showSnackBar('Semua field harus diisi!', isError: true);
      return;
    }
    setState(() => _isLoading = true);
    try {
      final response = await AuthClient.register(
        email: _emailController.text.trim(),
        password: _passwordController.text,
        username: _usernameController.text.trim(),
        fullName: _fullNameController.text.trim(),
      );
      if (response['success'] == true) {
        if (mounted) _showSuccessDialog();
      }
    } on AuthException catch (e) {
      if (!mounted) return;
      String errorMsg = e.message;
      if (errorMsg.contains('already registered') ||
          errorMsg.contains('already exists') ||
          errorMsg.contains('User already registered')) {
        _showResendVerificationDialog();
        return;
      } else if (errorMsg.contains('Password')) {
        errorMsg = 'Password minimal 6 karakter';
      }
      _showSnackBar(errorMsg, isError: true);
    } catch (e) {
      if (!mounted) return;
      String errorMsg = e.toString().replaceFirst('Exception: ', '');
      if (errorMsg.contains('timeout')) {
        errorMsg = 'Koneksi timeout. Cek internet Anda.';
      } else if (errorMsg.contains('already registered')) {
        _showResendVerificationDialog();
        return;
      }
      _showSnackBar(errorMsg, isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resendVerificationEmail() async {
    setState(() => _isLoading = true);
    try {
      final success =
          await AuthClient.resendVerificationEmail(_emailController.text.trim());
      if (mounted) {
        if (success) {
          _showSnackBar(
            'Email verifikasi telah dikirim ulang ke ${_emailController.text}',
            isError: false,
          );
        } else {
          _showSnackBar('Gagal mengirim email. Coba lagi nanti.', isError: true);
        }
      }
    } catch (e) {
      if (!mounted) return;
      String errorMsg = e.toString().replaceFirst('Exception: ', '');
      if (errorMsg.contains('rate') || errorMsg.contains('limit')) {
        errorMsg = 'Terlalu banyak permintaan. Tunggu beberapa menit.';
      }
      _showSnackBar(errorMsg, isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showResendVerificationDialog() {
    showDialog(
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
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.email_rounded,
                  color: Colors.white, size: 24),
            ),
            const SizedBox(width: 14),
            const Expanded(
                child: Text('Email Sudah Terdaftar',
                    style: TextStyle(
                        fontSize: 19, fontWeight: FontWeight.bold))),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
                'Email ${_emailController.text} sudah terdaftar.',
                style: const TextStyle(
                    fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 16),
            const Text(
                'Kemungkinan Anda belum verifikasi email. Kirim ulang email verifikasi?',
                style: TextStyle(fontSize: 14, height: 1.5)),
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
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: () {
                Navigator.pop(dialogContext);
                _resendVerificationEmail();
              },
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.send_rounded, color: Colors.white, size: 18),
                  SizedBox(width: 8),
                  Text('Kirim Ulang',
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

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                    colors: [Colors.green.shade400, Colors.green.shade600]),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.check_circle_rounded,
                  color: Colors.white, size: 28),
            ),
            const SizedBox(width: 14),
            const Text('Berhasil!',
                style: TextStyle(
                    fontSize: 22, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Akun Anda telah dibuat.',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 18),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [
                  AppTheme.primaryYellow.withValues(alpha: 0.2),
                  AppTheme.primaryOrange.withValues(alpha: 0.1),
                ]),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                    color: AppTheme.primaryYellow.withValues(alpha: 0.3)),
              ),
              child: Row(children: [
                const Icon(Icons.email_rounded,
                    color: AppTheme.primaryCoral, size: 22),
                const SizedBox(width: 10),
                Expanded(
                    child: Text(
                        'Email verifikasi telah dikirim ke ${_emailController.text}',
                        style: const TextStyle(
                            fontSize: 13,
                            color: AppTheme.textPrimary,
                            height: 1.4))),
              ]),
            ),
            const SizedBox(height: 16),
            Text(
                'Silakan cek inbox atau folder spam Anda dan klik link verifikasi untuk mengaktifkan akun.',
                style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey.shade600,
                    height: 1.5)),
          ],
        ),
        actions: [
          Container(
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: () {
                Navigator.pop(dialogContext);
                Navigator.of(context).pushReplacement(
                    MaterialPageRoute(builder: (_) => const LoginScreen()));
              },
              child: const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: Text('OK, Mengerti',
                    style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 15)),
              ),
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

  // ─────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────

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
                    // Back button
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.25),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                              color: Colors.white.withValues(alpha: 0.5),
                              width: 2),
                        ),
                        child: IconButton(
                            icon: const Icon(Icons.arrow_back_rounded,
                                color: Colors.white, size: 24),
                            onPressed: () => Navigator.pop(context)),
                      ),
                    ),
                    const SizedBox(height: 28),

                    // Logo
                    ScaleTransition(
                      scale: _scaleAnimation,
                      child: Container(
                        width: 110,
                        height: 110,
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
                                  size: 55,
                                  color: Color(0xFF2B6CB0)),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),

                    // Heading
                    SlideTransition(
                      position: _slideAnimation,
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 26, vertical: 12),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(30),
                              border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.4),
                                  width: 2),
                            ),
                            child: const Text('BERGABUNG',
                                style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 4,
                                    color: Colors.white)),
                          ),
                          const SizedBox(height: 16),
                          const Text('Buat Akun',
                              style: TextStyle(
                                  fontSize: 46,
                                  fontWeight: FontWeight.w900,
                                  color: Colors.white,
                                  letterSpacing: -1)),
                          const SizedBox(height: 10),
                          Text('Mulai Petualangan Kuliner Anda',
                              style: TextStyle(
                                  fontSize: 15,
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontWeight: FontWeight.w500)),
                        ],
                      ),
                    ),
                    const SizedBox(height: 42),

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
                                controller: _usernameController,
                                hint: 'Username',
                                icon: Icons.person_outline_rounded,
                                color: AppTheme.primaryCoral),
                            const SizedBox(height: 18),
                            _buildTextField(
                                controller: _fullNameController,
                                hint: 'Nama Lengkap',
                                icon: Icons.badge_outlined,
                                color: AppTheme.primaryTeal),
                            const SizedBox(height: 18),
                            _buildTextField(
                                controller: _emailController,
                                hint: 'Email Address',
                                icon: Icons.email_outlined,
                                color: AppTheme.primaryYellow,
                                keyboardType: TextInputType.emailAddress),
                            const SizedBox(height: 18),
                            _buildTextField(
                                controller: _passwordController,
                                hint: 'Password (min. 6 karakter)',
                                icon: Icons.lock_outline_rounded,
                                color: AppTheme.primaryOrange,
                                isPassword: true),
                            const SizedBox(height: 20),

                            // ── Consent checkbox ──
                            _buildConsentSection(),

                            const SizedBox(height: 24),

                            // Register button
                            AnimatedOpacity(
                              opacity: _canRegister ? 1.0 : 0.5,
                              duration: const Duration(milliseconds: 250),
                              child: Container(
                                width: double.infinity,
                                height: 58,
                                decoration: BoxDecoration(
                                  gradient: _canRegister
                                      ? AppTheme.orangeGradient
                                      : const LinearGradient(colors: [
                                          Colors.grey,
                                          Colors.grey,
                                        ]),
                                  borderRadius: BorderRadius.circular(16),
                                  boxShadow: _canRegister
                                      ? [
                                          BoxShadow(
                                              color: AppTheme.primaryOrange
                                                  .withValues(alpha: 0.5),
                                              blurRadius: 20,
                                              offset: const Offset(0, 10))
                                        ]
                                      : [],
                                ),
                                child: Material(
                                  color: Colors.transparent,
                                  child: InkWell(
                                    onTap: (_isLoading || !_canRegister)
                                        ? null
                                        : _signUp,
                                    borderRadius: BorderRadius.circular(16),
                                    child: Center(
                                      child: _isLoading
                                          ? const SizedBox(
                                              height: 26,
                                              width: 26,
                                              child:
                                                  CircularProgressIndicator(
                                                      strokeWidth: 3,
                                                      color: Colors.white))
                                          : const Text(
                                              'DAFTAR SEKARANG',
                                              style: TextStyle(
                                                  fontSize: 16,
                                                  fontWeight: FontWeight.bold,
                                                  letterSpacing: 2,
                                                  color: Colors.white),
                                            ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),

                    // Login link
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
                          Text('Sudah Punya Akun?',
                              style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontSize: 15,
                                  fontWeight: FontWeight.w500)),
                          const SizedBox(width: 10),
                          GestureDetector(
                            onTap: () => Navigator.pop(context),
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
                                  Text('Masuk',
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
  // CONSENT SECTION — single checkbox, inline links
  // ─────────────────────────────────────────────

  Widget _buildConsentSection() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Animated checkbox
        GestureDetector(
          onTap: () => setState(() => _agreedToTerms = !_agreedToTerms),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            width: 22,
            height: 22,
            margin: const EdgeInsets.only(top: 2),
            decoration: BoxDecoration(
              color: _agreedToTerms
                  ? const Color(0xFFE76F51)
                  : Colors.white,
              borderRadius: BorderRadius.circular(6),
              border: Border.all(
                color: _agreedToTerms
                    ? const Color(0xFFE76F51)
                    : Colors.grey.shade400,
                width: 2,
              ),
              boxShadow: _agreedToTerms
                  ? [
                      BoxShadow(
                        color: const Color(0xFFE76F51).withValues(alpha: 0.35),
                        blurRadius: 6,
                      )
                    ]
                  : [],
            ),
            child: _agreedToTerms
                ? const Icon(Icons.check_rounded,
                    color: Colors.white, size: 15)
                : null,
          ),
        ),
        const SizedBox(width: 10),

        // Inline label with tappable links
        Expanded(
          child: RichText(
            text: TextSpan(
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey.shade600,
                height: 1.5,
              ),
              children: [
                const TextSpan(text: 'Dengan mendaftar, Anda menyetujui '),
                WidgetSpan(
                  alignment: PlaceholderAlignment.middle,
                  child: GestureDetector(
                    onTap: _openTermsModal,
                    child: const Text(
                      'Syarat & Ketentuan',
                      style: TextStyle(
                        fontSize: 13,
                        color: Color(0xFFE76F51),
                        fontWeight: FontWeight.bold,
                        decoration: TextDecoration.underline,
                        decorationColor: Color(0xFFE76F51),
                      ),
                    ),
                  ),
                ),
                const TextSpan(text: ' dan '),
                WidgetSpan(
                  alignment: PlaceholderAlignment.middle,
                  child: GestureDetector(
                    onTap: _openPrivacyModal,
                    child: const Text(
                      'Kebijakan Privasi',
                      style: TextStyle(
                        fontSize: 13,
                        color: Color(0xFF2A9D8F),
                        fontWeight: FontWeight.bold,
                        decoration: TextDecoration.underline,
                        decorationColor: Color(0xFF2A9D8F),
                      ),
                    ),
                  ),
                ),
                const TextSpan(text: ' kami.'),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    required Color color,
    bool isPassword = false,
    TextInputType? keyboardType,
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
        keyboardType: keyboardType,
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