import 'package:flutter/material.dart';
import '../screens/home_screen.dart';
import '../screens/notification_screen.dart';
import '../screens/login_screen.dart';
import '../screens/ai_chat_screen.dart';
import '../services/auth_client.dart';
import '../services/notification_client.dart';

class CustomAppBar extends StatefulWidget implements PreferredSizeWidget {
  final bool showBackButton;
  final String? userId;

  const CustomAppBar({
    super.key,
    this.showBackButton = false,
    this.userId,
  });

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  State<CustomAppBar> createState() => _CustomAppBarState();
}

class _CustomAppBarState extends State<CustomAppBar>
    with SingleTickerProviderStateMixin {
  int _unreadCount = 0;
  late AnimationController _badgeController;
  late Animation<double> _badgeAnimation;

  @override
  void initState() {
    super.initState();
    _badgeController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync   : this,
    );
    _badgeAnimation = Tween<double>(begin: 1.0, end: 1.3).animate(
      CurvedAnimation(parent: _badgeController, curve: Curves.elasticOut),
    );
    _loadUnreadCount();
  }

  @override
  void dispose() {
    _badgeController.dispose();
    super.dispose();
  }

  Future<void> _loadUnreadCount() async {
    try {
      final userId = widget.userId;
      if (userId == null) return;
      final count = await NotificationClient.getUnreadCount(userId);
      if (mounted) {
        setState(() => _unreadCount = count);
        if (_unreadCount > 0) _badgeController.forward(from: 0);
      }
    } catch (e) {
      debugPrint('Error loading unread count: $e');
    }
  }

  Future<void> _signOut(BuildContext context) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape  : RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title  : Row(children: [
          Container(
            padding   : const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color        : Colors.red.shade50,
              borderRadius : BorderRadius.circular(12),
            ),
            child: Icon(Icons.logout_rounded, color: Colors.red.shade600, size: 24),
          ),
          const SizedBox(width: 16),
          const Expanded(
            child: Text('Keluar dari Akun',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ),
        ]),
        content: Text(
          'Apakah Anda yakin ingin keluar dari akun Savora?',
          style: TextStyle(fontSize: 15, color: Colors.grey.shade700, height: 1.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child    : Text('Batal', style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.w600)),
          ),
          Container(
            decoration: BoxDecoration(
              gradient    : LinearGradient(colors: [Colors.red.shade400, Colors.red.shade600]),
              borderRadius: BorderRadius.circular(12),
              boxShadow   : [BoxShadow(color: Colors.red.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 4))],
            ),
            child: TextButton(
              onPressed: () => Navigator.pop(context, true),
              child    : const Text('Keluar', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await AuthClient.logout();
      if (context.mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color    : Colors.white,
        boxShadow: [
          BoxShadow(
            color     : Colors.black.withValues(alpha: 0.06),
            blurRadius: 12,
            offset    : const Offset(0, 2),
          ),
        ],
      ),
      child: AppBar(
        backgroundColor: Colors.white,
        elevation      : 0,
        leadingWidth   : widget.showBackButton ? kToolbarHeight : 160,
        leading        : widget.showBackButton
            ? Padding(
                padding: const EdgeInsets.all(8),
                child  : Material(
                  color        : Colors.grey.shade100,
                  borderRadius : BorderRadius.circular(12),
                  child        : InkWell(
                    onTap        : () => Navigator.pop(context),
                    borderRadius : BorderRadius.circular(12),
                    child        : Center(
                      child: Icon(Icons.arrow_back_rounded, color: Colors.grey.shade800, size: 22),
                    ),
                  ),
                ),
              )
            : Padding(
                padding: const EdgeInsets.only(left: 16),
                child  : GestureDetector(
                  onTap: () => Navigator.pushAndRemoveUntil(
                    context,
                    MaterialPageRoute(builder: (_) => const HomeScreen()),
                    (route) => false,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children    : [
                      Container(
                        width     : 40, height: 40,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(colors: [Color(0xFF2B6CB0), Color(0xFFFF6B35)]),
                          shape   : BoxShape.circle,
                          boxShadow: [BoxShadow(color: const Color(0xFF2B6CB0).withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 2))],
                        ),
                        padding: const EdgeInsets.all(2),
                        child  : Container(
                          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                          child     : ClipOval(
                            child: Image.asset(
                              'assets/images/logo.png',
                              fit         : BoxFit.cover,
                              errorBuilder: (_, _, _) => const Icon(Icons.restaurant_rounded, size: 20, color: Color(0xFF2B6CB0)),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      ShaderMask(
                        shaderCallback: (bounds) => const LinearGradient(
                          colors: [Color(0xFF2B6CB0), Color(0xFFFF6B35)],
                        ).createShader(bounds),
                        child: const Text(
                          'Savora',
                          style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.white, letterSpacing: 0.5),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
        titleSpacing: 0,
        actions     : [
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child  : PopupMenuButton<String>(
              icon        : Stack(
                clipBehavior: Clip.none,
                children    : [
                  Container(
                    width     : 40, height: 40,
                    decoration: BoxDecoration(
                      color       : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.menu_rounded, color: Colors.grey.shade800, size: 22),
                  ),
                  if (_unreadCount > 0)
                    Positioned(
                      right: -2, top: -2,
                      child: ScaleTransition(
                        scale: _badgeAnimation,
                        child: Container(
                          padding    : const EdgeInsets.all(4),
                          decoration : BoxDecoration(
                            color    : const Color(0xFFFF6B35),
                            shape    : BoxShape.circle,
                            border   : Border.all(color: Colors.white, width: 1.5),
                          ),
                          constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                          child      : Text(
                            _unreadCount > 99 ? '99+' : _unreadCount.toString(),
                            style    : const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
              shape       : RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              offset      : const Offset(0, 48),
              elevation   : 4,
              color       : Colors.white,
              onSelected  : (value) async {
                switch (value) {
                  case 'ai_chat':
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AIChatScreen()));
                    break;
                  case 'notifications':
                    await Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationScreen()));
                    _loadUnreadCount();
                    break;
                  case 'logout':
                    _signOut(context);
                    break;
                }
              },
              itemBuilder : (_) => [
                // Chef AI
                PopupMenuItem(
                  value: 'ai_chat',
                  child: Row(
                    children: [
                      Container(
                        padding   : const EdgeInsets.all(7),
                        decoration: BoxDecoration(
                          color       : Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.psychology_rounded, size: 18, color: Colors.black87),
                      ),
                      const SizedBox(width: 12),
                      const Text('Chef AI', style: TextStyle(fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),

                // Notifikasi
                PopupMenuItem(
                  value: 'notifications',
                  child: Row(
                    children: [
                      Container(
                        padding   : const EdgeInsets.all(7),
                        decoration: BoxDecoration(
                          color       : Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(Icons.notifications_rounded, size: 18, color: Colors.grey.shade800),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Text('Notifikasi', style: TextStyle(fontWeight: FontWeight.w600)),
                      ),
                      if (_unreadCount > 0)
                        Container(
                          padding   : const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color       : const Color(0xFFFF6B35),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            '$_unreadCount',
                            style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ),
                    ],
                  ),
                ),

                const PopupMenuDivider(),

                // Keluar
                PopupMenuItem(
                  value: 'logout',
                  child: Row(
                    children: [
                      Container(
                        padding   : const EdgeInsets.all(7),
                        decoration: BoxDecoration(
                          color       : Colors.red.shade50,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(Icons.logout_rounded, size: 18, color: Colors.red.shade600),
                      ),
                      const SizedBox(width: 12),
                      Text('Keluar', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.red.shade600)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}