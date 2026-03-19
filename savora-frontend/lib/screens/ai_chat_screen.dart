import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import '../services/ai_chat_client.dart';
import '../widgets/theme.dart';
import 'ai_chat_history_screen.dart';
import 'ai_settings_screen.dart';

class AIChatScreen extends StatefulWidget {
  /// Jika null → buat conversation baru otomatis
  final String? conversationId;
  final String? initialTitle;

  const AIChatScreen({
    super.key,
    this.conversationId,
    this.initialTitle,
  });

  @override
  State<AIChatScreen> createState() => _AIChatScreenState();
}

class _AIChatScreenState extends State<AIChatScreen> {
  final _messageController = TextEditingController();
  final _scrollController  = ScrollController();
  final _imagePicker       = ImagePicker();

  String? _conversationId;
  String  _conversationTitle = 'Chef AI';
  List<Map<String, dynamic>> _messages = [];
  bool _isLoading        = false;
  bool _isInitializing   = true;
  File? _selectedImage;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  // ─────────────────────────────────────────────
  // INIT
  // ─────────────────────────────────────────────

  Future<void> _init() async {
    if (widget.conversationId != null) {
      // Buka conversation yang sudah ada
      await _loadConversation(widget.conversationId!);
    } else {
      // Buat conversation baru
      await _createNewConversation();
    }
    setState(() => _isInitializing = false);
    _addWelcomeMessageIfEmpty();
  }

  Future<void> _loadConversation(String id) async {
    final data = await AIChatClient.getConversation(id);
    if (data != null && mounted) {
      setState(() {
        _conversationId    = id;
        _conversationTitle = data['title'] ?? 'Chef AI';
        _messages          = List<Map<String, dynamic>>.from(
          data['messages'] ?? [],
        );
      });
    }
  }

  Future<void> _createNewConversation() async {
    final conv = await AIChatClient.createConversation(title: 'New Chat');
    if (conv != null && mounted) {
      setState(() {
        _conversationId    = conv['id'];
        _conversationTitle = conv['title'] ?? 'New Chat';
      });
    }
  }

  void _addWelcomeMessageIfEmpty() {
    if (_messages.isEmpty) {
      setState(() {
        _messages.add({
          'role'      : 'assistant',
          'content'   : 'Halo! Saya Chef AI Savora 👨‍🍳\n\n'
              'Saya siap membantu Anda dengan:\n'
              '• Pertanyaan tentang resep\n'
              '• Tips dan teknik memasak\n'
              '• Saran variasi resep\n'
              '• Analisis foto makanan 📸\n\n'
              'Ada yang bisa saya bantu?',
          'is_welcome': true,
          'is_error'  : false,
          'created_at': DateTime.now().toIso8601String(),
        });
      });
    }
  }

  // ─────────────────────────────────────────────
  // SEND MESSAGE
  // ─────────────────────────────────────────────

