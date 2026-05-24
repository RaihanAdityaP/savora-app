import 'dart:async';

import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_client.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../../widgets/theme.dart';
import '../../widgets/privacy_modal.dart';
import '../../widgets/terms_modal.dart';
import 'register_screen.dart';
import '../home_screen.dart';

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
  StreamSubscription<AuthState>? _authSubscription;

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
    _authSubscription =
        Supabase.instance.client.auth.onAuthStateChange.listen((data) {
      if (data.event == AuthChangeEvent.passwordRecovery && mounted) {
        _showUpdatePasswordDialog();
      }
    });
    _animationController.forward();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _authSubscription?.cancel();
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
              response['user']?['banned_reason'] ?? 'Not specified';
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
        _showSnackBar('Google Sign In failed: ${e.toString()}', isError: true);
      }
    } finally {
      if (mounted) setState(() => _isGoogleLoading = false);
    }
  }

  Future<void> _signIn() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      _showSnackBar('Email and password are required!', isError: true);
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
              response['user']?['banned_reason'] ?? 'Not specified';
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
        errorMsg = 'Email or password is incorrect';
      } else if (errorMsg.contains('Email not confirmed')) {
        errorMsg = 'Please verify your email first';
      }
      _showSnackBar(errorMsg, isError: true);
    } catch (e) {
      if (!mounted) return;
      String errorMsg = e.toString().replaceFirst('Exception: ', '');
      if (errorMsg.contains('timeout')) {
        errorMsg = 'Connection timed out. Check your internet connection.';
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
                child: Text('Email Verification',
                    style: TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold))),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
                'Enter your email to resend the verification link:'),
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
                style: const TextStyle(
                  color: AppTheme.primaryDark,
                  fontWeight: FontWeight.w500,
                ),
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
            child: Text('Cancel',
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
                      _showSnackBar('Verification email sent to $email',
                          isError: false);
                    } else {
                      _showSnackBar('Failed to send verification email',
                          isError: true);
                    }
                  }
                } catch (e) {
                  if (mounted) {
                    _showSnackBar('Failed: $e', isError: true);
                  }
                }
              },
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.send_rounded, color: Colors.white, size: 18),
                  SizedBox(width: 8),
                  Text('Send',
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

  Future<void> _showResetPasswordDialog() async {
    final emailController =
        TextEditingController(text: _emailController.text.trim());
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
              child: const Icon(Icons.key_rounded,
                  color: Colors.white, size: 24),
            ),
            const SizedBox(width: 14),
            const Expanded(
                child: Text('Reset Password',
                    style: TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold))),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Enter your email to receive a password reset link:'),
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
                style: const TextStyle(
                  color: AppTheme.primaryDark,
                  fontWeight: FontWeight.w500,
                ),
                decoration: const InputDecoration(
                  hintText: 'Email',
                  prefixIcon: Icon(Icons.email_outlined,
                      color: AppTheme.primaryTeal, size: 22),
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
            child: Text('Cancel',
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

                final success =
                    await AuthClient.sendPasswordResetEmail(email);
                if (!mounted) return;

                _showSnackBar(
                  success
                      ? 'Password reset link sent to $email'
                      : 'Failed to send password reset link',
                  isError: !success,
                );
              },
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.send_rounded, color: Colors.white, size: 18),
                  SizedBox(width: 8),
                  Text('Send',
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
    emailController.dispose();
  }

  Future<void> _showUpdatePasswordDialog() async {
    final passwordController = TextEditingController();
    final confirmController = TextEditingController();

    await showDialog(
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
                  gradient: AppTheme.orangeGradient,
                  borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.lock_reset_rounded,
                  color: Colors.white, size: 24),
            ),
            const SizedBox(width: 14),
            const Expanded(
                child: Text('New Password',
                    style: TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold))),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Create a new password for your Savora account.'),
            const SizedBox(height: 18),
            _buildDialogPasswordField(passwordController, 'New password'),
            const SizedBox(height: 12),
            _buildDialogPasswordField(confirmController, 'Confirm password'),
          ],
        ),
        actions: [
          Container(
            decoration: BoxDecoration(
                gradient: AppTheme.orangeGradient,
                borderRadius: BorderRadius.circular(12)),
            child: TextButton(
              onPressed: () async {
                final password = passwordController.text;
                final confirm = confirmController.text;

                if (password.length < 6) {
                  _showSnackBar('Password must be at least 6 characters',
                      isError: true);
                  return;
                }
                if (password != confirm) {
                  _showSnackBar('Password confirmation does not match',
                      isError: true);
                  return;
                }

                final success = await AuthClient.updatePassword(password);
                if (!dialogContext.mounted || !mounted) return;
                Navigator.pop(dialogContext);
                _showSnackBar(
                  success
                      ? 'Password updated. Please log in again.'
                      : 'Failed to update password',
                  isError: !success,
                );
                if (success) await AuthClient.logout();
              },
              child: const Text('Update',
                  style: TextStyle(
                      color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );

    passwordController.dispose();
    confirmController.dispose();
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
            const Text('Account Disabled'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Your account has been disabled by an administrator.',
                style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            const Text('Reason:',
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
              child: const Text('Close',
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
                            child: const Text('WELCOME',
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
                          Text('Your Culinary Journey Starts Here',
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
                            const SizedBox(height: 10),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                TextButton(
                                  onPressed: _showResendVerificationDialog,
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 6),
                                    minimumSize: Size.zero,
                                    tapTargetSize:
                                        MaterialTapTargetSize.shrinkWrap,
                                  ),
                                  child: const Text(
                                    'Email not verified?',
                                    style: TextStyle(
                                      color: AppTheme.primaryCoral,
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                TextButton(
                                  onPressed: _showResetPasswordDialog,
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 6),
                                    minimumSize: Size.zero,
                                    tapTargetSize:
                                        MaterialTapTargetSize.shrinkWrap,
                                  ),
                                  child: const Text(
                                    'Forgot password?',
                                    style: TextStyle(
                                      color: AppTheme.primaryTeal,
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 22),

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
                                        : const Text('LOG IN',
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
                                child: Text('OR',
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
                                                  'Continue with Google',
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

                    // Register link
                    _buildAuthSwitchCard(
                      text: 'Do not have an account?',
                      actionText: 'Register',
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const RegisterScreen(),
                        ),
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
          const TextSpan(text: 'By logging in, you agree to our '),
          WidgetSpan(
            alignment: PlaceholderAlignment.middle,
            child: GestureDetector(
              onTap: () => PrivacyModal.show(context),
              child: const Text(
                'Privacy',
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
                'Terms',
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
            text: '.',
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
        style: const TextStyle(
          color: AppTheme.primaryDark,
          fontSize: 15,
          fontWeight: FontWeight.w500,
        ),
        decoration: InputDecoration(
          filled: true,
          fillColor: Colors.grey.shade50,
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
          enabledBorder: OutlineInputBorder(
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

  Widget _buildAuthSwitchCard({
    required String text,
    required String actionText,
    required VoidCallback onTap,
  }) {
    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 460),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.2),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.4),
            width: 2,
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                text,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.95),
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            const SizedBox(width: 10),
            Material(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              child: InkWell(
                onTap: onTap,
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        actionText,
                        style: const TextStyle(
                          color: AppTheme.primaryCoral,
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(width: 6),
                      const Icon(
                        Icons.arrow_forward_rounded,
                        color: AppTheme.primaryCoral,
                        size: 18,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDialogPasswordField(
    TextEditingController controller,
    String hint,
  ) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.shade200, width: 1.5),
      ),
      child: TextField(
        controller: controller,
        obscureText: true,
        style: const TextStyle(
          color: AppTheme.primaryDark,
          fontWeight: FontWeight.w500,
        ),
        decoration: InputDecoration(
          hintText: hint,
          prefixIcon: const Icon(Icons.lock_outline_rounded,
              color: AppTheme.primaryOrange, size: 22),
          border: InputBorder.none,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        ),
      ),
    );
  }
}
