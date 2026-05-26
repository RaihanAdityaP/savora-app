import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import 'package:share_plus/share_plus.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'dart:convert';
import 'dart:io';
import '../../services/api_service.dart';
import '../../services/app_settings_service.dart';
import '../../services/recipe_client.dart';
import '../../widgets/custom_bottom_nav.dart';
import '../../widgets/theme.dart';
import '../home_screen.dart';
import 'edit_screen.dart';
import '../profile_screen.dart';
import '../searching_screen.dart';

class DetailScreen extends StatefulWidget {
  final String recipeId;
  const DetailScreen({super.key, required this.recipeId});
  @override
  State<DetailScreen> createState() => _DetailScreenState();
}

class _DetailScreenState extends State<DetailScreen>
    with TickerProviderStateMixin {
  Map<String, dynamic>? _recipe;
  bool _isLoading = true;
  bool _isFavorite = false;
  bool _isLiked = false;
  bool _isTogglingLike = false;
  bool _isPostingComment = false;
  bool _isTranslatingRecipe = false;
  bool _recipeTranslated = false;
  int _likesCount = 0;
  int? _userRating;
  int? _selectedReviewRating;
  double? _averageRating;
  int? _ratingCount;
  List<Map<String, dynamic>> _comments = [];
  final Set<String> _translatingCommentIds = {};
  final Set<String> _translatedCommentIds = {};
  final Map<String, String> _originalComments = {};
  final Map<String, dynamic> _originalRecipeText = {};
  List<String> _tags = [];
  final TextEditingController _commentController = TextEditingController();
  String? _userAvatarUrl;
  String? _currentUserId;
  String? _currentUserRole;
  VideoPlayerController? _videoPlayerController;
  ChewieController? _chewieController;
  bool _isVideoInitializing = false;
  late AnimationController _shareButtonAnimationController;

  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  List<Map<String, dynamic>> _extractMapList(dynamic payload) {
    if (payload is List) {
      return payload
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }

    if (payload is Map) {
      final normalizedMap = Map<String, dynamic>.from(payload);
      for (final key in const [
        'data',
        'items',
        'results',
        'ratings',
        'comments',
        'tags',
      ]) {
        final nested = normalizedMap[key];
        if (nested is List) {
          return nested
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList();
        }
      }
    }

    return [];
  }

  List<dynamic> _extractDynamicList(dynamic payload) {
    if (payload is List) return payload;

    if (payload is Map) {
      final normalizedMap = Map<String, dynamic>.from(payload);
      for (final key in const [
        'data',
        'items',
        'results',
        'ingredients',
        'steps',
      ]) {
        final nested = normalizedMap[key];
        if (nested is List) return nested;
      }
    }

    if (payload is String) {
      final text = payload.trim();
      if (text.isEmpty) return [];

      if ((text.startsWith('[') && text.endsWith(']')) ||
          (text.startsWith('{') && text.endsWith('}'))) {
        try {
          final decoded = jsonDecode(text);
          return _extractDynamicList(decoded);
        } catch (_) {
          // fallback to plain text parsing below
        }
      }

      final cleanedLines = text
          .split(RegExp(r'\r?\n'))
          .map(
            (line) => line
                .replaceFirst(RegExp(r'^\s*(?:[-•]|\d+[.)])\s*'), '')
                .trim(),
          )
          .where((line) => line.isNotEmpty)
          .toList();

      if (cleanedLines.length > 1) return cleanedLines;

      final commaSeparated = text
          .split(',')
          .map((item) => item.trim())
          .where((item) => item.isNotEmpty)
          .toList();

      if (commaSeparated.length > 1) return commaSeparated;

      return [text];
    }

    return [];
  }

  @override
  void initState() {
    super.initState();
    _currentUserId = ApiService.currentUserId;
    _shareButtonAnimationController = AnimationController(
      duration: const Duration(milliseconds: 150),
      vsync: this,
    );
    _initializeScreen();
  }

  @override
  void dispose() {
    _commentController.dispose();
    _chewieController?.dispose();
    _videoPlayerController?.dispose();
    _shareButtonAnimationController.dispose();
    super.dispose();
  }

  Future<void> _initializeScreen() async {
    await Future.wait([
      _loadRecipe(),
      _incrementViews(),
      _checkIfFavorite(),
      _loadUserRating(),
      _loadComments(),
      _loadCurrentUserProfile(),
      _loadRecipeTags(),
    ]);
  }

  Future<void> _initializeVideoPlayer(String videoUrl) async {
    if (_isVideoInitializing) return;
    setState(() => _isVideoInitializing = true);
    try {
      _videoPlayerController = VideoPlayerController.networkUrl(
        Uri.parse(videoUrl),
      );
      await _videoPlayerController!.initialize();
      _chewieController = ChewieController(
        videoPlayerController: _videoPlayerController!,
        autoPlay: false,
        looping: false,
        aspectRatio: _videoPlayerController!.value.aspectRatio,
        errorBuilder: (context, errorMessage) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 48, color: Colors.red.shade400),
                const SizedBox(height: 16),
                Text(
                  _t('Failed to load video', 'Gagal memuat video'),
                  style: TextStyle(color: Colors.red.shade600),
                ),
              ],
            ),
          );
        },
      );
      if (mounted) setState(() => _isVideoInitializing = false);
    } catch (e) {
      debugPrint('Error initializing video: $e');
      if (mounted) setState(() => _isVideoInitializing = false);
    }
  }

  Future<void> _loadCurrentUserProfile() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId != null) {
        final response = await ApiService.get('/users/$userId/profile');
        if (!mounted) return;
        final data = response['data'] ?? response;
        setState(() {
          _userAvatarUrl = data['avatar_url'];
          _currentUserRole = data['role']?.toString() ?? 'user';
        });
      }
    } catch (e) {
      debugPrint('Error loading user profile: $e');
    }
  }

  Future<void> _loadRecipe() async {
    try {
      final response = await ApiService.get('/recipes/${widget.recipeId}');
      if (!mounted) return;
      final data = response['data'] ?? response;
      if (data == null) {
        setState(() {
          _isLoading = false;
          _recipe = null;
        });
        return;
      }
      final ratingResponse = await ApiService.get(
        '/ratings/recipe/${widget.recipeId}',
      );
      final ratings = _extractMapList(ratingResponse['data'] ?? ratingResponse);
      setState(() {
        _recipe = Map<String, dynamic>.from(data);
        _likesCount = _toInt(_recipe?['likes_count']);
        _isLiked = _recipe?['is_liked'] == true;
        _ratingCount = ratings.length;
        if (_ratingCount! > 0) {
          final total = ratings.fold<int>(
            0,
            (sum, r) => sum + _toInt(r['rating']),
          );
          _averageRating = total / _ratingCount!;
        } else {
          _averageRating = 0;
        }
        _isLoading = false;
      });
      if (_recipe!['video_url'] != null) {
        _initializeVideoPlayer(_recipe!['video_url']);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _recipe = null;
      });
      final error = e.toString().toLowerCase();
      if (error.contains('recipe not found') ||
          error.contains('resep tidak ditemukan') ||
          error.contains('404')) {
        _showSnackBar(
          _t(
            'Recipe not found or deleted.',
            'Resep tidak ditemukan atau sudah dihapus.',
          ),
          isError: true,
        );
      } else {
        _showSnackBar(
          'Error loading recipe: ${e.toString().replaceFirst('Exception: ', '')}',
          isError: true,
        );
      }
    }
  }

  Future<void> _incrementViews() async {
    try {
      await ApiService.post('/recipes/${widget.recipeId}/view', {
        if (ApiService.currentUserId != null)
          'user_id': ApiService.currentUserId,
      });
    } catch (e) {
      debugPrint('Error incrementing views: $e');
    }
  }

  int _toInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  int? _ratingFromComment(Map<String, dynamic> comment) {
    final rawRating =
        comment['rating'] ?? comment['user_rating'] ?? comment['review_rating'];
    final rating = _toInt(rawRating);
    return rating >= 1 && rating <= 5 ? rating : null;
  }

  Future<void> _toggleLike() async {
    if (_isTogglingLike) return;
    if (ApiService.currentUserId == null) {
      _showSnackBar(
        _t(
          'Please log in to like recipes',
          'Silakan login untuk menyukai resep',
        ),
        isError: true,
      );
      return;
    }

    setState(() => _isTogglingLike = true);
    final result = await RecipeClient.toggleLike(widget.recipeId);
    if (!mounted) return;

    if (result != null) {
      setState(() {
        _likesCount = _toInt(result['likes_count']);
        _isLiked = result['is_liked'] == true;
        _recipe?['likes_count'] = _likesCount;
        _recipe?['is_liked'] = _isLiked;
      });
    } else {
      _showSnackBar(
        _t('Failed to update like', 'Gagal memperbarui like'),
        isError: true,
      );
    }

    if (mounted) setState(() => _isTogglingLike = false);
  }

  bool get _shouldShowTranslate => AppSettingsService.current.language == 'en';

  Future<String> _translateTextToEnglish(String text) async {
    final original = text.trim();
    if (original.isEmpty) return text;

    final uri = Uri.https('api.mymemory.translated.net', '/get', {
      'q': original,
      'langpair': 'id|en',
    });
    final response = await http.get(uri);
    if (response.statusCode < 200 || response.statusCode >= 300) return text;

    final decoded = jsonDecode(response.body);
    final responseData = decoded is Map ? decoded['responseData'] : null;
    final translated = responseData is Map
        ? responseData['translatedText']?.toString()
        : null;
    return translated != null && translated.trim().isNotEmpty
        ? translated
        : text;
  }

  Future<void> _toggleRecipeTranslate() async {
    if (!_shouldShowTranslate || _recipe == null || _isTranslatingRecipe) {
      return;
    }

    if (_recipeTranslated) {
      setState(() {
        _recipe!.addAll(_originalRecipeText);
        _recipeTranslated = false;
      });
      return;
    }

    setState(() => _isTranslatingRecipe = true);
    try {
      _originalRecipeText
        ..clear()
        ..addAll({
          'title': _recipe!['title'],
          'description': _recipe!['description'],
          'ingredients': _recipe!['ingredients'],
          'steps': _recipe!['steps'],
        });

      final translatedTitle = await _translateTextToEnglish(
        (_recipe!['title'] ?? '').toString(),
      );
      final translatedDescription = await _translateTextToEnglish(
        (_recipe!['description'] ?? '').toString(),
      );
      final translatedIngredients = await _translateDynamicTextList(
        _recipe!['ingredients'],
      );
      final translatedSteps = await _translateDynamicTextList(
        _recipe!['steps'],
      );

      if (!mounted) return;
      setState(() {
        _recipe!['title'] = translatedTitle;
        _recipe!['description'] = translatedDescription;
        _recipe!['ingredients'] = translatedIngredients;
        _recipe!['steps'] = translatedSteps;
        _recipeTranslated = true;
      });
    } finally {
      if (mounted) setState(() => _isTranslatingRecipe = false);
    }
  }

  Future<dynamic> _translateDynamicTextList(dynamic value) async {
    final items = _extractDynamicList(value);
    final translated = <dynamic>[];

    for (final item in items) {
      if (item is String) {
        translated.add(await _translateTextToEnglish(item));
      } else if (item is Map) {
        final copy = Map<String, dynamic>.from(item);
        for (final key in ['name', 'description', 'text', 'step']) {
          if (copy[key] != null) {
            copy[key] = await _translateTextToEnglish(copy[key].toString());
          }
        }
        translated.add(copy);
      } else {
        translated.add(item);
      }
    }

    return translated;
  }

  Future<void> _toggleCommentTranslate(String commentId, int index) async {
    if (!_shouldShowTranslate || _translatingCommentIds.contains(commentId)) {
      return;
    }

    if (_translatedCommentIds.contains(commentId)) {
      setState(() {
        _comments[index]['content'] =
            _originalComments[commentId] ?? _comments[index]['content'];
        _translatedCommentIds.remove(commentId);
      });
      return;
    }

    setState(() => _translatingCommentIds.add(commentId));
    try {
      final original = (_comments[index]['content'] ?? '').toString();
      _originalComments[commentId] = original;
      final translated = await _translateTextToEnglish(original);
      if (!mounted) return;
      setState(() {
        _comments[index]['content'] = translated;
        _translatedCommentIds.add(commentId);
      });
    } finally {
      if (mounted) setState(() => _translatingCommentIds.remove(commentId));
    }
  }

  Future<void> _checkIfFavorite() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId != null) {
        final response = await ApiService.get(
          '/favorites/check?recipe_id=${widget.recipeId}',
        );
        if (!mounted) return;
        setState(() => _isFavorite = response['data']?['is_saved'] == true);
      }
    } catch (e) {
      debugPrint('Error checking favorite: $e');
    }
  }

  Future<void> _loadUserRating() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId != null) {
        final response = await ApiService.get(
          '/ratings/recipe/${widget.recipeId}/user',
        );
        if (!mounted) return;
        final rating = _toInt(response['data']?['rating']);
        setState(() {
          _userRating = rating >= 1 && rating <= 5 ? rating : null;
          _selectedReviewRating ??= _userRating;
        });
      }
    } catch (e) {
      debugPrint('Error loading user rating: $e');
    }
  }

  Future<void> _loadRecipeTags() async {
    try {
      final response = await ApiService.get('/recipes/${widget.recipeId}/tags');
      if (!mounted) return;
      final tags = _extractMapList(response['data'] ?? response);
      setState(() => _tags = tags.map((t) => t['name'] as String).toList());
    } catch (e) {
      debugPrint('Error loading tags: $e');
    }
  }

  // ============ SHARE FEATURE ============
  static const String _webBase = 'https://savora-app.up.railway.app';

  String _getRecipeWebUrl() => '$_webBase/r/${widget.recipeId}';

  String _generateShareText() {
    final title = _recipe?['title'] ?? 'Resep Tanpa Judul';
    final profile = _recipe?['profiles'];
    final username = profile?['username'] ?? 'Anonymous';
    final time = _recipe?['cooking_time'] ?? '?';
    final servings = _recipe?['servings'] ?? '?';
    final difficulty = (_recipe?['difficulty'] ?? 'mudah').toUpperCase();
    final rating = _averageRating != null
        ? '${_averageRating!.toStringAsFixed(1)}/5'
        : '?/5';
    return '''
  🍳 RESEP DARI SAVORA 🍳
  📋 Judul: $title
  👨‍🍳 Chef: $username
  ⏱️ Waktu: $time menit
  🍽️ Porsi: $servings porsi
  📊 Tingkat: $difficulty
  ⭐ Rating: $rating
  📝 Deskripsi:
  ${_recipe?['description'] ?? 'Tidak ada deskripsi.'}
  🔗 Link resep: ${_getRecipeWebUrl()}
  '''
        .trim();
  }

  Future<void> _shareLink() async {
    await SharePlus.instance.share(
      ShareParams(
        text: 'Lihat resep ini di Savora: ${_getRecipeWebUrl()}',
        subject: 'Resep dari Savora: ${_recipe?['title']}',
      ),
    );
  }

  Future<void> _shareDetail() async {
    await SharePlus.instance.share(
      ShareParams(
        text: _generateShareText(),
        subject: 'Resep dari Savora: ${_recipe?['title']}',
      ),
    );
  }

  Future<void> _shareImage() async {
    final imageUrl = _recipe?['image_url'];
    if (imageUrl == null) return;
    try {
      final response = await http.get(Uri.parse(imageUrl));
      if (response.statusCode == 200) {
        final tempDir = await getTemporaryDirectory();
        final file = File('${tempDir.path}/${widget.recipeId}.jpg');
        await file.writeAsBytes(response.bodyBytes);
        await SharePlus.instance.share(
          ShareParams(
            files: [XFile(file.path)],
            text:
                '${_recipe?['title'] ?? 'Resep Savora'} 🍳\n🔗 ${_getRecipeWebUrl()}',
            subject: 'Resep dari Savora: ${_recipe?['title']}',
          ),
        );
      } else {
        _showSnackBar(
          _t('Failed to download image', 'Gagal mengunduh gambar'),
          isError: true,
        );
      }
    } catch (e) {
      _showSnackBar('Error saat berbagi gambar: $e', isError: true);
    }
  }

  Future<void> _shareToWhatsApp() async {
    await SharePlus.instance.share(
      ShareParams(
        text: _generateShareText(),
        subject: 'Resep dari Savora: ${_recipe?['title']}',
      ),
    );
  }

  Future<void> _shareWithChooser() async {
    final text =
        '${_recipe?['title'] ?? 'Resep Savora'} 🍳\n'
        '${_recipe?['description'] ?? ''}\n'
        '🔗 ${_getRecipeWebUrl()}';
    await SharePlus.instance.share(
      ShareParams(
        text: text,
        subject: 'Resep dari Savora: ${_recipe?['title']}',
      ),
    );
  }

  Future<void> _copyLinkToClipboard() async {
    await Clipboard.setData(ClipboardData(text: _getRecipeWebUrl()));
    _showSnackBar('Link berhasil disalin! 🔗', isError: false);
  }

  Widget _buildShareOption({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () {
          Navigator.pop(context);
          onTap();
        },
        borderRadius: BorderRadius.circular(16),
        child: Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: 0.2), width: 1.5),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                child: Icon(icon, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: TextStyle(
                        fontSize: 12,
                        color: AppTheme.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_ios_rounded,
                size: 16,
                color: AppTheme.textMuted,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showShareBottomSheet() async {
    _shareButtonAnimationController.forward().then(
      (_) => _shareButtonAnimationController.reverse(),
    );
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        decoration: BoxDecoration(
          color: AppTheme.surfaceColor,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: DraggableScrollableSheet(
          maxChildSize: 0.7,
          minChildSize: 0.4,
          initialChildSize: 0.55,
          expand: false,
          builder: (_, controller) => Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(12),
                child: Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppTheme.textMuted,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 8,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      _t('Share Recipe', 'Bagikan Resep'),
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.textPrimary,
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: Icon(Icons.close, color: AppTheme.textSecondary),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              const SizedBox(height: 16),
              Expanded(
                child: ListView(
                  controller: controller,
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  children: [
                    _buildShareOption(
                      icon: Icons.link_rounded,
                      color: Colors.blue,
                      title: _t('Share Link', 'Bagikan Link'),
                      subtitle: _t('Share recipe link', 'Bagikan link resep'),
                      onTap: _shareLink,
                    ),
                    _buildShareOption(
                      icon: Icons.content_copy_rounded,
                      color: Colors.blueGrey,
                      title: 'Copy Link',
                      subtitle: 'Salin link ke clipboard',
                      onTap: _copyLinkToClipboard,
                    ),
                    _buildShareOption(
                      icon: Icons.description_rounded,
                      color: AppTheme.primaryCoral,
                      title: _t('Share Full Detail', 'Share Detail Lengkap'),
                      subtitle: _t(
                        'Share with full description',
                        'Bagikan dengan deskripsi lengkap',
                      ),
                      onTap: _shareDetail,
                    ),
                    if (_recipe?['image_url'] != null)
                      _buildShareOption(
                        icon: Icons.image_rounded,
                        color: Colors.green,
                        title: _t('Share with Image', 'Share dengan Gambar'),
                        subtitle: _t(
                          'Share image + caption',
                          'Bagikan gambar + caption',
                        ),
                        onTap: _shareImage,
                      ),
                    _buildShareOption(
                      icon: Icons.chat,
                      color: const Color(0xFF25D366),
                      title: 'Share ke WhatsApp',
                      subtitle: 'Kirim langsung ke WhatsApp',
                      onTap: _shareToWhatsApp,
                    ),
                    _buildShareOption(
                      icon: Icons.share_rounded,
                      color: Colors.purple,
                      title: _t('More Sharing Options', 'Share Lainnya'),
                      subtitle: _t(
                        'Choose an app to share',
                        'Pilih aplikasi untuk berbagi',
                      ),
                      onTap: _shareWithChooser,
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  // ============ END SHARE FEATURE ============

  Future<void> _showBoardSelector() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) {
        _showSnackBar(
          _t('Please log in first', 'Silakan login terlebih dahulu'),
          isError: true,
        );
        return;
      }
      final response = await ApiService.get('/favorites/boards');
      final boards = _extractMapList(response['data'] ?? response);
      if (!mounted) return;
      showModalBottomSheet(
        context: context,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        builder: (context) => _buildBoardSelectorSheet(boards, userId),
      );
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Widget _buildBoardSelectorSheet(
    List<Map<String, dynamic>> boards,
    String userId,
  ) {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.collections_bookmark_rounded,
                  color: Colors.white,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  _t('Save to Collection', 'Simpan ke Koleksi'),
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            decoration: BoxDecoration(
              gradient: AppTheme.primaryGradient,
              borderRadius: BorderRadius.circular(16),
              boxShadow: AppTheme.buttonShadow,
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () {
                  Navigator.pop(context);
                  _showCreateBoardDialog(userId);
                },
                borderRadius: BorderRadius.circular(16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.3),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(
                          Icons.add_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _t('Create New Collection', 'Buat Koleksi Baru'),
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      const Icon(
                        Icons.arrow_forward_ios_rounded,
                        color: Colors.white,
                        size: 16,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (boards.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: AppTheme.subtleSurfaceColor,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Center(
                child: Column(
                  children: [
                    Icon(
                      Icons.collections_bookmark_outlined,
                      size: 48,
                      color: AppTheme.textMuted,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _t('No collections yet', 'Belum ada koleksi'),
                      style: TextStyle(
                        fontSize: 16,
                        color: AppTheme.textSecondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: boards.length,
                itemBuilder: (context, index) {
                  final board = boards[index];
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceColor,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: AppTheme.primaryCoral.withValues(alpha: 0.2),
                        width: 1.5,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () {
                          Navigator.pop(context);
                          _addToBoard(board['id'].toString(), board['name']);
                        },
                        borderRadius: BorderRadius.circular(16),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      AppTheme.primaryCoral.withValues(
                                        alpha: 0.2,
                                      ),
                                      AppTheme.primaryOrange.withValues(
                                        alpha: 0.1,
                                      ),
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(
                                  Icons.collections_bookmark_rounded,
                                  color: AppTheme.primaryCoral,
                                  size: 20,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      board['name'] ?? '',
                                      style: TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.bold,
                                        color: AppTheme.textPrimary,
                                      ),
                                    ),
                                    if (board['description'] != null &&
                                        board['description']
                                            .toString()
                                            .isNotEmpty)
                                      Text(
                                        board['description'],
                                        style: TextStyle(
                                          fontSize: 12,
                                          color: AppTheme.textSecondary,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                  ],
                                ),
                              ),
                              Icon(
                                Icons.arrow_forward_ios_rounded,
                                size: 14,
                                color: AppTheme.primaryCoral,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  void _showCreateBoardDialog(String userId) {
    final nameController = TextEditingController();
    final descController = TextEditingController();
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.add_rounded,
                color: Colors.white,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Text(
              _t('New Collection', 'Koleksi Baru'),
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              decoration: BoxDecoration(
                color: AppTheme.subtleSurfaceColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.borderColor, width: 1.5),
              ),
              child: TextField(
                controller: nameController,
                style: AppTheme.fieldText,
                decoration: InputDecoration(
                  hintText: _t('Collection Name', 'Nama Koleksi'),
                  hintStyle: TextStyle(color: AppTheme.textMuted),
                  prefixIcon: const Icon(
                    Icons.collections_bookmark_rounded,
                    color: AppTheme.primaryCoral,
                  ),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 16,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              decoration: BoxDecoration(
                color: AppTheme.subtleSurfaceColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.borderColor, width: 1.5),
              ),
              child: TextField(
                controller: descController,
                maxLines: 3,
                style: AppTheme.fieldText,
                decoration: InputDecoration(
                  hintText: 'Deskripsi (opsional)',
                  hintStyle: TextStyle(color: AppTheme.textMuted),
                  prefixIcon: const Padding(
                    padding: EdgeInsets.only(bottom: 60),
                    child: Icon(
                      Icons.description_rounded,
                      color: AppTheme.primaryOrange,
                    ),
                  ),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 16,
                  ),
                ),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            style: TextButton.styleFrom(
              foregroundColor: AppTheme.textSecondary,
            ),
            child: Text(
              _t('Cancel', 'Batal'),
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Container(
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: () async {
                if (nameController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                    SnackBar(
                      content: Text(
                        _t(
                          'Collection name is required',
                          'Nama koleksi harus diisi',
                        ),
                      ),
                    ),
                  );
                  return;
                }
                try {
                  await ApiService.post('/favorites/boards', {
                    'name': nameController.text.trim(),
                    'description': descController.text.trim(),
                  });
                  if (!dialogContext.mounted) return;
                  Navigator.pop(dialogContext);
                  if (!mounted) return;
                  _showSnackBar(
                    _t('Collection created!', 'Koleksi berhasil dibuat!'),
                    isError: false,
                  );
                  _showBoardSelector();
                } catch (e) {
                  _showSnackBar('Error: $e', isError: true);
                }
              },
              child: Text(
                _t('Create', 'Buat'),
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _addToBoard(String boardId, String boardName) async {
    try {
      await ApiService.post('/favorites/boards/$boardId/recipes', {
        'recipe_id': widget.recipeId,
      });
      if (mounted) {
        setState(() => _isFavorite = true);
        _showSnackBar(
          '${_t('Added to', 'Ditambahkan ke')} "$boardName"',
          isError: false,
        );
      }
    } catch (e) {
      final msg = e.toString();
      if (msg.contains('sudah ada') || msg.contains('already')) {
        _showSnackBar(
          _t(
            'Recipe is already in this collection',
            'Resep sudah ada di koleksi ini',
          ),
          isError: true,
        );
      } else {
        _showSnackBar('Error: $msg', isError: true);
      }
    }
  }

  Future<void> _loadComments() async {
    try {
      final response = await ApiService.get(
        '/comments/recipe/${widget.recipeId}',
      );
      if (!mounted) return;
      setState(() => _comments = _extractMapList(response['data'] ?? response));
    } catch (e) {
      debugPrint('Error loading comments: $e');
    }
  }

  Future<void> _postComment() async {
    if (_isPostingComment) return;
    if (_selectedReviewRating == null) {
      _showSnackBar(
        _t('Please choose a rating first', 'Pilih rating terlebih dahulu'),
        isError: true,
      );
      return;
    }
    if (_commentController.text.trim().isEmpty) {
      _showSnackBar(
        _t('Comment cannot be empty', 'Komentar tidak boleh kosong'),
        isError: true,
      );
      return;
    }
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) {
        _showSnackBar(
          _t('Please log in to comment', 'Silakan login untuk berkomentar'),
          isError: true,
        );
        return;
      }
      setState(() => _isPostingComment = true);
      await ApiService.post('/comments', {
        'recipe_id': widget.recipeId,
        'user_id': userId,
        'rating': _selectedReviewRating,
        'content': _commentController.text.trim(),
      });
      if (!mounted) return;
      _commentController.clear();
      setState(() => _userRating = _selectedReviewRating);
      await _loadRecipe();
      await _loadComments();
      _showSnackBar(
        _t('Review sent!', 'Ulasan berhasil dikirim!'),
        isError: false,
      );
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    } finally {
      if (mounted) setState(() => _isPostingComment = false);
    }
  }

  Future<void> _editComment(String commentId, String newContent) async {
    if (newContent.trim().isEmpty) return;
    try {
      await ApiService.put('/comments/$commentId', {
        'content': newContent.trim(),
      });
      await _loadComments();
      _showSnackBar(
        _t('Comment updated!', 'Komentar berhasil diperbarui!'),
        isError: false,
      );
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Future<void> _deleteComment(String commentId, String commentUserId) async {
    final isOwner = _currentUserId == commentUserId;
    final isAdmin = _currentUserRole == 'admin';

    if (!isOwner && !isAdmin) {
      _showSnackBar(
        _t(
          'You do not have permission to delete this comment',
          'Anda tidak memiliki izin untuk menghapus komentar ini',
        ),
        isError: true,
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.red.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.warning_rounded,
                color: Colors.red,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Text(_t('Delete Comment', 'Hapus Komentar')),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _t(
                'Are you sure you want to delete this comment?',
                'Apakah Anda yakin ingin menghapus komentar ini?',
              ),
            ),
            if (isAdmin && !isOwner) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.admin_panel_settings,
                      size: 18,
                      color: Colors.orange.shade700,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _t(
                          'You are deleting this comment as Admin',
                          'Anda menghapus komentar sebagai Admin',
                        ),
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.orange.shade700,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(_t('Cancel', 'Batal')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(
              _t('Delete', 'Hapus'),
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await ApiService.delete('/comments/$commentId');
        await _loadComments();
        _showSnackBar(
          isAdmin && !isOwner
              ? _t(
                  'Comment deleted by Admin!',
                  'Komentar berhasil dihapus oleh Admin!',
                )
              : _t('Comment deleted!', 'Komentar berhasil dihapus!'),
          isError: false,
        );
      } catch (e) {
        _showSnackBar('Error: $e', isError: true);
      }
    }
  }

  Future<void> _deleteRecipe() async {
    final isOwner = _currentUserId == _recipe?['user_id'].toString();
    final isAdmin = _currentUserRole == 'admin';

    if (!isOwner && !isAdmin) {
      _showSnackBar(
        _t(
          'You do not have permission to delete this recipe',
          'Anda tidak memiliki izin untuk menghapus resep ini',
        ),
        isError: true,
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.red.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.warning_rounded,
                color: Colors.red,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Text(_t('Delete Recipe', 'Hapus Resep')),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _t(
                'Are you sure you want to delete this recipe?',
                'Apakah Anda yakin ingin menghapus resep ini?',
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Tindakan ini tidak dapat dibatalkan.',
              style: TextStyle(
                fontSize: 12,
                color: Colors.red.shade600,
                fontWeight: FontWeight.w500,
              ),
            ),
            if (isAdmin && !isOwner) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.admin_panel_settings,
                      size: 20,
                      color: Colors.orange.shade700,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _t(
                          'You are deleting this recipe as Admin',
                          'Anda menghapus resep sebagai Admin',
                        ),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.orange.shade700,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(_t('Cancel', 'Batal')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(
              foregroundColor: Colors.red,
              backgroundColor: Colors.red.withValues(alpha: 0.1),
            ),
            child: Text(
              _t('Delete', 'Hapus'),
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await ApiService.delete('/recipes/${widget.recipeId}');
        if (!mounted) return;
        _showSnackBar(
          isAdmin && !isOwner
              ? _t(
                  'Recipe deleted by Admin!',
                  'Resep berhasil dihapus oleh Admin!',
                )
              : _t('Recipe deleted!', 'Resep berhasil dihapus!'),
          isError: false,
        );
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const HomeScreen()),
          (route) => false,
        );
      } catch (e) {
        _showSnackBar('Error: $e', isError: true);
      }
    }
  }

  void _showSnackBar(String message, {required bool isError}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              isError
                  ? Icons.error_outline_rounded
                  : Icons.check_circle_outline_rounded,
              color: Colors.white,
            ),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  String _formatDateTime(String dateTimeStr) {
    try {
      final dateTime = DateTime.parse(dateTimeStr).toLocal();
      final now = DateTime.now();
      final difference = now.difference(dateTime);
      if (difference.inDays > 7) {
        return '${dateTime.day}/${dateTime.month}/${dateTime.year}';
      }
      if (difference.inDays > 0) {
        return '${difference.inDays} hari lalu';
      }
      if (difference.inHours > 0) {
        return '${difference.inHours} jam lalu';
      }
      if (difference.inMinutes > 0) {
        return '${difference.inMinutes} menit lalu';
      }
      return 'Baru saja';
    } catch (e) {
      return dateTimeStr;
    }
  }

  void _showEditCommentDialog(String commentId, String currentContent) {
    final controller = TextEditingController(text: currentContent);
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(_t('Edit Review', 'Edit Ulasan')),
        content: TextField(
          controller: controller,
          maxLines: 3,
          style: AppTheme.fieldText,
          decoration: InputDecoration(
            hintText: _t('Edit your review', 'Edit ulasan Anda'),
            hintStyle: AppTheme.fieldHint,
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(_t('Cancel', 'Batal')),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _editComment(commentId, controller.text);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryCoral,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            child: Text(_t('Save', 'Simpan')),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isOwner = _currentUserId == _recipe?['user_id'].toString();
    final canEdit = isOwner;
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: AppTheme.primaryCoral),
            )
          : _recipe == null
          ? Center(child: Text(_t('Recipe not found', 'Resep tidak ditemukan')))
          : CustomScrollView(
              slivers: [
                _buildAppBar(),
                SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      _buildHeroCard(canEdit),
                      const SizedBox(height: 16),
                      _buildContentCard(),
                      const SizedBox(height: 16),
                      _buildInteractionCard(),
                      const SizedBox(height: 100),
                    ]),
                  ),
                ),
              ],
            ),
      bottomNavigationBar: CustomBottomNav(
        currentIndex: 0,
        avatarUrl: _userAvatarUrl,
        onRefresh: _loadRecipe,
      ),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 180,
      floating: false,
      pinned: true,
      backgroundColor: Colors.transparent,
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(gradient: AppTheme.primaryGradient),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(24, 60, 24, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.25),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.5),
                            width: 2,
                          ),
                        ),
                        child: const Icon(
                          Icons.restaurant_rounded,
                          color: Colors.white,
                          size: 32,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _t('Recipe Detail', 'Detail Resep'),
                              style: AppTheme.headingLarge,
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _t(
                                'Complete recipe information',
                                'Informasi lengkap resep',
                              ),
                              style: const TextStyle(
                                fontSize: 14,
                                color: Colors.white70,
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
      leading: Padding(
        padding: const EdgeInsets.all(8.0),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: AppTheme.primaryCoral.withValues(alpha: 0.3),
                blurRadius: 8,
              ),
            ],
          ),
          child: IconButton(
            icon: const Icon(
              Icons.arrow_back_rounded,
              color: AppTheme.primaryDark,
            ),
            onPressed: () => Navigator.pop(context),
          ),
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.all(8.0),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primaryCoral.withValues(alpha: 0.3),
                  blurRadius: 8,
                ),
              ],
            ),
            child: IconButton(
              icon: Icon(
                _isFavorite
                    ? Icons.bookmark_rounded
                    : Icons.bookmark_border_rounded,
                color: _isFavorite
                    ? AppTheme.primaryCoral
                    : AppTheme.primaryDark,
              ),
              onPressed: _showBoardSelector,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCompactInfoChip(String text, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primaryCoral.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: AppTheme.primaryCoral.withValues(alpha: 0.18),
          width: 1.2,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppTheme.primaryCoral),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCompactRatingChip(double rating, int ratingCount) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primaryCoral.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: AppTheme.primaryCoral.withValues(alpha: 0.18),
          width: 1.2,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          ...List.generate(5, (index) {
            final value = index + 1;
            final icon = rating >= value
                ? Icons.star_rounded
                : rating >= value - 0.5
                ? Icons.star_half_rounded
                : Icons.star_outline_rounded;
            return Icon(icon, size: 13, color: AppTheme.primaryYellow);
          }),
          const SizedBox(width: 6),
          Text(
            '${rating.toStringAsFixed(1)}${ratingCount > 0 ? ' ($ratingCount)' : ''}',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPhotoPill({
    required IconData icon,
    required String label,
    required Color backgroundColor,
    required Color foregroundColor,
  }) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 170),
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.22),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: foregroundColor),
          const SizedBox(width: 5),
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: foregroundColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeroCard(bool canEdit) {
    final profile = _recipe!['profiles'];
    final username = profile?['username'] ?? 'Anonymous';
    final avatarUrl = profile?['avatar_url'];
    final role = profile?['role'] == 'admin'
        ? 'user'
        : (profile?['role'] ?? 'user');
    final isPremium = profile?['is_premium'] ?? false;
    final oderId = _recipe!['user_id'];
    final category = _recipe!['categories'];
    final categoryName = category is Map ? category['name']?.toString() : null;
    return Container(
      decoration: AppTheme.cardDecoration,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
            child: Stack(
              children: [
                _recipe!['image_url'] != null
                    ? Image.network(
                        _recipe!['image_url'],
                        width: double.infinity,
                        height: 280,
                        fit: BoxFit.cover,
                        cacheWidth:
                            (720 * MediaQuery.devicePixelRatioOf(context))
                                .round(),
                        errorBuilder: (_, _, _) => _buildPlaceholderImage(),
                      )
                    : _buildPlaceholderImage(),
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    height: 100,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.25),
                          Colors.black.withValues(alpha: 0.65),
                        ],
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: 14,
                  left: 14,
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: () => Navigator.pop(context),
                      borderRadius: BorderRadius.circular(999),
                      child: Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.92),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.18),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Icon(
                          Icons.arrow_back_rounded,
                          color: AppTheme.primaryDark,
                        ),
                      ),
                    ),
                  ),
                ),
                if (categoryName != null && categoryName.isNotEmpty)
                  Positioned(
                    bottom: 16,
                    right: 16,
                    child: _buildPhotoPill(
                      icon: Icons.category_rounded,
                      label: categoryName,
                      backgroundColor: AppTheme.primaryCoral,
                      foregroundColor: Colors.white,
                    ),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _recipe!['title'] ?? 'Untitled',
                  style: TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                    height: 1.2,
                  ),
                ),
                if (_shouldShowTranslate) ...[
                  const SizedBox(height: 10),
                  _buildTranslateChip(
                    isTranslated: _recipeTranslated,
                    isLoading: _isTranslatingRecipe,
                    onTap: _toggleRecipeTranslate,
                  ),
                ],
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if ((_averageRating ?? 0) > 0)
                      _buildCompactRatingChip(
                        _averageRating!,
                        _ratingCount ?? 0,
                      ),
                    _buildCompactInfoChip(
                      '${_recipe!['cooking_time'] ?? 15} min',
                      Icons.access_time_rounded,
                    ),
                    _buildCompactInfoChip(
                      '${_recipe!['servings'] ?? 1} ${_t('servings', 'porsi')}',
                      Icons.restaurant_menu_rounded,
                    ),
                    if (_recipe!['calories'] != null)
                      _buildCompactInfoChip(
                        '${_recipe!['calories']} ${_t('cal', 'kal')}',
                        Icons.local_fire_department_rounded,
                      ),
                    _buildCompactInfoChip(
                      (_recipe!['difficulty'] ?? 'mudah').toUpperCase(),
                      Icons.bar_chart_rounded,
                    ),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (_recipe!['description'] != null &&
                    _recipe!['description'].toString().isNotEmpty) ...[
                  AppTheme.buildSectionHeader(
                    _t('Description', 'Deskripsi'),
                    Icons.description_rounded,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _recipe!['description'],
                    style: TextStyle(
                      fontSize: 14,
                      color: AppTheme.textSecondary,
                      height: 1.6,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Divider(color: AppTheme.borderColor),
                  const SizedBox(height: 20),
                ],
                GestureDetector(
                  onTap: oderId != null
                      ? () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) =>
                                ProfileScreen(userId: oderId.toString()),
                          ),
                        )
                      : null,
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          AppTheme.primaryYellow.withValues(alpha: 0.2),
                          AppTheme.primaryOrange.withValues(alpha: 0.1),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: AppTheme.primaryYellow.withValues(alpha: 0.3),
                        width: 1.5,
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 54,
                          height: 54,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(
                              colors: AppTheme.getRoleGradient(role),
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.1),
                                blurRadius: 8,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: ClipOval(
                            child: avatarUrl != null
                                ? Image.network(
                                    avatarUrl,
                                    fit: BoxFit.cover,
                                    cacheWidth:
                                        (108 *
                                                MediaQuery.devicePixelRatioOf(
                                                  context,
                                                ))
                                            .round(),
                                  )
                                : Icon(
                                    Icons.person,
                                    color: Colors.white,
                                    size: 28,
                                  ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                username,
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: isPremium
                                      ? const Color(0xFF6C63FF)
                                      : Colors.grey.shade400,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  isPremium
                                      ? 'SAVORA CHEF'
                                      : _t('USER', 'PENGGUNA'),
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (oderId != null)
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppTheme.surfaceColor.withValues(
                                alpha: 0.7,
                              ),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.chevron_right_rounded,
                              color: AppTheme.textSecondary,
                              size: 20,
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Expanded(
                      child: _buildInfoDetailCard(
                        '${_recipe!['cooking_time'] ?? 15}',
                        _t('Minutes', 'Menit'),
                        Icons.access_time_rounded,
                        AppTheme.primaryTeal,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildInfoDetailCard(
                        '${_recipe!['servings'] ?? 1}',
                        _t('Servings', 'Porsi'),
                        Icons.restaurant_menu_rounded,
                        AppTheme.primaryDark,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildInfoDetailCard(
                        (_recipe!['difficulty'] ?? 'mudah').toUpperCase(),
                        _t('Level', 'Tingkat'),
                        Icons.bar_chart_rounded,
                        AppTheme.primaryOrange,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildInfoDetailCard(
                        _recipe!['calories'] != null
                            ? '${_recipe!['calories']}'
                            : 'N/A',
                        _t('Calories', 'Kalori'),
                        Icons.local_fire_department_rounded,
                        AppTheme.primaryCoral,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                _buildActionButtons(canEdit),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoDetailCard(
    String value,
    String label,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [color.withValues(alpha: 0.1), color.withValues(alpha: 0.05)],
        ),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3), width: 1.5),
      ),
      child: Column(
        children: [
          Icon(icon, size: 28, color: color),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: AppTheme.textSecondary,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      width: double.infinity,
      height: 280,
      decoration: const BoxDecoration(gradient: AppTheme.primaryGradient),
      child: Icon(Icons.restaurant_rounded, size: 80, color: Colors.white),
    );
  }

  Widget _buildTranslateChip({
    required bool isTranslated,
    required bool isLoading,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: isLoading ? null : onTap,
        borderRadius: BorderRadius.circular(999),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.92),
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: AppTheme.primaryCoral.withValues(alpha: 0.25),
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                isTranslated ? Icons.undo_rounded : Icons.translate_rounded,
                size: 15,
                color: AppTheme.primaryCoral,
              ),
              const SizedBox(width: 6),
              Text(
                isLoading
                    ? 'Translating...'
                    : (isTranslated ? 'Undo' : 'Translate'),
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.primaryCoral,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActionButtons(bool canEdit) {
    final isAdmin = _currentUserRole == 'admin';
    return Column(
      children: [
        if (canEdit) ...[
          Row(
            children: [
              Expanded(
                child: _buildRecipeActionButton(
                  icon: Icons.edit_rounded,
                  label: _t('Edit', 'Edit'),
                  gradient: AppTheme.tealGradient,
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => EditRecipeScreen(recipe: _recipe!),
                    ),
                  ).then((_) => _loadRecipe()),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildRecipeActionButton(
                  icon: Icons.delete_rounded,
                  label: _t('Delete', 'Hapus'),
                  gradient: LinearGradient(
                    colors: [Colors.red.shade500, Colors.red.shade600],
                  ),
                  shadowColor: Colors.red,
                  onTap: _deleteRecipe,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
        ] else if (isAdmin) ...[
          Container(
            height: 48,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.orange.shade500, Colors.orange.shade700],
              ),
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: Colors.orange.withValues(alpha: 0.3),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: _deleteRecipe,
                borderRadius: BorderRadius.circular(14),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.admin_panel_settings,
                      size: 20,
                      color: Colors.white,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      _t('Delete Recipe (Admin)', 'Hapus Resep (Admin)'),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
        ],
        AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          height: 68,
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            gradient: _isLiked
                ? AppTheme.accentGradient
                : LinearGradient(
                    colors: [
                      Colors.white,
                      AppTheme.primaryCoral.withValues(alpha: 0.06),
                    ],
                  ),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: _isLiked
                  ? Colors.white.withValues(alpha: 0.20)
                  : AppTheme.primaryCoral.withValues(alpha: 0.18),
              width: 1.2,
            ),
            boxShadow: _isLiked
                ? AppTheme.buttonShadow
                : [
                    BoxShadow(
                      color: AppTheme.primaryCoral.withValues(alpha: 0.08),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
          ),
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: _isTogglingLike ? null : _toggleLike,
              borderRadius: BorderRadius.circular(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  AnimatedScale(
                    duration: const Duration(milliseconds: 160),
                    scale: _isLiked ? 1.04 : 1,
                    child: Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(
                          alpha: _isLiked ? 0.24 : 1,
                        ),
                        borderRadius: BorderRadius.circular(13),
                        border: Border.all(
                          color: _isLiked
                              ? Colors.white.withValues(alpha: 0.24)
                              : AppTheme.primaryCoral.withValues(alpha: 0.16),
                        ),
                      ),
                      child: _isTogglingLike
                          ? Padding(
                              padding: const EdgeInsets.all(11),
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: _isLiked
                                    ? Colors.white
                                    : AppTheme.primaryCoral,
                              ),
                            )
                          : Icon(
                              _isLiked
                                  ? Icons.favorite_rounded
                                  : Icons.favorite_border_rounded,
                              size: 24,
                              color: _isLiked
                                  ? Colors.white
                                  : AppTheme.primaryCoral,
                            ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _isLiked
                              ? _t('Liked', 'Disukai')
                              : _t('Like this recipe', 'Suka resep ini'),
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                            color: _isLiked
                                ? Colors.white
                                : AppTheme.primaryCoral,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _likesCount == 0
                              ? _t(
                                  'Be the first to like this recipe',
                                  'Jadilah yang pertama menyukai resep ini',
                                )
                              : _t(
                                  '$_likesCount people like this recipe',
                                  '$_likesCount orang menyukai resep ini',
                                ),
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                            fontSize: 11,
                            color: _isLiked
                                ? Colors.white.withValues(alpha: 0.88)
                                : AppTheme.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 7,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(
                        alpha: _isLiked ? 0.22 : 0.85,
                      ),
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(
                        color: Colors.white.withValues(
                          alpha: _isLiked ? 0.20 : 0,
                        ),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.favorite_rounded,
                          size: 13,
                          color: _isLiked
                              ? Colors.white
                              : AppTheme.primaryCoral,
                        ),
                        const SizedBox(width: 4),
                        AnimatedSwitcher(
                          duration: const Duration(milliseconds: 160),
                          child: Text(
                            _likesCount.toString(),
                            key: ValueKey(_likesCount),
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 13,
                              color: _isLiked
                                  ? Colors.white
                                  : AppTheme.primaryCoral,
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
        Row(
          children: [
            Expanded(
              child: _buildRecipeActionButton(
                icon: _isFavorite
                    ? Icons.bookmark_rounded
                    : Icons.bookmark_border_rounded,
                label: _isFavorite
                    ? _t('Saved', 'Tersimpan')
                    : _t('Save', 'Simpan'),
                gradient: _isFavorite
                    ? AppTheme.orangeGradient
                    : AppTheme.accentGradient,
                shadowColor: _isFavorite
                    ? AppTheme.primaryOrange
                    : AppTheme.primaryCoral,
                onTap: _showBoardSelector,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildRecipeActionButton(
                icon: Icons.share_rounded,
                label: _t('Share', 'Bagikan'),
                gradient: const LinearGradient(
                  colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)],
                ),
                shadowColor: const Color(0xFF8B5CF6),
                onTap: _showShareBottomSheet,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildRecipeActionButton({
    required IconData icon,
    required String label,
    required Gradient gradient,
    required VoidCallback onTap,
    Color? shadowColor,
  }) {
    return Container(
      height: 52,
      decoration: BoxDecoration(
        gradient: gradient,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: (shadowColor ?? AppTheme.primaryTeal).withValues(
              alpha: 0.34,
            ),
            blurRadius: 12,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 21, color: Colors.white),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildContentCard() {
    return Container(
      decoration: AppTheme.cardDecoration,
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_recipe!['video_url'] != null) ...[
            AppTheme.buildSectionHeader(
              _t('Video Tutorial', 'Video Tutorial'),
              Icons.videocam_rounded,
            ),
            const SizedBox(height: 16),
            _buildVideoPlayer(),
            const SizedBox(height: 28),
            const Divider(height: 1),
            const SizedBox(height: 28),
          ] else ...[
            AppTheme.buildSectionHeader(
              _t('Video Tutorial', 'Video Tutorial'),
              Icons.videocam_rounded,
            ),
            const SizedBox(height: 16),
            AppTheme.buildEmptyState(
              icon: Icons.videocam_off_rounded,
              title: _t(
                'User did not upload a video',
                'User tidak mengunggah video',
              ),
            ),
            const SizedBox(height: 28),
            const Divider(height: 1),
            const SizedBox(height: 28),
          ],
          AppTheme.buildSectionHeader(
            _t('Ingredients', 'Bahan-bahan'),
            Icons.restaurant_menu_rounded,
          ),
          const SizedBox(height: 16),
          _buildIngredientsList(),
          const SizedBox(height: 28),
          const Divider(height: 1),
          const SizedBox(height: 28),
          AppTheme.buildSectionHeader(
            _t('Steps', 'Langkah-langkah'),
            Icons.format_list_numbered_rounded,
          ),
          const SizedBox(height: 16),
          _buildStepsList(),
          const SizedBox(height: 28),
          const Divider(height: 1),
          const SizedBox(height: 28),
          AppTheme.buildSectionHeader('Tags', Icons.label_rounded),
          const SizedBox(height: 16),
          _buildTagsList(),
        ],
      ),
    );
  }

  Widget _buildVideoPlayer() {
    if (_isVideoInitializing) {
      return Container(
        height: 200,
        decoration: BoxDecoration(
          color: Colors.black87,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
      );
    }
    if (_chewieController != null && _videoPlayerController != null) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: AspectRatio(
          aspectRatio: _videoPlayerController!.value.aspectRatio,
          child: Chewie(controller: _chewieController!),
        ),
      );
    }
    return Container(
      height: 200,
      decoration: BoxDecoration(
        color: Colors.black87,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 48, color: Colors.red.shade400),
            const SizedBox(height: 12),
            Text(
              _t('Failed to load video', 'Gagal memuat video'),
              style: TextStyle(color: Colors.red.shade400),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildIngredientsList() {
    final ingredients = _extractDynamicList(_recipe!['ingredients']);
    if (ingredients.isEmpty) {
      return AppTheme.buildEmptyState(
        icon: Icons.restaurant_menu_rounded,
        title: _t('No ingredients yet', 'Belum ada bahan'),
      );
    }
    return Column(
      children: ingredients.asMap().entries.map((entry) {
        final index = entry.key;
        final ingredient = entry.value;
        String name;
        String? quantity;
        if (ingredient is String) {
          name = ingredient;
        } else if (ingredient is Map && ingredient.containsKey('name')) {
          name = ingredient['name'].toString();
          quantity = ingredient['quantity']?.toString();
        } else {
          return const SizedBox.shrink();
        }
        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: AppTheme.inputDecoration(AppTheme.primaryYellow),
          child: Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  name,
                  style: TextStyle(
                    fontSize: 14,
                    color: AppTheme.textPrimary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              if (quantity != null)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        AppTheme.primaryOrange.withValues(alpha: 0.3),
                        AppTheme.primaryYellow.withValues(alpha: 0.2),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    quantity,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.primaryCoral,
                    ),
                  ),
                ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildStepsList() {
    final steps = _extractDynamicList(_recipe!['steps']);
    if (steps.isEmpty) {
      return AppTheme.buildEmptyState(
        icon: Icons.format_list_numbered_rounded,
        title: _t('No steps yet', 'Belum ada langkah'),
      );
    }
    return Column(
      children: List.generate(
        steps.length,
        (index) => Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppTheme.primaryCoral.withValues(alpha: 0.1),
                AppTheme.primaryOrange.withValues(alpha: 0.05),
              ],
            ),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: AppTheme.primaryCoral.withValues(alpha: 0.2),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  _buildStepText(steps[index]),
                  style: TextStyle(
                    fontSize: 14,
                    color: AppTheme.textPrimary,
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _buildStepText(dynamic step) {
    if (step is String) return step;
    if (step is Map) {
      final normalized = Map<String, dynamic>.from(step);
      return (normalized['description'] ??
              normalized['text'] ??
              normalized['step'] ??
              step.toString())
          .toString();
    }
    return step.toString();
  }

  Widget _buildTagsList() {
    if (_tags.isEmpty) {
      return AppTheme.buildEmptyState(
        icon: Icons.label_rounded,
        title: _t('No tags yet', 'Belum ada tag'),
      );
    }
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: _tags
          .map(
            (tag) => GestureDetector(
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SearchingScreen(initialTagName: tag),
                ),
              ),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 8,
                ),
                decoration: AppTheme.selectedTagDecoration,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.tag_rounded, size: 14, color: Colors.white),
                    const SizedBox(width: 6),
                    Text(
                      tag,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          )
          .toList(),
    );
  }

  Widget _buildInteractionCard() {
    return Container(
      decoration: AppTheme.cardDecoration,
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppTheme.buildSectionHeader(
            _t('Rating & Reviews', 'Rating & Ulasan'),
            Icons.star_rounded,
          ),
          const SizedBox(height: 20),
          Text(
            _t('Reviews (${_comments.length})', 'Ulasan (${_comments.length})'),
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppTheme.primaryYellow.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: AppTheme.primaryYellow.withValues(alpha: 0.25),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    _t(
                      'Rate when writing your review',
                      'Rating saat menulis ulasan',
                    ),
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textPrimary,
                    ),
                  ),
                ),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: List.generate(5, (index) {
                    final value = index + 1;
                    final isActive = (_selectedReviewRating ?? 0) >= value;
                    return GestureDetector(
                      onTap: () =>
                          setState(() => _selectedReviewRating = value),
                      child: Padding(
                        padding: EdgeInsets.only(left: index == 0 ? 0 : 2),
                        child: Icon(
                          isActive
                              ? Icons.star_rounded
                              : Icons.star_outline_rounded,
                          color: isActive
                              ? AppTheme.primaryYellow
                              : Colors.grey.shade300,
                          size: 28,
                        ),
                      ),
                    );
                  }),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Container(
            decoration: AppTheme.inputDecoration(AppTheme.primaryYellow),
            child: TextField(
              controller: _commentController,
              maxLines: 3,
              minLines: 1,
              style: AppTheme.fieldText,
              decoration: InputDecoration(
                hintText: _t('Write your review...', 'Tulis ulasan Anda...'),
                hintStyle: AppTheme.fieldHint,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
                suffixIcon: Container(
                  margin: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    gradient: AppTheme.accentGradient,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: IconButton(
                    onPressed: _isPostingComment ? null : _postComment,
                    icon: _isPostingComment
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(
                            Icons.send_rounded,
                            color: Colors.white,
                            size: 18,
                          ),
                  ),
                ),
              ),
              textInputAction: TextInputAction.done,
              onSubmitted: (_) => _postComment(),
            ),
          ),
          const SizedBox(height: 16),
          if (_comments.isEmpty)
            AppTheme.buildEmptyState(
              icon: Icons.chat_bubble_outline_rounded,
              title: _t('No reviews yet', 'Belum ada ulasan'),
              subtitle: _t(
                'Be the first to leave a review!',
                'Jadilah yang pertama memberikan ulasan!',
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _comments.length,
              itemBuilder: (context, index) {
                final comment = _comments[index];
                final profile = comment['profiles'] ?? comment['user'];
                final username = profile?['username'] ?? 'Anonymous';
                final avatarUrl = profile?['avatar_url'];
                final commentUserId = comment['user_id'].toString();
                final commentId = comment['id']?.toString() ?? 'comment-$index';
                final isCommentOwner = _currentUserId == commentUserId;
                final isAdmin = _currentUserRole == 'admin';
                final canManageComment = isCommentOwner || isAdmin;
                final commentTranslated = _translatedCommentIds.contains(
                  commentId,
                );
                final commentTranslating = _translatingCommentIds.contains(
                  commentId,
                );
                final commentRating = _ratingFromComment(comment);
                return Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(14),
                  decoration: AppTheme.inputDecoration(AppTheme.primaryYellow),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          GestureDetector(
                            onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) =>
                                    ProfileScreen(userId: commentUserId),
                              ),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    gradient: LinearGradient(
                                      colors: [
                                        Colors.grey.shade300,
                                        Colors.grey.shade400,
                                      ],
                                    ),
                                  ),
                                  child: ClipOval(
                                    child: avatarUrl != null
                                        ? Image.network(
                                            avatarUrl,
                                            fit: BoxFit.cover,
                                            cacheWidth:
                                                (72 *
                                                        MediaQuery.devicePixelRatioOf(
                                                          context,
                                                        ))
                                                    .round(),
                                            errorBuilder: (_, _, _) =>
                                                const Icon(
                                                  Icons.person,
                                                  size: 18,
                                                  color: Colors.white,
                                                ),
                                          )
                                        : const Icon(
                                            Icons.person,
                                            size: 18,
                                            color: Colors.white,
                                          ),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Text(
                                  username,
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppTheme.textPrimary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const Spacer(),
                          if (canManageComment)
                            PopupMenuButton<String>(
                              onSelected: (value) {
                                if (value == 'edit' && isCommentOwner) {
                                  _showEditCommentDialog(
                                    comment['id'].toString(),
                                    comment['content'],
                                  );
                                } else if (value == 'delete') {
                                  _deleteComment(
                                    comment['id'].toString(),
                                    commentUserId,
                                  );
                                }
                              },
                              itemBuilder: (_) => [
                                if (isCommentOwner)
                                  PopupMenuItem(
                                    value: 'edit',
                                    child: Row(
                                      children: [
                                        Icon(Icons.edit_rounded, size: 16),
                                        const SizedBox(width: 8),
                                        Text(_t('Edit', 'Edit')),
                                      ],
                                    ),
                                  ),
                                PopupMenuItem(
                                  value: 'delete',
                                  child: Row(
                                    children: [
                                      Icon(
                                        isAdmin && !isCommentOwner
                                            ? Icons.admin_panel_settings
                                            : Icons.delete_rounded,
                                        size: 16,
                                        color: Colors.red,
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        isAdmin && !isCommentOwner
                                            ? _t(
                                                'Delete (Admin)',
                                                'Hapus (Admin)',
                                              )
                                            : _t('Delete', 'Hapus'),
                                        style: TextStyle(color: Colors.red),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                              icon: Icon(
                                Icons.more_vert_rounded,
                                color: Colors.grey.shade400,
                                size: 18,
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      if (commentRating != null) ...[
                        Row(
                          children: [
                            ...List.generate(5, (starIndex) {
                              final isActive = commentRating >= starIndex + 1;
                              return Icon(
                                isActive
                                    ? Icons.star_rounded
                                    : Icons.star_outline_rounded,
                                size: 15,
                                color: isActive
                                    ? AppTheme.primaryYellow
                                    : Colors.grey.shade300,
                              );
                            }),
                          ],
                        ),
                        const SizedBox(height: 8),
                      ],
                      Text(
                        comment['content'] ?? '',
                        style: TextStyle(
                          fontSize: 13,
                          color: AppTheme.textPrimary,
                          height: 1.4,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(
                            Icons.access_time_rounded,
                            size: 12,
                            color: Colors.grey.shade400,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _formatDateTime(comment['created_at']),
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade500,
                            ),
                          ),
                          if (_shouldShowTranslate) ...[
                            const SizedBox(width: 10),
                            InkWell(
                              onTap: commentTranslating
                                  ? null
                                  : () => _toggleCommentTranslate(
                                      commentId,
                                      index,
                                    ),
                              borderRadius: BorderRadius.circular(999),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      commentTranslated
                                          ? Icons.undo_rounded
                                          : Icons.translate_rounded,
                                      size: 12,
                                      color: AppTheme.primaryCoral,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      commentTranslating
                                          ? 'Translating...'
                                          : (commentTranslated
                                                ? 'Undo'
                                                : 'Translate'),
                                      style: const TextStyle(
                                        fontSize: 11,
                                        color: AppTheme.primaryCoral,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}
