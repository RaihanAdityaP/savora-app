import 'package:flutter/material.dart';
import '../../services/app_settings_service.dart';
import '../../services/user_client.dart';
import '../../widgets/theme.dart';
import '../profile_screen.dart';

class FollowListScreen extends StatefulWidget {
  final String userId;
  final bool followers;
  final String? username;

  const FollowListScreen({
    super.key,
    required this.userId,
    required this.followers,
    this.username,
  });

  @override
  State<FollowListScreen> createState() => _FollowListScreenState();
}

class _FollowListScreenState extends State<FollowListScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _users = [];
  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    setState(() => _isLoading = true);
    final users = widget.followers
        ? await UserClient.getFollowers(widget.userId)
        : await UserClient.getFollowing(widget.userId);
    if (!mounted) return;
    setState(() {
      _users = users;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.followers ? _t('Followers', 'Pengikut') : _t('Following', 'Mengikuti');

    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: AppTheme.textPrimary,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: TextStyle(fontWeight: FontWeight.bold)),
            if (widget.username != null)
              Text(widget.username!, style: TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadUsers,
        color: AppTheme.primaryCoral,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryCoral))
            : _users.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(20),
                    children: [
                      const SizedBox(height: 120),
                      AppTheme.buildEmptyState(
                        icon: Icons.people_outline_rounded,
                        title: widget.followers
                            ? _t('No followers yet', 'Belum ada pengikut')
                            : _t('Not following anyone yet', 'Belum mengikuti siapa pun'),
                      ),
                    ],
                  )
                : ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                    itemCount: _users.length,
                    itemBuilder: (context, index) => _userTile(_users[index]),
                  ),
      ),
    );
  }

  Widget _userTile(Map<String, dynamic> user) {
    final userId = user['id']?.toString();
    final avatar = user['avatar_url']?.toString();

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.primaryCoral.withValues(alpha: 0.14)),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundImage: avatar != null && avatar.isNotEmpty ? NetworkImage(avatar) : null,
          backgroundColor: AppTheme.primaryCoral.withValues(alpha: 0.12),
          child: avatar == null || avatar.isEmpty ? const Icon(Icons.person_rounded, color: AppTheme.primaryCoral) : null,
        ),
        title: Text(user['username']?.toString() ?? 'Unknown', style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: user['full_name'] != null ? Text(user['full_name'].toString()) : null,
        trailing: const Icon(Icons.chevron_right_rounded),
        onTap: userId == null
            ? null
            : () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => ProfileScreen(userId: userId)));
              },
      ),
    );
  }
}
