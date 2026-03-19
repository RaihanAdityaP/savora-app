import 'package:flutter/material.dart';
import '../services/ai_chat_client.dart';
import '../widgets/theme.dart';
import 'ai_chat_screen.dart';
import 'ai_settings_screen.dart';

class AIChatHistoryScreen extends StatefulWidget {
  const AIChatHistoryScreen({super.key});

  @override
  State<AIChatHistoryScreen> createState() => _AIChatHistoryScreenState();
}

class _AIChatHistoryScreenState extends State<AIChatHistoryScreen> {
  final _searchController = TextEditingController();

  List<Map<String, dynamic>> _conversations = [];
  bool   _isLoading    = true;
  String _searchQuery  = '';

  @override
  void initState() {
    super.initState();
    _loadConversations();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  // ─────────────────────────────────────────────
  // LOAD
  // ─────────────────────────────────────────────

  Future<void> _loadConversations() async {
    setState(() => _isLoading = true);
    final list = await AIChatClient.listConversations(
      search: _searchQuery,
      limit : 50,
    );
    if (mounted) {
      setState(() {
        _conversations = list;
        _isLoading     = false;
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
        shape  : RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title  : const Row(children: [
          Icon(Icons.delete_sweep_rounded, color: Colors.red),
          SizedBox(width: 8),
          Text('Hapus Semua'),
        ]),
        content: Text(
          'Hapus semua ${_conversations.length} conversation? Tindakan ini tidak bisa dibatalkan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child    : const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style    : ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade600,
              foregroundColor: Colors.white,
            ),
            child: const Text('Hapus Semua'),
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
      final dt   = DateTime.parse(dateStr).toLocal();
      final now  = DateTime.now();
      final diff = now.difference(dt);

      if (diff.inMinutes < 1)  return 'Baru saja';
      if (diff.inHours < 1)    return '${diff.inMinutes} menit lalu';
      if (diff.inDays < 1)     return '${diff.inHours} jam lalu';
      if (diff.inDays < 7)     return '${diff.inDays} hari lalu';
      return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {
      return '';
    }
  }

  String _getProviderLabel(String? provider) {
    switch (provider) {
      case 'openrouter': return 'OpenRouter';
      default:           return 'Groq';
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
      appBar         : _buildAppBar(),
      body           : Column(
        children: [
          _buildSearchBar(),
          Expanded(child: _buildBody()),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed        : () => Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const AIChatScreen()),
        ),
        icon             : const Icon(Icons.add_comment_rounded),
        label            : const Text('Chat Baru'),
        backgroundColor  : AppTheme.primaryCoral,
        foregroundColor  : Colors.white,
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation      : 1,
      leading        : IconButton(
        icon     : const Icon(Icons.arrow_back_rounded, color: AppTheme.textPrimary),
        onPressed: () => Navigator.pop(context),
      ),
      title: const Text(
        'Riwayat Chat',
        style: TextStyle(
          color     : AppTheme.textPrimary,
          fontWeight: FontWeight.bold,
          fontSize  : 18,
        ),
      ),
      actions: [
        IconButton(
          icon     : const Icon(Icons.tune_rounded, color: AppTheme.textPrimary),
          tooltip  : 'AI Settings',
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const AISettingsScreen()),
          ),
        ),
        if (_conversations.isNotEmpty)
          IconButton(
            icon     : const Icon(Icons.delete_sweep_rounded, color: Colors.red),
            tooltip  : 'Hapus Semua',
            onPressed: _deleteAll,
          ),
      ],
    );
  }

  Widget _buildSearchBar() {
    return Container(
      margin   : const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color       : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border      : Border.all(color: AppTheme.primaryCoral.withValues(alpha: 0.3)),
        boxShadow   : [
          BoxShadow(
            color     : Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset    : const Offset(0, 2),
          ),
        ],
      ),
      child: TextField(
        controller : _searchController,
        onChanged  : (v) {
          setState(() => _searchQuery = v);
          // Debounce search
          Future.delayed(const Duration(milliseconds: 400), () {
            if (_searchController.text == v) _loadConversations();
          });
        },
        decoration: InputDecoration(
          hintText      : 'Cari riwayat chat...',
          hintStyle     : TextStyle(color: Colors.grey.shade400),
          prefixIcon    : const Icon(Icons.search_rounded, color: AppTheme.primaryCoral),
          suffixIcon    : _searchQuery.isNotEmpty
              ? IconButton(
                  icon     : const Icon(Icons.clear_rounded),
                  onPressed: () {
                    _searchController.clear();
                    setState(() => _searchQuery = '');
                    _loadConversations();
                  },
                )
              : null,
          border        : InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
      onRefresh : _loadConversations,
      color     : AppTheme.primaryCoral,
      child     : ListView.builder(
        padding    : const EdgeInsets.fromLTRB(16, 0, 16, 100),
        itemCount  : _conversations.length,
        itemBuilder: (_, index) => _buildConversationCard(_conversations[index]),
      ),
    );
  }

  Widget _buildConversationCard(Map<String, dynamic> conv) {
    final id          = conv['id']?.toString() ?? '';
    final title       = conv['title'] ?? 'New Chat';
    final lastMessage = conv['last_message'] ?? 'Belum ada pesan';
    final provider    = conv['provider'] ?? 'groq';
    final updatedAt   = conv['updated_at'];
    final msgCount    = conv['message_count'] ?? 0;

    return Dismissible(
      key             : Key(id),
      direction       : DismissDirection.endToStart,
      confirmDismiss  : (_) => showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
          shape  : RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title  : const Text('Hapus Chat'),
          content: const Text('Hapus conversation ini?'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style    : ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
              child    : const Text('Hapus'),
            ),
          ],
        ),
      ),
      onDismissed: (_) => AIChatClient.deleteConversation(id),
      background: Container(
        margin       : const EdgeInsets.only(bottom: 12),
        decoration   : BoxDecoration(
          color        : Colors.red.shade500,
          borderRadius : BorderRadius.circular(16),
        ),
        alignment    : Alignment.centerRight,
        padding      : const EdgeInsets.only(right: 24),
        child        : const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children         : [
            Icon(Icons.delete_rounded, color: Colors.white, size: 28),
            SizedBox(height: 4),
            Text('Hapus', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
      child: GestureDetector(
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => AIChatScreen(
              conversationId: id,
              initialTitle  : title,
            ),
          ),
        ).then((_) => _loadConversations()),
        child: Container(
          margin    : const EdgeInsets.only(bottom: 12),
          padding   : const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color       : Colors.white,
            borderRadius: BorderRadius.circular(16),
            border      : Border.all(color: Colors.grey.shade200),
            boxShadow   : [
              BoxShadow(
                color     : Colors.black.withValues(alpha: 0.05),
                blurRadius: 8,
                offset    : const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              // Icon
              Container(
                width     : 48,
                height    : 48,
                decoration: BoxDecoration(
                  gradient    : AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(Icons.psychology_rounded, color: Colors.white, size: 26),
              ),
              const SizedBox(width: 14),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children          : [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            title,
                            style  : const TextStyle(
                              fontSize  : 15,
                              fontWeight: FontWeight.bold,
                              color     : AppTheme.textPrimary,
                            ),
                            maxLines : 1,
                            overflow : TextOverflow.ellipsis,
                          ),
                        ),
                        Text(
                          _formatTime(updatedAt),
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      lastMessage,
                      style  : TextStyle(fontSize: 13, color: Colors.grey.shade600),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        // Provider chip
                        Container(
                          padding   : const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color       : _getProviderColor(provider).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            _getProviderLabel(provider),
                            style: TextStyle(
                              fontSize  : 10,
                              fontWeight: FontWeight.bold,
                              color     : _getProviderColor(provider),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        // Message count
                        Icon(Icons.chat_bubble_outline_rounded,
                            size: 12, color: Colors.grey.shade400),
                        const SizedBox(width: 3),
                        Text(
                          '$msgCount pesan',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade400),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right_rounded, color: Colors.grey.shade400),
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
        children         : [
          Container(
            padding   : const EdgeInsets.all(28),
            decoration: BoxDecoration(
              gradient    : AppTheme.cardGradient,
              shape       : BoxShape.circle,
            ),
            child: Icon(Icons.chat_bubble_outline_rounded,
                size: 64, color: AppTheme.primaryCoral.withValues(alpha: 0.6)),
          ),
          const SizedBox(height: 24),
          Text(
            _searchQuery.isNotEmpty ? 'Tidak ditemukan' : 'Belum Ada Riwayat Chat',
            style: const TextStyle(
              fontSize  : 20,
              fontWeight: FontWeight.bold,
              color     : AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _searchQuery.isNotEmpty
                ? 'Coba kata kunci lain'
                : 'Mulai chat dengan Chef AI Savora!',
            style: TextStyle(fontSize: 14, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }
}