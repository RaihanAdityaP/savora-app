import 'package:flutter/material.dart';
import '../../services/ai_chat_client.dart';
import '../../services/app_settings_service.dart';
import '../../widgets/theme.dart';
import 'chat_screen.dart';
import 'settings_screen.dart';

class AIChatHistoryScreen extends StatefulWidget {
  const AIChatHistoryScreen({super.key});

  @override
  State<AIChatHistoryScreen> createState() => _AIChatHistoryScreenState();
}

class _AIChatHistoryScreenState extends State<AIChatHistoryScreen> {
  List<Map<String, dynamic>> _conversations = [];
  bool _isLoading = true;

  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _loadConversations();
  }

  // ─────────────────────────────────────────────
  // LOAD
  // ─────────────────────────────────────────────

  Future<void> _loadConversations() async {
    setState(() => _isLoading = true);
    final list = await AIChatClient.listConversations(limit: 50);
    if (mounted) {
      setState(() {
        _conversations = list;
        _isLoading = false;
      });
    }
  }

  // ─────────────────────────────────────────────
  // DELETE ALL
  // ─────────────────────────────────────────────
  Future<void> _deleteAll() async {
    if (_conversations.isEmpty) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Icon(Icons.delete_sweep_rounded, color: Colors.red),
            const SizedBox(width: 8),
            Text(_t('Delete All', 'Hapus Semua')),
          ],
        ),
        content: Text(
          _t(
            'Delete all ${_conversations.length} conversations? This cannot be undone.',
            'Hapus semua ${_conversations.length} conversation? Tindakan ini tidak bisa dibatalkan.',
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
              backgroundColor: Colors.red.shade600,
              foregroundColor: Colors.white,
            ),
            child: Text(_t('Delete All', 'Hapus Semua')),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await AIChatClient.deleteAllConversations();
      _loadConversations();
    }
  }

  // ─────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────

  String _formatTime(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      final now = DateTime.now();
      final diff = now.difference(dt);

      if (diff.inMinutes < 1) {
        return _t('Just now', 'Baru saja');
      }
      if (diff.inHours < 1) {
        return _t('${diff.inMinutes} min ago', '${diff.inMinutes} menit lalu');
      }
      if (diff.inDays < 1) {
        return _t('${diff.inHours} hours ago', '${diff.inHours} jam lalu');
      }
      if (diff.inDays < 7) {
        return _t('${diff.inDays} days ago', '${diff.inDays} hari lalu');
      }
      return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {
      return '';
    }
  }

  String _getProviderLabel(String? provider) {
    switch (provider) {
      case 'openrouter':
        return 'OpenRouter';
      default:
        return 'Groq';
    }
  }

  Color _getProviderColor(String? provider) {
    return provider == 'openrouter' ? Colors.purple : AppTheme.primaryTeal;
  }

  // ─────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: _buildAppBar(),
      body: Column(
        children: [
          _buildNewChatButton(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: AppTheme.surfaceColor,
      elevation: 1,
      leading: IconButton(
        icon: Icon(Icons.arrow_back_rounded, color: AppTheme.textPrimary),
        onPressed: () => Navigator.pop(context),
      ),
      title: Text(
        'Chef AI',
        style: TextStyle(
          color: AppTheme.textPrimary,
          fontWeight: FontWeight.bold,
          fontSize: 18,
        ),
      ),
      actions: [
        IconButton(
          icon: Icon(Icons.tune_rounded, color: AppTheme.textPrimary),
          tooltip: 'AI Settings',
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const AISettingsScreen()),
          ),
        ),
        if (_conversations.isNotEmpty)
          IconButton(
            icon: Icon(Icons.delete_sweep_rounded, color: Colors.red),
            tooltip: _t('Delete All', 'Hapus Semua'),
            onPressed: _deleteAll,
          ),
      ],
    );
  }

  Widget _buildNewChatButton() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
      child: SizedBox(
        width: double.infinity,
        height: 56,
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: AppTheme.accentGradient,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primaryCoral.withValues(alpha: 0.22),
                blurRadius: 16,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const AIChatScreen()),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.add_rounded, color: Colors.white, size: 26),
                  const SizedBox(width: 10),
                  Text(
                    _t('New Chat with Chef AI', 'Chat Baru dengan Chef AI'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: AppTheme.primaryCoral),
      );
    }

    if (_conversations.isEmpty) {
      return _buildEmptyState();
    }

    return RefreshIndicator(
      onRefresh: _loadConversations,
      color: AppTheme.primaryCoral,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
        itemCount: _conversations.length + 1,
        itemBuilder: (_, index) {
          if (index == 0) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(
                _t('Chat History', 'Riwayat Chat'),
                style: TextStyle(
                  color: AppTheme.textPrimary,
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                ),
              ),
            );
          }
          return _buildConversationCard(_conversations[index - 1]);
        },
      ),
    );
  }

  Widget _buildConversationCard(Map<String, dynamic> conv) {
    final id = conv['id']?.toString() ?? '';
    final title = (conv['title'] ?? _t('New Chat', 'Chat Baru')).toString();
    final lastMessage =
        (conv['last_message'] ?? _t('No messages yet', 'Belum ada pesan'))
            .toString();
    final provider = conv['provider'] ?? 'groq';
    final updatedAt = conv['updated_at'];
    final msgCount = conv['message_count'] ?? 0;

    return Dismissible(
      key: Key(id),
      direction: DismissDirection.endToStart,
      confirmDismiss: (_) => showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(_t('Delete Chat', 'Hapus Chat')),
          content: Text(
            _t('Delete this conversation?', 'Hapus conversation ini?'),
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
              ),
              child: Text(_t('Delete', 'Hapus')),
            ),
          ],
        ),
      ),
      onDismissed: (_) => AIChatClient.deleteConversation(id),
      background: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.red.shade500,
          borderRadius: BorderRadius.circular(16),
        ),
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.delete_rounded, color: Colors.white, size: 28),
            const SizedBox(height: 4),
            Text(
              _t('Delete', 'Hapus'),
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
      child: GestureDetector(
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) =>
                AIChatScreen(conversationId: id, initialTitle: title),
          ),
        ).then((_) => _loadConversations()),
        child: Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppTheme.surfaceColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppTheme.borderColor),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              // Icon
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.psychology_rounded,
                  color: Colors.white,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            title,
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textPrimary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        Text(
                          _formatTime(updatedAt),
                          style: TextStyle(
                            fontSize: 11,
                            color: AppTheme.textMuted,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      lastMessage,
                      style: TextStyle(
                        fontSize: 13,
                        color: AppTheme.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        // Provider chip
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: _getProviderColor(
                              provider,
                            ).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            _getProviderLabel(provider),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: _getProviderColor(provider),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        // Message count
                        Icon(
                          Icons.chat_bubble_outline_rounded,
                          size: 12,
                          color: AppTheme.textMuted,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          _t('$msgCount messages', '$msgCount pesan'),
                          style: TextStyle(
                            fontSize: 11,
                            color: AppTheme.textMuted,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right_rounded, color: AppTheme.textMuted),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(28),
            decoration: BoxDecoration(
              gradient: AppTheme.cardGradient,
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.chat_bubble_outline_rounded,
              size: 64,
              color: AppTheme.primaryCoral.withValues(alpha: 0.6),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            _t('No Chat History Yet', 'Belum Ada Riwayat Chat'),
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _t(
              'Start chatting with Savora Chef AI!',
              'Mulai chat dengan Chef AI Savora!',
            ),
            style: TextStyle(fontSize: 14, color: AppTheme.textSecondary),
          ),
        ],
      ),
    );
  }
}
