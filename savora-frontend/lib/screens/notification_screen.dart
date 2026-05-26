import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/app_settings_service.dart';
import '../services/user_client.dart';
import '../widgets/theme.dart';
import 'profile_screen.dart';
import 'recipes/detail_screen.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen>
    with SingleTickerProviderStateMixin {
  List<Map<String, dynamic>> _notifications = [];
  bool _isLoading = true;
  int _unreadCount = 0;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );
    _fadeAnimation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    );
    _loadNotifications();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadNotifications() async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService.get('/notifications');
      if (mounted) {
        final notifs = List<Map<String, dynamic>>.from(response['data'] ?? []);
        final deduped = _dedupeNotifications(notifs);
        setState(() {
          _notifications = deduped;
          _unreadCount = deduped.where((n) => n['is_read'] == false).length;
          _isLoading = false;
        });
        _animationController.forward(from: 0);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  List<Map<String, dynamic>> _dedupeNotifications(
    List<Map<String, dynamic>> notifications,
  ) {
    final seen = <String>{};
    final deduped = <Map<String, dynamic>>[];
    for (final notification in notifications) {
      final key = [
        notification['type']?.toString() ?? '',
        notification['related_entity_type']?.toString() ?? '',
        notification['related_entity_id']?.toString() ?? '',
        notification['title']?.toString() ?? '',
        notification['message']?.toString() ?? '',
      ].join('|');
      if (seen.add(key)) deduped.add(notification);
    }
    return deduped;
  }

  Future<void> _markAsRead(String notificationId) async {
    try {
      await ApiService.post('/notifications/$notificationId/read', {});
      _loadNotifications();
    } catch (e) {
      debugPrint('Error marking notification as read: $e');
    }
  }

  Future<void> _markAllAsRead() async {
    try {
      await ApiService.post('/notifications/read-all', {});
      _loadNotifications();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle, color: Colors.white),
                const SizedBox(width: 8),
                Text(
                  _t(
                    'All notifications marked as read',
                    'Semua notifikasi ditandai sudah dibaca',
                  ),
                ),
              ],
            ),
            backgroundColor: Colors.green.shade600,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  Future<void> _deleteAllNotifications() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.delete_sweep, color: Colors.red, size: 28),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                _t('Delete All Notifications', 'Hapus Semua Notifikasi'),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
        content: Text(
          _t(
            'Are you sure you want to delete all notifications?',
            'Apakah Anda yakin ingin menghapus semua notifikasi?',
          ),
        ),
        actions: [
          Wrap(
            alignment: WrapAlignment.end,
            spacing: 8,
            runSpacing: 8,
            children: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: Text(_t('Cancel', 'Batal')),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: Text(_t('Delete All', 'Hapus Semua')),
              ),
            ],
          ),
        ],
      ),
    );
    if (confirm != true) return;

    try {
      await ApiService.delete('/notifications');
      _loadNotifications();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle, color: Colors.white),
                const SizedBox(width: 8),
                Text(
                  _t(
                    'All notifications deleted successfully',
                    'Semua notifikasi berhasil dihapus',
                  ),
                ),
              ],
            ),
            backgroundColor: Colors.green.shade600,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  String _normalizeRelatedId(dynamic rawId) {
    final value = rawId?.toString().trim() ?? '';
    if (value.isEmpty) return '';
    return value.replaceAll('"', '').replaceAll("'", '');
  }

  Future<void> _deleteNotification(String notificationId) async {
    try {
      await ApiService.delete('/notifications/$notificationId');
      _loadNotifications();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.delete, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Text(_t('Notification deleted', 'Notifikasi dihapus')),
              ],
            ),
            duration: const Duration(seconds: 2),
            behavior: SnackBarBehavior.floating,
            backgroundColor: Colors.red.shade400,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  Future<void> _respondToFollowRequest(String requestId, bool accept) async {
    final userId = ApiService.currentUserId;
    if (userId == null || userId.isEmpty) return;

    try {
      final success = await UserClient.respondToFollowRequest(
        targetUserId: userId,
        requestId: requestId,
        accept: accept,
      );
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success
                ? accept
                      ? _t(
                          'Follow request accepted',
                          'Permintaan follow diterima',
                        )
                      : _t(
                          'Follow request rejected',
                          'Permintaan follow ditolak',
                        )
                : _t('Failed to respond', 'Gagal memproses permintaan'),
          ),
          backgroundColor: success
              ? Colors.green.shade600
              : Colors.red.shade600,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
        ),
      );

      if (success) _loadNotifications();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  void _handleNotificationTap(Map<String, dynamic> notification) {
    if (!notification['is_read']) {
      _markAsRead(notification['id'].toString());
    }

    final type = notification['type'] ?? '';
    final relatedId = _normalizeRelatedId(notification['related_entity_id']);
    if (relatedId.isEmpty) return;

    switch (type) {
      case 'new_follower':
      case 'follow_request_approved':
      case 'follow_request_rejected':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ProfileScreen(userId: relatedId),
          ),
        );
        break;
      case 'follow_request':
        break;
      case 'new_recipe_from_following':
      case 'recipe_approved':
      case 'recipe_rejected':
      case 'new_comment':
      case 'new_like':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => DetailScreen(recipeId: relatedId),
          ),
        );
        break;
      default:
        break;
    }
  }

  String _getTimeAgo(String timestamp) {
    final dateTime = DateTime.parse(timestamp);
    final now = DateTime.now();
    final difference = now.difference(dateTime);
    if (difference.inDays > 7) {
      return '${dateTime.day}/${dateTime.month}/${dateTime.year}';
    }
    if (difference.inDays > 0) {
      return _t(
        '${difference.inDays} days ago',
        '${difference.inDays} hari lalu',
      );
    }
    if (difference.inHours > 0) {
      return _t(
        '${difference.inHours} hours ago',
        '${difference.inHours} jam lalu',
      );
    }
    if (difference.inMinutes > 0) {
      return _t(
        '${difference.inMinutes} minutes ago',
        '${difference.inMinutes} menit lalu',
      );
    }
    return _t('Just now', 'Baru saja');
  }

  Color _getNotificationColor(String type) {
    switch (type) {
      case 'recipe_approved':
        return Colors.green.shade500;
      case 'recipe_rejected':
        return Colors.red.shade500;
      case 'new_follower':
      case 'follow_request':
        return AppTheme.primaryTeal;
      case 'follow_request_approved':
        return Colors.green.shade500;
      case 'follow_request_rejected':
        return Colors.red.shade500;
      case 'new_recipe_from_following':
        return AppTheme.primaryOrange;
      case 'new_like':
        return AppTheme.primaryCoral;
      case 'new_comment':
        return AppTheme.primaryYellow;
      case 'admin':
        return AppTheme.primaryCoral;
      default:
        return Colors.grey.shade500;
    }
  }

  IconData _getNotificationIcon(String type) {
    switch (type) {
      case 'recipe_approved':
        return Icons.check_circle_rounded;
      case 'recipe_rejected':
        return Icons.cancel_rounded;
      case 'new_follower':
        return Icons.person_add_rounded;
      case 'follow_request':
        return Icons.person_add_alt_1_rounded;
      case 'follow_request_approved':
        return Icons.verified_user_rounded;
      case 'follow_request_rejected':
        return Icons.person_remove_rounded;
      case 'new_recipe_from_following':
        return Icons.restaurant_rounded;
      case 'new_like':
        return Icons.favorite_rounded;
      case 'new_comment':
        return Icons.chat_bubble_rounded;
      case 'admin':
        return Icons.admin_panel_settings_rounded;
      default:
        return Icons.notifications_rounded;
    }
  }

  /// Builds popup menu items. "Mark all as read" hanya muncul kalau ada unread.
  List<PopupMenuEntry<String>> _buildMenuItems(BuildContext context) {
    final items = <PopupMenuEntry<String>>[];

    if (_unreadCount > 0) {
      items.add(
        PopupMenuItem(
          value: 'read_all',
          child: Row(
            children: [
              Icon(
                Icons.done_all_rounded,
                color: AppTheme.primaryTeal,
                size: 20,
              ),
              const SizedBox(width: 10),
              Text(
                _t('Mark All as Read', 'Tandai Semua Dibaca'),
                style: TextStyle(color: AppTheme.primaryTeal),
              ),
            ],
          ),
        ),
      );
      items.add(const PopupMenuDivider());
    }

    items.add(
      PopupMenuItem(
        value: 'delete_all',
        child: Row(
          children: [
            const Icon(Icons.delete_sweep_rounded, color: Colors.red, size: 20),
            const SizedBox(width: 10),
            Text(_t('Delete All', 'Hapus Semua')),
          ],
        ),
      ),
    );

    return items;
  }

  String _notificationTitle(Map<String, dynamic> notification) {
    final type = notification['type']?.toString() ?? 'system';
    switch (type) {
      case 'recipe_approved':
        return _t('Recipe Approved', 'Resep Disetujui');
      case 'recipe_rejected':
        return _t('Recipe Rejected', 'Resep Ditolak');
      case 'new_follower':
        return _t('New Follower', 'Follower Baru');
      case 'follow_request':
        return _t('Follow Request', 'Permintaan Follow');
      case 'follow_request_approved':
        return _t('Follow Request Accepted', 'Permintaan Follow Diterima');
      case 'follow_request_rejected':
        return _t('Follow Request Rejected', 'Permintaan Follow Ditolak');
      case 'new_like':
        return _t('New Like', 'Like Baru');
      case 'new_comment':
        return _t('New Comment', 'Komentar Baru');
      case 'new_recipe_from_following':
        return _t('New Recipe', 'Resep Baru');
      default:
        return notification['title']?.toString() ??
            _t('Notification', 'Notifikasi');
    }
  }

  String _notificationMessage(Map<String, dynamic> notification) {
    final type = notification['type']?.toString() ?? 'system';
    final raw = notification['message']?.toString() ?? '';

    switch (type) {
      case 'recipe_approved':
        final title = _quotedValue(raw);
        return title == null
            ? _t(
                'Your recipe was approved and published.',
                'Resep Anda telah disetujui dan dipublikasikan.',
              )
            : _t(
                "Your recipe '$title' was approved and published.",
                "Resep '$title' Anda telah disetujui dan dipublikasikan.",
              );
      case 'recipe_rejected':
        final title = _quotedValue(raw);
        final reason = _reasonFromMessage(raw);
        final reasonSuffix = reason == null
            ? ''
            : _t(' Reason: $reason', ' Alasan: $reason');
        return title == null
            ? _t(
                'Your recipe was rejected.$reasonSuffix',
                'Resep Anda ditolak.$reasonSuffix',
              )
            : _t(
                "Your recipe '$title' was rejected.$reasonSuffix",
                "Resep '$title' ditolak.$reasonSuffix",
              );
      case 'new_follower':
        final actor = _actorFromMessage(
          raw,
          englishMarkers: [' started following you!', ' started following you'],
          indonesianMarkers: [
            ' mulai mengikuti Anda!',
            ' mulai mengikuti Anda',
          ],
        );
        return _t(
          '$actor started following you!',
          '$actor mulai mengikuti Anda!',
        );
      case 'follow_request':
        final requesterName =
            notification['follow_requester_name']
                    ?.toString()
                    .trim()
                    .isNotEmpty ==
                true
            ? notification['follow_requester_name'].toString().trim()
            : _followRequesterName(raw);
        return _t(
          '$requesterName wants to follow your private account.',
          '$requesterName ingin mengikuti akun private Anda.',
        );
      case 'follow_request_approved':
        return _t(
          'Your follow request was accepted.',
          'Permintaan follow Anda diterima.',
        );
      case 'follow_request_rejected':
        return _t(
          'Your follow request was rejected.',
          'Permintaan follow Anda ditolak.',
        );
      case 'new_like':
        final actor = _actorFromMessage(
          raw,
          englishMarkers: [' liked your recipe', ' liked'],
          indonesianMarkers: [' menyukai resep', ' menyukai'],
        );
        final title = _quotedValue(raw);
        return title == null
            ? _t('$actor liked your recipe.', '$actor menyukai resep Anda.')
            : _t(
                "$actor liked your recipe '$title'.",
                "$actor menyukai resep '$title'.",
              );
      case 'new_comment':
        final actor = _actorFromMessage(
          raw,
          englishMarkers: [' commented on your recipe', ' commented'],
          indonesianMarkers: [' berkomentar di resep', ' berkomentar'],
        );
        final title = _quotedValue(raw);
        return title == null
            ? _t(
                '$actor commented on your recipe.',
                '$actor berkomentar di resep Anda.',
              )
            : _t(
                "$actor commented on your recipe '$title'.",
                "$actor berkomentar di resep '$title'.",
              );
      default:
        return raw;
    }
  }

  String? _quotedValue(String message) {
    final match = RegExp("'([^']+)'").firstMatch(message);
    return match?.group(1);
  }

  String? _reasonFromMessage(String message) {
    for (final marker in ['Reason:', 'Alasan:']) {
      if (message.contains(marker)) {
        final reason = message.split(marker).last.trim();
        if (reason.isNotEmpty) return reason;
      }
    }
    return null;
  }

  String _actorFromMessage(
    String message, {
    required List<String> englishMarkers,
    required List<String> indonesianMarkers,
  }) {
    for (final marker in [...englishMarkers, ...indonesianMarkers]) {
      if (message.contains(marker)) {
        final actor = message.split(marker).first.trim();
        if (actor.isNotEmpty) return actor;
      }
    }
    return _t('Someone', 'Seseorang');
  }

  String _followRequesterName(String message) {
    const idMarker = ' ingin mengikuti akun private Anda.';
    const enMarker = ' wants to follow your private account.';

    if (message.contains(idMarker)) {
      final name = message.split(idMarker).first.trim();
      if (name.isNotEmpty) return name;
    }
    if (message.contains(enMarker)) {
      final name = message.split(enMarker).first.trim();
      if (name.isNotEmpty) return name;
    }
    return _t('Someone', 'Seseorang');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      body: CustomScrollView(
        slivers: [
          // ── SLIVER APP BAR ──
          SliverAppBar(
            expandedHeight: 140,
            floating: false,
            pinned: true,
            backgroundColor: AppTheme.backgroundLight,
            elevation: 0,
            scrolledUnderElevation: 1,
            shadowColor: Colors.black.withValues(alpha: 0.08),
            leading: Padding(
              padding: const EdgeInsets.all(8.0),
              child: Container(
                decoration: BoxDecoration(
                  color: AppTheme.surfaceColor,
                  shape: BoxShape.circle,
                  boxShadow: AppTheme.cardShadow,
                ),
                child: IconButton(
                  icon: Icon(Icons.arrow_back, color: AppTheme.textPrimary),
                  onPressed: () => Navigator.pop(context),
                ),
              ),
            ),
            actions: [
              Padding(
                padding: const EdgeInsets.all(8.0),
                child: Container(
                  decoration: BoxDecoration(
                    color: AppTheme.surfaceColor,
                    shape: BoxShape.circle,
                    boxShadow: AppTheme.cardShadow,
                  ),
                  child: PopupMenuButton<String>(
                    icon: Icon(
                      Icons.more_vert_rounded,
                      color: AppTheme.textPrimary,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    onSelected: (value) {
                      if (value == 'read_all') _markAllAsRead();
                      if (value == 'delete_all') _deleteAllNotifications();
                    },
                    itemBuilder: _buildMenuItems,
                  ),
                ),
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 60, 24, 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryTeal.withValues(
                                alpha: 0.1,
                              ),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: AppTheme.primaryTeal.withValues(
                                  alpha: 0.25,
                                ),
                                width: 1.5,
                              ),
                            ),
                            child: const Icon(
                              Icons.notifications_active_rounded,
                              color: AppTheme.primaryTeal,
                              size: 28,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _t('Notifications', 'Notifikasi'),
                                  style: AppTheme.headingMedium.copyWith(
                                    color: AppTheme.textPrimary,
                                  ),
                                ),
                                if (_unreadCount > 0)
                                  Container(
                                    margin: const EdgeInsets.only(top: 4),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 4,
                                    ),
                                    decoration: BoxDecoration(
                                      color: AppTheme.primaryOrange.withValues(
                                        alpha: 0.1,
                                      ),
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color: AppTheme.primaryOrange
                                            .withValues(alpha: 0.3),
                                      ),
                                    ),
                                    child: Text(
                                      _t(
                                        '$_unreadCount new',
                                        '$_unreadCount baru',
                                      ),
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: AppTheme.primaryOrange,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),

          _isLoading
              ? const SliverFillRemaining(
                  child: Center(
                    child: CircularProgressIndicator(
                      valueColor: AlwaysStoppedAnimation<Color>(
                        AppTheme.primaryTeal,
                      ),
                    ),
                  ),
                )
              : _notifications.isEmpty
              ? SliverFillRemaining(child: _buildEmptyState())
              : SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => FadeTransition(
                        opacity: _fadeAnimation,
                        child: SlideTransition(
                          position:
                              Tween<Offset>(
                                begin: const Offset(0.3, 0),
                                end: Offset.zero,
                              ).animate(
                                CurvedAnimation(
                                  parent: _animationController,
                                  curve: Interval(
                                    (index * 0.1).clamp(0.0, 1.0),
                                    1.0,
                                    curve: Curves.easeOut,
                                  ),
                                ),
                              ),
                          child: _buildNotificationCard(_notifications[index]),
                        ),
                      ),
                      childCount: _notifications.length,
                    ),
                  ),
                ),
          const SliverToBoxAdapter(child: SizedBox(height: 20)),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              gradient: AppTheme.cardGradient,
              shape: BoxShape.circle,
              boxShadow: AppTheme.cardShadow,
            ),
            child: Icon(
              Icons.notifications_off_rounded,
              size: 80,
              color: Colors.grey.shade400,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            _t('No notifications', 'Tidak ada notifikasi'),
            style: TextStyle(
              fontSize: 20,
              color: AppTheme.textPrimary,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _t(
              'Notifications will appear here',
              'Notifikasi akan muncul di sini',
            ),
            style: AppTheme.bodySmall,
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationCard(Map<String, dynamic> notification) {
    final isRead = notification['is_read'] == true;
    final type = notification['type'] ?? 'system';
    final color = _getNotificationColor(type);
    final icon = _getNotificationIcon(type);
    final relatedId = _normalizeRelatedId(notification['related_entity_id']);

    // Follow request: tampil tombol selama statusnya pending (belum di-accept/reject),
    // tidak terikat is_read karena user mungkin sudah baca tapi belum merespons
    final followRequestStatus =
        notification['follow_request_status']?.toString() ?? '';
    final showFollowRequestActions =
        type == 'follow_request' &&
        relatedId.isNotEmpty &&
        followRequestStatus == 'pending';

    return Dismissible(
      key: Key(notification['id'].toString()),
      direction: DismissDirection.endToStart,
      confirmDismiss: (direction) async => await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          title: Row(
            children: [
              const Icon(Icons.delete_rounded, color: Colors.red, size: 24),
              const SizedBox(width: 8),
              Text(_t('Delete Notification', 'Hapus Notifikasi')),
            ],
          ),
          content: Text(
            _t(
              'Are you sure you want to delete this notification?',
              'Apakah Anda yakin ingin menghapus notifikasi ini?',
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(_t('Cancel', 'Batal')),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: Text(_t('Delete', 'Hapus')),
            ),
          ],
        ),
      ),
      background: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.red.shade400, Colors.red.shade600],
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.delete_rounded, color: Colors.white, size: 32),
            const SizedBox(height: 4),
            Text(
              _t('Delete', 'Hapus'),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
      onDismissed: (direction) =>
          _deleteNotification(notification['id'].toString()),
      child: GestureDetector(
        onTap: () => _handleNotificationTap(notification),
        child: Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: BoxDecoration(
            color: isRead
                ? AppTheme.surfaceColor
                : (AppTheme.isDarkMode
                      ? AppTheme.primaryCoral.withValues(alpha: 0.08)
                      : Colors.white),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isRead
                  ? AppTheme.borderColor
                  : color.withValues(alpha: 0.5),
              width: isRead ? 1 : 2,
            ),
            boxShadow: [
              BoxShadow(
                color: isRead
                    ? Colors.black.withValues(alpha: 0.05)
                    : color.withValues(alpha: 0.15),
                blurRadius: isRead ? 8 : 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Stack(
            children: [
              if (!isRead)
                Positioned.fill(
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          color.withValues(alpha: 0.05),
                          Colors.transparent,
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Icon box
                    Container(
                      width: 56,
                      height: 56,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            color.withValues(alpha: 0.2),
                            color.withValues(alpha: 0.1),
                          ],
                        ),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: color.withValues(alpha: 0.3),
                          width: 2,
                        ),
                      ),
                      child: Icon(icon, color: color, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Title row
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  _notificationTitle(notification),
                                  style: TextStyle(
                                    fontWeight: isRead
                                        ? FontWeight.w600
                                        : FontWeight.bold,
                                    fontSize: 15,
                                    color: AppTheme.textPrimary,
                                  ),
                                ),
                              ),
                              if (!isRead)
                                Container(
                                  width: 10,
                                  height: 10,
                                  decoration: BoxDecoration(
                                    color: color,
                                    shape: BoxShape.circle,
                                    boxShadow: [
                                      BoxShadow(
                                        color: color.withValues(alpha: 0.45),
                                        blurRadius: 5,
                                        spreadRadius: 1,
                                      ),
                                    ],
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          // Message
                          Text(
                            _notificationMessage(notification),
                            style: AppTheme.bodyMedium.copyWith(
                              color: AppTheme.textSecondary,
                              height: 1.4,
                            ),
                          ),

                          // ── FOLLOW REQUEST ACTIONS ──
                          if (showFollowRequestActions) ...[
                            const SizedBox(height: 14),
                            Row(
                              children: [
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () => _respondToFollowRequest(
                                      relatedId,
                                      false,
                                    ),
                                    icon: Icon(
                                      Icons.close_rounded,
                                      size: 15,
                                      color: Colors.red.shade600,
                                    ),
                                    label: Text(_t('Reject', 'Tolak')),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: Colors.red.shade600,
                                      side: BorderSide(
                                        color: Colors.red.shade200,
                                        width: 1.5,
                                      ),
                                      padding: const EdgeInsets.symmetric(
                                        vertical: 10,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () => _respondToFollowRequest(
                                      relatedId,
                                      true,
                                    ),
                                    icon: const Icon(
                                      Icons.check_rounded,
                                      size: 15,
                                      color: Colors.white,
                                    ),
                                    label: Text(_t('Accept', 'Terima')),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppTheme.primaryTeal,
                                      foregroundColor: Colors.white,
                                      elevation: 0,
                                      padding: const EdgeInsets.symmetric(
                                        vertical: 10,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],

                          const SizedBox(height: 10),
                          // Timestamp
                          Row(
                            children: [
                              Icon(
                                Icons.access_time_rounded,
                                size: 14,
                                color: Colors.grey.shade400,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                _getTimeAgo(notification['created_at']),
                                style: AppTheme.bodySmall.copyWith(
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
