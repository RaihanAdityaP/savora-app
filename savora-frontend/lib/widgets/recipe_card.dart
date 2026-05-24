import 'package:flutter/material.dart';
import '../screens/recipes/detail_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/searching_screen.dart';
import '../services/app_settings_service.dart';
import '../services/favorite_client.dart';
import '../services/recipe_client.dart';
import '../widgets/theme.dart';

class RecipeCard extends StatefulWidget {
  final Map<String, dynamic> recipe;
  final double? rating;
  final VoidCallback? onTap;

  // userId diperlukan untuk operasi favorit
  final String? currentUserId;

  const RecipeCard({
    super.key,
    required this.recipe,
    this.rating,
    this.onTap,
    this.currentUserId,
  });

  @override
  State<RecipeCard> createState() => _RecipeCardState();
}

class _RecipeCardState extends State<RecipeCard> {
  bool _isPressed = false;
  bool _isFavorite = false;
  bool _isLiked = false;
  bool _isTogglingLike = false;
  int _likesCount = 0;
  bool _isCheckingFavorite = true;

  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _syncLikeStateFromRecipe();
    _syncSavedStateFromRecipe();
  }

  @override
  void didUpdateWidget(covariant RecipeCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.recipe['id'] != widget.recipe['id'] ||
        oldWidget.recipe['likes_count'] != widget.recipe['likes_count'] ||
        oldWidget.recipe['is_liked'] != widget.recipe['is_liked']) {
      _syncLikeStateFromRecipe();
    }
    if (oldWidget.recipe['id'] != widget.recipe['id'] ||
        oldWidget.recipe['is_saved'] != widget.recipe['is_saved']) {
      _syncSavedStateFromRecipe();
    }
  }

  void _syncLikeStateFromRecipe() {
    _likesCount = _toInt(widget.recipe['likes_count']);
    _isLiked = widget.recipe['is_liked'] == true;
  }

  void _syncSavedStateFromRecipe() {
    _isFavorite = widget.recipe['is_saved'] == true;
    _isCheckingFavorite = false;
  }

  int _toInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  // ─────────────────────────────────────────────
  // CEK APAKAH SUDAH DI-FAVORITE
  // ─────────────────────────────────────────────

  // ─────────────────────────────────────────────
  // SHOW BOARD SELECTOR
  // ─────────────────────────────────────────────

  Future<void> _showBoardSelector() async {
    try {
      final userId = widget.currentUserId;
      if (userId == null) {
        _showSnackBar(_t('Please log in first', 'Silakan login terlebih dahulu'),
            isError: true);
        return;
      }

      final boards = await FavoriteClient.getBoards(userId);

      if (!mounted) return;

      showModalBottomSheet(
        context: context,
        backgroundColor: AppTheme.surfaceColor,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        builder: (context) => _buildBoardSelectorSheet(boards, userId),
      );
    } catch (e) {
      if (!mounted) return;
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Widget _buildBoardSelectorSheet(
      List<Map<String, dynamic>> boards, String userId) {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Handle
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppTheme.borderColor,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.collections_bookmark_rounded,
                    color: Colors.white, size: 24),
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

          // Buat koleksi baru
          Container(
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
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
                        child: Icon(Icons.add_rounded,
                            color: Colors.white, size: 20),
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
                      const Icon(Icons.arrow_forward_ios_rounded,
                          color: Colors.white, size: 16),
                    ],
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // List boards
          if (boards.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: AppTheme.subtleSurfaceColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppTheme.borderColor),
              ),
              child: Center(
                child: Column(
                  children: [
                    Icon(Icons.collections_bookmark_outlined,
                        size: 48, color: Colors.grey.shade400),
                    const SizedBox(height: 12),
                    Text(
                      _t('No collections yet', 'Belum ada koleksi'),
                      style: TextStyle(
                        fontSize: 16,
                        color: AppTheme.textSecondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _t('Create your first collection',
                          'Buat koleksi pertama Anda'),
                      style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
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
                          _addToBoard(
                            board['id']?.toString() ?? '',
                            board['name'] ?? '',
                          );
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
                                      AppTheme.primaryCoral.withValues(alpha: 0.2),
                                      AppTheme.primaryOrange.withValues(alpha: 0.1),
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
                                        board['description'].toString().isNotEmpty)
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

  // ─────────────────────────────────────────────
  // CREATE BOARD DIALOG
  // ─────────────────────────────────────────────

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
              child: const Icon(Icons.add_rounded, color: Colors.white, size: 24),
            ),
            const SizedBox(width: 12),
            Text(_t('New Collection', 'Koleksi Baru'),
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
              child: TextField(
                controller: nameController,
                decoration: AppTheme.buildInputDecoration(
                  hint: _t('Collection Name', 'Nama Koleksi'),
                  icon: Icons.collections_bookmark_rounded,
                  iconColor: AppTheme.primaryCoral,
                ),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              decoration: AppTheme.inputDecoration(AppTheme.primaryOrange),
              child: TextField(
                controller: descController,
                maxLines: 3,
                decoration: AppTheme.buildInputDecoration(
                  hint: _t('Description (optional)', 'Deskripsi (opsional)'),
                  icon: Icons.description_rounded,
                  iconColor: AppTheme.primaryOrange,
                  maxLines: 3,
                ),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            style: TextButton.styleFrom(foregroundColor: AppTheme.textSecondary),
            child: Text(_t('Cancel', 'Batal'), style: TextStyle(fontWeight: FontWeight.w600)),
          ),
          Container(
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: () async {
                if (nameController.text.trim().isEmpty) {
                  if (!dialogContext.mounted) return;
                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                    SnackBar(content: Text(_t('Collection name is required',
                        'Nama koleksi harus diisi'))),
                  );
                  return;
                }

                final board = await FavoriteClient.createBoard(
                  userId: userId,
                  name: nameController.text.trim(),
                  description: descController.text.trim().isEmpty
                      ? null
                      : descController.text.trim(),
                );

                if (!dialogContext.mounted) return;
                Navigator.pop(dialogContext);

                if (board != null) {
                  if (!mounted) return;
                  _showSnackBar(_t('Collection created!', 'Koleksi berhasil dibuat!'), isError: false);
                  _showBoardSelector();
                } else {
                  if (!mounted) return;
                  _showSnackBar(_t('Failed to create collection',
                      'Gagal membuat koleksi'), isError: true);
                }
              },
              style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12)),
              child: Text(_t('Create', 'Buat'), style: AppTheme.buttonText),
            ),
          ),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // ADD TO BOARD via API
  // ─────────────────────────────────────────────

  Future<void> _addToBoard(String boardId, String boardName) async {
    try {
      final recipeId = widget.recipe['id']?.toString() ?? '';
      final success = await FavoriteClient.addRecipeToBoard(
        boardId: boardId,
        recipeId: recipeId,
      );

      if (!mounted) return;

      if (success) {
        setState(() => _isFavorite = true);
        _showSnackBar(_t('Added to "$boardName"', 'Ditambahkan ke "$boardName"'), isError: false);
      } else {
        _showSnackBar(_t('Recipe is already in this collection',
            'Resep sudah ada di koleksi ini'), isError: true);
      }
    } catch (e) {
      if (!mounted) return;
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Future<void> _toggleLike() async {
    if (_isTogglingLike) return;
    final recipeId = widget.recipe['id']?.toString();
    if (recipeId == null || recipeId.isEmpty) return;

    if (widget.currentUserId == null) {
      _showSnackBar(_t('Please log in first', 'Silakan login terlebih dahulu'),
          isError: true);
      return;
    }

    setState(() => _isTogglingLike = true);
    final result = await RecipeClient.toggleLike(recipeId);
    if (!mounted) return;

    if (result != null) {
      setState(() {
        _likesCount = _toInt(result['likes_count']);
        _isLiked = result['is_liked'] == true;
        widget.recipe['likes_count'] = _likesCount;
        widget.recipe['is_liked'] = _isLiked;
      });
    } else {
      _showSnackBar(_t('Failed to update like', 'Gagal memperbarui like'),
          isError: true);
    }

    if (mounted) setState(() => _isTogglingLike = false);
  }

  // ─────────────────────────────────────────────
  // SNACKBAR HELPER
  // ─────────────────────────────────────────────

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
        backgroundColor:
            isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  // ─────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final profile = widget.recipe['profiles'];
    final username = widget.recipe['author']?['name'] ??
        profile?['username'] ??
        _t('Anonymous', 'Anonim');
    final avatarUrl = profile?['avatar_url'];
    final authorUserId = widget.recipe['user_id'];
    final userRole =
        profile?['role'] == 'admin' ? 'user' : (profile?['role'] ?? 'user');

    final category = widget.recipe['categories'];
    final categoryName = widget.recipe['category'] ??
        category?['name'] ??
        _t('Uncategorized', 'Tanpa Kategori');
    final categoryId = category?['id'];
    final title =
        widget.recipe['title'] ?? widget.recipe['name'] ?? _t('Recipe', 'Resep');
    final imageUrl = widget.recipe['image_url'] ?? widget.recipe['image'];
    final description = widget.recipe['description']?.toString() ?? '';
    final cookingTime = widget.recipe['prep_time'] ??
        widget.recipe['cooking_time'] ??
        widget.recipe['cook_time'];
    final servings = widget.recipe['servings'];
    final calories = widget.recipe['calories'];
    final difficulty = widget.recipe['difficulty']?.toString();
    final ratingCount = _toInt(widget.recipe['rating_count']);
    final effectiveRating = widget.rating ??
        (widget.recipe['rating_avg'] is num
            ? (widget.recipe['rating_avg'] as num).toDouble()
            : double.tryParse(widget.recipe['rating_avg']?.toString() ?? ''));

    final tags = _recipeTags();

    return GestureDetector(
      onTapDown: (_) => setState(() => _isPressed = true),
      onTapUp: (_) {
        setState(() => _isPressed = false);
        if (widget.onTap != null) {
          widget.onTap!();
        } else {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => DetailScreen(
                recipeId: widget.recipe['id'].toString(),
              ),
            ),
          );
        }
      },
      onTapCancel: () => setState(() => _isPressed = false),
      child: AnimatedScale(
        scale: _isPressed ? 0.98 : 1.0,
        duration: const Duration(milliseconds: 150),
        child: Container(
          margin: const EdgeInsets.only(bottom: 20),
          decoration: BoxDecoration(
            color: AppTheme.surfaceColor,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: _isPressed
                  ? AppTheme.primaryCoral.withValues(alpha: 0.4)
                  : AppTheme.primaryCoral.withValues(alpha: 0.2),
              width: 1.5,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(
                    alpha: AppTheme.isDarkMode ? 0.30 : (_isPressed ? 0.12 : 0.08)),
                blurRadius: _isPressed ? 16 : 12,
                offset: Offset(0, _isPressed ? 6 : 4),
              ),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AspectRatio(
                aspectRatio: 1.35,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Container(
                      color: AppTheme.subtleSurfaceColor,
                      child: imageUrl != null
                          ? Image.network(
                              imageUrl.toString(),
                              fit: BoxFit.cover,
                              errorBuilder: (_, _, _) => _buildPlaceholder(),
                            )
                          : _buildPlaceholder(),
                  ),
                  Positioned(
                    top: 14,
                    left: 14,
                    child: _roundOverlayButton(
                      onTap: _isCheckingFavorite ? null : _showBoardSelector,
                      child: Icon(
                        _isFavorite
                            ? Icons.bookmark_rounded
                            : Icons.bookmark_border_rounded,
                        color: _isFavorite
                            ? AppTheme.primaryCoral
                            : Colors.grey.shade800,
                        size: 21,
                      ),
                    ),
                  ),
                  Positioned(
                    top: 14,
                    right: 14,
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: _isTogglingLike ? null : _toggleLike,
                        borderRadius: BorderRadius.circular(999),
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(minWidth: 40),
                          child: Ink(
                            height: 40,
                            padding: const EdgeInsets.symmetric(horizontal: 11),
                            decoration: BoxDecoration(
                              gradient: _isLiked ? AppTheme.accentGradient : null,
                              color: _isLiked
                                  ? null
                                  : Colors.white.withValues(alpha: 0.92),
                              borderRadius: BorderRadius.circular(999),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.18),
                                  blurRadius: 10,
                                ),
                              ],
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                _isLiked
                                    ? Icons.favorite_rounded
                                    : Icons.favorite_border_rounded,
                                color: _isLiked
                                    ? Colors.white
                                    : Colors.grey.shade800,
                                size: 20,
                              ),
                              const SizedBox(width: 5),
                              Text(
                                _likesCount.toString(),
                                style: TextStyle(
                                  color: _isLiked
                                      ? Colors.white
                                      : Colors.grey.shade800,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  ),
                  if (categoryName.toString().isNotEmpty)
                    Positioned(
                      right: 14,
                      bottom: 14,
                      child: _buildCategoryChip(
                        context,
                        categoryId,
                        categoryName.toString(),
                      ),
                    ),
                  Positioned(
                    left: 14,
                    bottom: 14,
                    child: _buildRatingBadge(effectiveRating, ratingCount),
                  ),
                  if (difficulty != null && difficulty.isNotEmpty)
                    Positioned(
                      bottom: 14,
                      left: 0,
                      right: 0,
                      child: Center(child: _buildDifficultyBadge(difficulty)),
                    ),
                ],
              ),
              ),
              Padding(
                padding: const EdgeInsets.all(18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            title.toString(),
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                              height: 1.15,
                              color: AppTheme.textPrimary,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (effectiveRating != null && effectiveRating > 0) ...[
                          const SizedBox(width: 10),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 8, vertical: 5),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryCoral
                                  .withValues(alpha: 0.10),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.star_rounded,
                                    color: AppTheme.primaryYellow, size: 16),
                                const SizedBox(width: 3),
                                Text(
                                  effectiveRating.toStringAsFixed(1),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w800,
                                    color: AppTheme.primaryCoral,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (description.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Text(
                        description,
                        style: AppTheme.bodySmall.copyWith(height: 1.4),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    if (cookingTime != null ||
                        servings != null ||
                        calories != null) ...[
                      const SizedBox(height: 14),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          if (cookingTime != null)
                            _buildMetaChip(
                              Icons.access_time_rounded,
                              '$cookingTime ${_t('min', 'mnt')}',
                            ),
                          if (servings != null)
                            _buildMetaChip(
                              Icons.people_rounded,
                              '$servings ${_t('servings', 'porsi')}',
                            ),
                          if (calories != null)
                            _buildMetaChip(
                              Icons.local_fire_department_rounded,
                              '$calories ${_t('cal', 'kal')}',
                            ),
                        ],
                      ),
                    ],
                    if (tags.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: tags
                            .take(3)
                            .map((tag) => _buildTagChip(context, tag))
                            .toList(),
                      ),
                    ],
                    const SizedBox(height: 16),
                    Container(height: 1, color: AppTheme.borderColor),
                    const SizedBox(height: 12),
                    GestureDetector(
                      onTap: authorUserId != null
                          ? () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      ProfileScreen(userId: authorUserId),
                                ),
                              )
                          : null,
                      child: Row(
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: LinearGradient(
                                colors: AppTheme.getRoleGradient(userRole),
                              ),
                            ),
                            child: ClipOval(
                              child: avatarUrl != null
                                  ? Image.network(
                                      avatarUrl,
                                      fit: BoxFit.cover,
                                      errorBuilder: (_, _, _) => const Icon(
                                        Icons.person_rounded,
                                        size: 18,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.person_rounded,
                                      size: 18, color: Colors.white),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              username,
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.textPrimary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (profile?['role'] == 'admin')
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                gradient: AppTheme.adminGradient,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Text(
                                'Admin',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
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

  // ─────────────────────────────────────────────
  // HELPER WIDGETS
  // ─────────────────────────────────────────────

  Widget _buildPlaceholder() {
    return Container(
      color: AppTheme.subtleSurfaceColor,
      child: Center(
        child:
            Icon(Icons.restaurant_rounded, size: 48, color: AppTheme.textMuted),
      ),
    );
  }

  Widget _roundOverlayButton({
    required VoidCallback? onTap,
    required Widget child,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Ink(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.92),
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.18),
                blurRadius: 10,
              ),
            ],
          ),
          child: Center(child: child),
        ),
      ),
    );
  }

  Widget _buildRatingBadge(double? rating, int ratingCount) {
    if (rating != null && rating > 0) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: AppTheme.primaryYellow,
          borderRadius: BorderRadius.circular(999),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.20),
              blurRadius: 8,
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.star_rounded, color: Color(0xFF1C1917), size: 15),
            const SizedBox(width: 4),
            Text(
              rating.toStringAsFixed(1),
              style: const TextStyle(
                color: Color(0xFF1C1917),
                fontSize: 13,
                fontWeight: FontWeight.w900,
              ),
            ),
            if (ratingCount > 0) ...[
              const SizedBox(width: 3),
              Text(
                '($ratingCount)',
                style: TextStyle(
                  color: const Color(0xFF1C1917).withValues(alpha: 0.72),
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ],
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _t('No rating yet', 'Belum ada rating'),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _buildDifficultyBadge(String difficulty) {
    final key = difficulty.toLowerCase();
    final color = switch (key) {
      'easy' || 'mudah' => const Color(0xFF22C55E),
      'medium' || 'sedang' => const Color(0xFFEAB308),
      'hard' || 'sulit' => const Color(0xFFEF4444),
      _ => Colors.grey,
    };
    final label = switch (key) {
      'easy' || 'mudah' => _t('Easy', 'Mudah'),
      'medium' || 'sedang' => _t('Medium', 'Sedang'),
      'hard' || 'sulit' => _t('Hard', 'Sulit'),
      _ => difficulty,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.18),
            blurRadius: 8,
          ),
        ],
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }

  Widget _buildMetaChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: AppTheme.subtleSurfaceColor,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppTheme.borderColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: AppTheme.primaryCoral),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              color: AppTheme.textSecondary,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  List<dynamic> _recipeTags() {
    final directTags = widget.recipe['tags'];
    if (directTags is List) return directTags;

    final recipeTags = widget.recipe['recipe_tags'];
    if (recipeTags is! List) return const [];

    return recipeTags
        .map((rt) => rt is Map ? rt['tags'] : null)
        .where((tag) => tag != null)
        .toList();
  }

  Widget _buildCategoryChip(
      BuildContext context, dynamic categoryId, String categoryName) {
    return GestureDetector(
      onTap: categoryId != null
          ? () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => SearchingScreen(
                    initialCategoryId: categoryId,
                    initialCategoryName: categoryName,
                  ),
                ),
              )
          : null,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          gradient: AppTheme.categoryGradient,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(
          categoryName,
          style: TextStyle(
            color: Colors.white,
            fontSize: 9,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildTagChip(BuildContext context, dynamic tag) {
    final tagName = tag is Map ? (tag['name'] ?? '') : tag.toString();
    final tagId = tag is Map ? tag['id'] : null;

    return GestureDetector(
      onTap: tagId != null
          ? () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => SearchingScreen(
                    initialTagId: tagId,
                    initialTagName: tagName,
                  ),
                ),
              )
          : null,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: AppTheme.tagBorder.withValues(alpha: 0.3),
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: AppTheme.tagBorder, width: 1),
        ),
        child: Text(
          '#$tagName',
          style: TextStyle(
            color: AppTheme.primaryCoral,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