  Future<void> _sendMessage({String? imageUrl}) async {
    final content = _messageController.text.trim();
    if (content.isEmpty) return;

    // Jika conversation belum terbuat (misal gagal saat init), coba lagi
    if (_conversationId == null) {
      await _createNewConversation();
      if (_conversationId == null) {
        _showSnackBar('Gagal membuat sesi chat. Cek koneksi kamu.', isError: true);
        return;
      }
    }

    _messageController.clear();
    setState(() {
      _messages.add({
        'role'      : 'user',
        'content'   : content,
        'image_url' : imageUrl,
        'is_error'  : false,
        'created_at': DateTime.now().toIso8601String(),
      });
      _isLoading = true;
    });
    _scrollToBottom();

    try {
      final result = await AIChatClient.sendMessage(
        conversationId: _conversationId!,
        content       : content,
        imageUrl      : imageUrl,
      );

      if (mounted && result != null) {
        final assistantMsg = result['assistant_message'];
        setState(() {
          _messages.add(Map<String, dynamic>.from(assistantMsg ?? {}));
          _isLoading = false;

          // Update title jika masih "New Chat"
          if (_conversationTitle == 'New Chat' && _messages.length <= 3) {
            _conversationTitle = content.length > 40
                ? '${content.substring(0, 37)}...'
                : content;
          }
        });
        _scrollToBottom();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _messages.add({
            'role'    : 'assistant',
            'content' : _getErrorMessage(e.toString()),
            'is_error': true,
            'created_at': DateTime.now().toIso8601String(),
          });
          _isLoading = false;
        });
        _scrollToBottom();
      }
    }
  }

  // ─────────────────────────────────────────────
  // IMAGE
  // ─────────────────────────────────────────────

  Future<void> _pickImage(ImageSource source) async {
    try {
      final image = await _imagePicker.pickImage(
        source      : source,
        maxWidth    : 1280,
        imageQuality: 85,
      );
      if (image != null && mounted) {
        setState(() => _selectedImage = File(image.path));
        _showImagePreview();
      }
    } catch (e) {
      _showSnackBar('Gagal memilih gambar: $e', isError: true);
    }
  }

  void _showImageSourceDialog() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          color        : Colors.white,
          borderRadius : BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                margin    : const EdgeInsets.only(top: 12),
                width     : 40, height: 4,
                decoration: BoxDecoration(
                  color        : Colors.grey.shade300,
                  borderRadius : BorderRadius.circular(2),
                ),
              ),
              const Padding(
                padding: EdgeInsets.all(16),
                child  : Text('Pilih Sumber Gambar',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              ),
              ListTile(
                leading: Icon(Icons.camera_alt, color: Colors.blue.shade700),
                title  : const Text('Kamera'),
                onTap  : () { Navigator.pop(context); _pickImage(ImageSource.camera); },
              ),
              ListTile(
                leading: Icon(Icons.photo_library, color: Colors.purple.shade700),
                title  : const Text('Galeri'),
                onTap  : () { Navigator.pop(context); _pickImage(ImageSource.gallery); },
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }

  void _showImagePreview() {
    if (_selectedImage == null) return;
    final captionController = TextEditingController();

    showModalBottomSheet(
      context            : context,
      isScrollControlled : true,
      backgroundColor    : Colors.transparent,
      builder            : (_) => Padding(
        // Naik saat keyboard muncul
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child  : Container(
          height    : MediaQuery.of(context).size.height * 0.72,
          decoration: const BoxDecoration(
            color        : Colors.white,
            borderRadius : BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle bar
              Container(
                margin    : const EdgeInsets.only(top: 12),
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color       : Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),

              // Header
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child  : Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Kirim Gambar',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    IconButton(
                      icon     : const Icon(Icons.close),
                      onPressed: () {
                        setState(() => _selectedImage = null);
                        Navigator.pop(context);
                      },
                    ),
                  ],
                ),
              ),

              // Preview gambar
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child  : ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child       : Image.file(_selectedImage!, fit: BoxFit.contain),
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Caption input
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child  : Container(
                  decoration: BoxDecoration(
                    color       : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(16),
                    border      : Border.all(
                      color: AppTheme.primaryCoral.withValues(alpha: 0.3),
                    ),
                  ),
                  child: TextField(
                    controller    : captionController,
                    maxLines      : 3,
                    minLines      : 1,
                    autofocus     : false,
                    textInputAction: TextInputAction.done,
                    decoration    : InputDecoration(
                      hintText      : 'Tambah pertanyaan atau caption... (opsional)',
                      hintStyle     : TextStyle(color: Colors.grey.shade400, fontSize: 13),
                      border        : InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 12,
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Tombol kirim
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                child  : SizedBox(
                  width : double.infinity,
                  child : ElevatedButton.icon(
                    onPressed: () {
                      final caption = captionController.text.trim();
                      Navigator.pop(context);
                      _sendMessageWithImage(caption: caption);
                    },
                    icon : const Icon(Icons.send_rounded),
                    label: const Text('Kirim'),
                    style: ElevatedButton.styleFrom(
                      padding        : const EdgeInsets.symmetric(vertical: 14),
                      backgroundColor: AppTheme.primaryCoral,
                      foregroundColor: Colors.white,
                      shape          : RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _sendMessageWithImage({String caption = ''}) async {
    if (_selectedImage == null) return;

    // Retry buat conversation jika null
    if (_conversationId == null) {
      await _createNewConversation();
      if (_conversationId == null) {
        _showSnackBar('Gagal membuat sesi chat. Cek koneksi kamu.', isError: true);
        return;
      }
    }

    final imagePath = _selectedImage!.path;
    final content   = caption.isNotEmpty
        ? caption
        : '📸 Tolong analisis gambar makanan ini dan berikan informasi atau resep tentangnya.';

    setState(() {
      _messages.add({
        'role'      : 'user',
        'content'   : content,
        'image_file': imagePath,
        'is_error'  : false,
        'created_at': DateTime.now().toIso8601String(),
      });
      _isLoading     = true;
      _selectedImage = null;
    });
    _scrollToBottom();

    try {
      final result = await AIChatClient.analyzeRecipeFromImage(imagePath);

      if (mounted) {
        setState(() {
          _messages.add({
            'role'      : 'assistant',
            'content'   : result,
            'is_error'  : false,
            'created_at': DateTime.now().toIso8601String(),
          });
          _isLoading = false;
        });
        _scrollToBottom();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _messages.add({
            'role'    : 'assistant',
            'content' : _getErrorMessage(e.toString()),
            'is_error': true,
            'created_at': DateTime.now().toIso8601String(),
          });
          _isLoading = false;
        });
      }
    }
  }

  // ─────────────────────────────────────────────
  // RENAME DIALOG
  // ─────────────────────────────────────────────

  void _showRenameDialog() {
    final controller = TextEditingController(text: _conversationTitle);
    showDialog(
      context: context,
      builder: (dialogCtx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Ganti Judul'),
        content: TextField(
          controller : controller,
          autofocus  : true,
          decoration : const InputDecoration(hintText: 'Judul conversation'),
          onSubmitted: (_) => Navigator.pop(dialogCtx, controller.text.trim()),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogCtx),
            child    : const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(dialogCtx, controller.text.trim()),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryCoral,
              foregroundColor: Colors.white,
            ),
            child: const Text('Simpan'),
          ),
        ],
      ),
    ).then((newTitle) async {
      if (newTitle == null || newTitle.isEmpty) return;
      if (newTitle == _conversationTitle) return;
      // Update local state dulu agar terasa responsif
      if (mounted) setState(() => _conversationTitle = newTitle);
      // Simpan ke server
      if (_conversationId != null) {
        final ok = await AIChatClient.updateConversationTitle(_conversationId!, newTitle);
        if (!ok && mounted) {
          _showSnackBar('Gagal menyimpan judul', isError: true);
          // Rollback
          setState(() => _conversationTitle = widget.initialTitle ?? 'Chef AI');
        }
      }
    });
  }

  // ─────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 120), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve   : Curves.easeOut,
        );
      }
    });
  }

  String _getErrorMessage(String error) {
    if (error.contains('API key') || error.contains('401')) {
      return '🔑 API key tidak valid. Cek pengaturan AI kamu.';
    } else if (error.contains('429')) {
      return '⚠️ Terlalu banyak permintaan. Tunggu sebentar ya.';
    } else if (error.contains('timeout')) {
      return '⏱️ Koneksi timeout. Coba lagi.';
    } else if (error.contains('503')) {
      return '🔄 Server sedang sibuk. Coba lagi sebentar.';
    }
    return '❌ Maaf, terjadi kesalahan.\n\n$error';
  }

  void _showSnackBar(String message, {required bool isError}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
      behavior: SnackBarBehavior.floating,
      shape   : RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin  : const EdgeInsets.all(16),
    ));
  }

  // ─────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: _buildAppBar(),
      body  : _isInitializing
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryCoral))
          : Column(
              children: [
                Expanded(child: _buildMessageList()),
                if (_isLoading) _buildTypingIndicator(),
                _buildInputArea(),
              ],
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
      title: GestureDetector(
        onTap: _showRenameDialog,
        child: Row(
          children: [
            Container(
              padding   : const EdgeInsets.all(8),
              decoration: BoxDecoration(
                gradient     : AppTheme.accentGradient,
                shape        : BoxShape.circle,
              ),
              child: const Icon(Icons.psychology_rounded, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children          : [
                  Text(
                    _conversationTitle,
                    style: const TextStyle(
                      fontSize  : 16,
                      fontWeight: FontWeight.bold,
                      color     : AppTheme.textPrimary,
                    ),
                    maxLines : 1,
                    overflow : TextOverflow.ellipsis,
                  ),
                  Text(
                    'Tap untuk ganti judul',
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      actions: [
        // History button
        IconButton(
          icon     : const Icon(Icons.history_rounded, color: AppTheme.textPrimary),
          tooltip  : 'Riwayat Chat',
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const AIChatHistoryScreen()),
          ),
        ),
        // Settings button
        IconButton(
          icon     : const Icon(Icons.tune_rounded, color: AppTheme.textPrimary),
          tooltip  : 'AI Settings',
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const AISettingsScreen()),
          ),
        ),
        // New chat button
        IconButton(
          icon     : const Icon(Icons.add_comment_rounded, color: AppTheme.primaryCoral),
          tooltip  : 'Chat Baru',
          onPressed: () => Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const AIChatScreen()),
          ),
        ),
      ],
    );
  }

  Widget _buildMessageList() {
    if (_messages.isEmpty) return const SizedBox.shrink();
    return ListView.builder(
      controller : _scrollController,
      padding    : const EdgeInsets.all(16),
      itemCount  : _messages.length,
      itemBuilder: (_, index) => _buildMessageBubble(_messages[index]),
    );
  }

  Widget _buildMessageBubble(Map<String, dynamic> message) {
    final isUser   = message['role'] == 'user';
    final isError  = message['is_error'] == true;
    final content  = message['content'] ?? '';
    final imgPath  = message['image_file'] as String?;

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child    : Container(
        margin     : const EdgeInsets.only(bottom: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.78,
        ),
        padding   : const EdgeInsets.all(14),
        decoration: BoxDecoration(
          gradient    : isUser
              ? AppTheme.accentGradient
              : isError
                  ? LinearGradient(colors: [Colors.red.shade50, Colors.red.shade100])
                  : LinearGradient(colors: [Colors.grey.shade100, Colors.grey.shade50]),
          borderRadius: BorderRadius.only(
            topLeft    : const Radius.circular(16),
            topRight   : const Radius.circular(16),
            bottomLeft : Radius.circular(isUser ? 16 : 4),
            bottomRight: Radius.circular(isUser ? 4  : 16),
          ),
          border: isError
              ? Border.all(color: Colors.red.shade300)
              : null,
          boxShadow: [
            BoxShadow(
              color     : Colors.black.withValues(alpha: 0.06),
              blurRadius: 6,
              offset    : const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children          : [
            // Tampilkan preview gambar jika ada
            if (imgPath != null) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child       : Image.file(
                  File(imgPath),
                  height      : 140,
                  width       : double.infinity,
                  fit         : BoxFit.cover,
                  errorBuilder: (_, _, _) => Container(
                    height    : 40,
                    decoration: BoxDecoration(
                      color       : Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children         : [
                        Icon(Icons.image_rounded, color: Colors.white.withValues(alpha: 0.7), size: 16),
                        const SizedBox(width: 6),
                        Text('Gambar dikirim', style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12)),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
            ],
            Text(
              content,
              style: TextStyle(
                color : isUser
                    ? Colors.white
                    : isError
                        ? Colors.red.shade900
                        : AppTheme.textPrimary,
                fontSize: 15,
                height  : 1.45,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child  : Row(
        children: [
          Container(
            padding   : const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color       : Colors.grey.shade200,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children    : [
                SizedBox(
                  width: 16, height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color      : AppTheme.primaryCoral,
                  ),
                ),
                const SizedBox(width: 8),
                Text('Chef AI sedang mengetik...',
                    style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInputArea() {
    return Container(
      padding   : const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color    : Colors.white,
        boxShadow: [
          BoxShadow(
            color     : Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset    : const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            // Image picker
            Container(
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                shape: BoxShape.circle,
              ),
              child: IconButton(
                icon     : Icon(Icons.image_rounded,
                    color: _isLoading ? Colors.grey : AppTheme.primaryCoral),
                onPressed: _isLoading ? null : _showImageSourceDialog,
              ),
            ),
            const SizedBox(width: 8),

            // Text input
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color       : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(24),
                ),
                child: TextField(
                  controller     : _messageController,
                  maxLines       : null,
                  enabled        : !_isLoading,
                  textInputAction: TextInputAction.send,
                  onSubmitted    : (_) => _sendMessage(),
                  decoration     : InputDecoration(
                    hintText       : 'Tanya tentang masak...',
                    hintStyle      : TextStyle(color: Colors.grey.shade500),
                    border         : InputBorder.none,
                    contentPadding : const EdgeInsets.symmetric(
                      horizontal: 18, vertical: 12,
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),

            // Send button
            Container(
              decoration: BoxDecoration(
                gradient: _isLoading
                    ? LinearGradient(colors: [Colors.grey, Colors.grey.shade400])
                    : AppTheme.accentGradient,
                shape: BoxShape.circle,
              ),
              child: IconButton(
                icon     : const Icon(Icons.send_rounded, color: Colors.white),
                onPressed: _isLoading ? null : _sendMessage,
              ),
            ),
          ],
        ),
      ),
    );
  }
}