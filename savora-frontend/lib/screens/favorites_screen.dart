import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/favorite_client.dart';
import '../widgets/custom_app_bar.dart';
import '../widgets/custom_bottom_nav.dart';
import '../widgets/recipe_card.dart';
import '../widgets/theme.dart';
import 'detail_screen.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen>
    with SingleTickerProviderStateMixin {
  List<Map<String, dynamic>> _collections = [];
  Map<String, dynamic>? _selectedCollection;
  List<Map<String, dynamic>> _collectionRecipes = [];
  final Map<String, List<String>> _collectionPreviews = {};
  bool _isLoading = true;
  String? _userAvatarUrl;

  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
        duration: const Duration(milliseconds: 1200), vsync: this);
    _fadeAnimation =
        CurvedAnimation(parent: _animationController, curve: Curves.easeInOut);
    _slideAnimation =
        Tween<Offset>(begin: const Offset(0, 0.3), end: Offset.zero).animate(
            CurvedAnimation(
                parent: _animationController, curve: Curves.easeOutCubic));

    _loadUserAvatar();
    _loadCollections();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadUserAvatar() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId != null) {
        final response = await ApiService.get('/users/$userId');
        if (!mounted) return;
        if (response['success'] == true) {
          setState(() => _userAvatarUrl = response['data']?['avatar_url']);
        }
      }
    } catch (e) {
      debugPrint('Error loading user avatar: $e');
    }
  }

  Future<void> _loadCollections() async {
    setState(() => _isLoading = true);
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) {
        setState(() => _isLoading = false);
        return;
      }

      final collections = await FavoriteClient.getBoards(userId);

      if (!mounted) return;
      setState(() {
        _collections = collections;
        _isLoading = false;
      });

      _loadAllCollectionPreviews(collections);
      _animationController.forward();
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      debugPrint('Error loading collections: $e');
    }
  }

  Future<void> _loadAllCollectionPreviews(
      List<Map<String, dynamic>> collections) async {
    for (final collection in collections) {
      final id = collection['id']?.toString();
      if (id == null) continue;
      try {
        final recipes = await FavoriteClient.getBoardRecipes(id);
        if (!mounted) return;
        final images = <String>[];
        for (final boardRecipe in recipes) {
          if (images.length >= 4) break;
          final recipe =
              (boardRecipe['recipes'] as Map<String, dynamic>?) ?? boardRecipe;
          final url = recipe['image_url'];
          if (url != null && url.toString().isNotEmpty) {
            images.add(url.toString());
          }
        }
        if (mounted) {
          setState(() => _collectionPreviews[id] = images);
        }
      } catch (e) {
        debugPrint('Error loading preview for collection $id: $e');
      }
    }
  }

  Future<void> _loadCollectionRecipes(String collectionId) async {
    setState(() => _isLoading = true);
    try {
      final recipes = await FavoriteClient.getBoardRecipes(collectionId);
      if (!mounted) return;
      setState(() {
        _collectionRecipes = recipes;
        _isLoading = false;
      });
      _animationController.forward(from: 0);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      debugPrint('Error loading collection recipes: $e');
    }
  }

  Future<void> _deleteCollection(Map<String, dynamic> collection) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                  color: Colors.red.shade100,
                  borderRadius: BorderRadius.circular(10)),
              child: Icon(Icons.delete_outline_rounded,
                  color: Colors.red.shade600, size: 24),
            ),
            const SizedBox(width: 12),
            const Text('Hapus Koleksi'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Apakah Anda yakin ingin menghapus koleksi "${collection['name']}"?'),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.amber.shade200)),
              child: Row(
                children: [
                  Icon(Icons.warning_rounded,
                      color: Colors.amber.shade700, size: 20),
                  const SizedBox(width: 8),
                  const Expanded(
                      child: Text(
                          'Semua resep dalam koleksi ini akan dihapus dari koleksi.',
                          style: TextStyle(fontSize: 12))),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                foregroundColor: Colors.white),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final success =
            await FavoriteClient.deleteBoard(collection['id'].toString());
        if (!mounted) return;
        if (success) {
          _showSnackBar('Koleksi berhasil dihapus!', isError: false);
          _loadCollections();
        } else {
          _showSnackBar('Gagal menghapus koleksi', isError: true);
        }
      } catch (e) {
        if (!mounted) return;
        _showSnackBar('Error: $e', isError: true);
      }
    }
  }

  void _showEditCollectionDialog(Map<String, dynamic> collection) {
    final nameController = TextEditingController(text: collection['name']);
    final descController =
        TextEditingController(text: collection['description'] ?? '');

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
                  borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.edit_rounded, color: Colors.white, size: 24),
            ),
            const SizedBox(width: 12),
            const Text('Edit Koleksi', style: AppTheme.headingMedium),
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
                  hint: 'Nama Koleksi',
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
                  hint: 'Deskripsi (opsional)',
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
            child: const Text('Batal'),
          ),
          Container(
            decoration: AppTheme.primaryButtonDecoration,
            child: TextButton(
              onPressed: () async {
                if (nameController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                      const SnackBar(content: Text('Nama koleksi harus diisi')));
                  return;
                }
                try {
                  final success = await FavoriteClient.updateBoard(
                    boardId: collection['id'].toString(),
                    name: nameController.text.trim(),
                    description: descController.text.trim(),
                  );
                  if (!dialogContext.mounted) return;
                  Navigator.pop(dialogContext);
                  if (!mounted) return;
                  if (success) {
                    _showSnackBar('Koleksi berhasil diperbarui!', isError: false);
                    _loadCollections();
                  } else {
                    _showSnackBar('Gagal memperbarui koleksi', isError: true);
                  }
                } catch (e) {
                  _showSnackBar('Error: $e', isError: true);
                }
              },
              child: const Text('Simpan', style: AppTheme.buttonText),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _removeRecipeFromCollection(Map<String, dynamic> boardRecipe) async {
    final recipe = boardRecipe['recipes'] ?? boardRecipe;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Hapus dari Koleksi'),
        content: Text('Hapus "${recipe['title']}" dari koleksi ini?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                foregroundColor: Colors.white),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final success = await FavoriteClient.removeRecipeFromBoard(
          boardId: _selectedCollection!['id'].toString(),
          recipeId: recipe['id'].toString(),
        );
        if (!mounted) return;
        if (success) {
          _showSnackBar('Resep dihapus dari koleksi', isError: false);
          _loadCollectionRecipes(_selectedCollection!['id'].toString());
        } else {
          _showSnackBar('Gagal menghapus resep', isError: true);
        }
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
                color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _showCreateCollectionDialog() {
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
                  borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.add_rounded, color: Colors.white, size: 24),
            ),
            const SizedBox(width: 12),
            const Text('Koleksi Baru', style: AppTheme.headingMedium),
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
                  hint: 'Nama Koleksi',
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
                  hint: 'Deskripsi (opsional)',
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
            child: const Text('Batal'),
          ),
          Container(
            decoration: AppTheme.primaryButtonDecoration,
            child: TextButton(
              onPressed: () async {
                if (nameController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                      const SnackBar(content: Text('Nama koleksi harus diisi')));
                  return;
                }
                try {
                  final userId = ApiService.currentUserId;
                  if (userId == null) return;
                  final result = await FavoriteClient.createBoard(
                    userId: userId,
                    name: nameController.text.trim(),
                    description: descController.text.trim(),
                  );
                  if (!dialogContext.mounted) return;
                  Navigator.pop(dialogContext);
                  if (!mounted) return;
                  if (result != null) {
                    _loadCollections();
                    _showSnackBar('Koleksi berhasil dibuat!', isError: false);
                  } else {
                    _showSnackBar('Gagal membuat koleksi', isError: true);
                  }
                } catch (e) {
                  _showSnackBar('Error: $e', isError: true);
                }
              },
              child: const Text('Buat', style: AppTheme.buttonText),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: const CustomAppBar(showBackButton: false),
      body: _isLoading
          ? _buildLoadingState()
          : _selectedCollection == null
              ? _buildCollectionsView()
              : _buildCollectionRecipesView(),
      bottomNavigationBar: CustomBottomNav(
        currentIndex: 3,
        avatarUrl: _userAvatarUrl,
        onRefresh: () {
          if (_selectedCollection != null) {
            _loadCollectionRecipes(_selectedCollection!['id'].toString());
          } else {
            _loadCollections();
          }
        },
      ),
      floatingActionButton: _selectedCollection == null
          ? Container(
              decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(16),
                boxShadow: AppTheme.buttonShadow,
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: _showCreateCollectionDialog,
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.add_rounded, color: Colors.white, size: 24),
                        SizedBox(width: 8),
                        Text('Koleksi Baru',
                            style: TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(24),
              boxShadow: AppTheme.buttonShadow,
            ),
            child: const CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                strokeWidth: 3),
          ),
          const SizedBox(height: 24),
          const Text('Memuat koleksi...', style: AppTheme.bodyLarge),
        ],
      ),
    );
  }

  Widget _buildCollectionsView() {
    if (_collections.isEmpty) return _buildEmptyCollectionsState();

    return FadeTransition(
      opacity: _fadeAnimation,
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          // ── TRANSPARENT HEADER ──
          SliverToBoxAdapter(
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 8),
                child: SlideTransition(
                  position: _slideAnimation,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryCoral.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                  color: AppTheme.primaryCoral.withValues(alpha: 0.25),
                                  width: 1.5),
                            ),
                            child: const Icon(Icons.collections_bookmark_rounded,
                                color: AppTheme.primaryCoral, size: 28),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Koleksi Resep',
                                  style: AppTheme.headingMedium
                                      .copyWith(color: AppTheme.textPrimary),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Kumpulan resep favorit Anda',
                                  style: TextStyle(
                                      fontSize: 14,
                                      color: AppTheme.textSecondary),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 14, vertical: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.primaryCoral.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                              color: AppTheme.primaryCoral.withValues(alpha: 0.2)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.folder_special_rounded,
                                color: AppTheme.primaryCoral, size: 16),
                            const SizedBox(width: 6),
                            Text(
                              '${_collections.length} Koleksi',
                              style: const TextStyle(
                                  fontSize: 13,
                                  color: AppTheme.primaryCoral,
                                  fontWeight: FontWeight.w600),
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
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: _buildBoardCard(_collections[index], index),
                ),
                childCount: _collections.length,
              ),
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
  }

  Widget _buildBoardCard(Map<String, dynamic> collection, int index) {
    final recipeCount = collection['recipe_count'] ?? 0;
    final description = collection['description'];
    final collectionId = collection['id']?.toString() ?? '';
    final previews = _collectionPreviews[collectionId] ?? [];

    return GestureDetector(
      onTap: () {
        setState(() => _selectedCollection = collection);
        _loadCollectionRecipes(collection['id'].toString());
      },
      onLongPress: () => _showCollectionOptions(collection),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: SizedBox(
                  width: 100,
                  height: 100,
                  child: _buildPhotoGrid(previews),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      collection['name'] ?? '',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.textPrimary,
                        letterSpacing: -0.3,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    if (description != null &&
                        description.toString().trim().isNotEmpty)
                      Text(
                        description,
                        style: TextStyle(
                            fontSize: 12,
                            color: AppTheme.textSecondary,
                            height: 1.3),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      )
                    else
                      Text(
                        'Koleksi resep spesial',
                        style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade400,
                            fontStyle: FontStyle.italic),
                      ),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryCoral.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.restaurant_rounded,
                              size: 13, color: AppTheme.primaryCoral),
                          const SizedBox(width: 4),
                          Text(
                            '$recipeCount Resep',
                            style: TextStyle(
                                fontSize: 12,
                                color: AppTheme.primaryCoral,
                                fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: () => _showCollectionOptions(collection),
                icon: Icon(Icons.more_vert_rounded, color: Colors.grey.shade400),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPhotoGrid(List<String> images) {
    const gap = 3.0;

    Widget slot(int index) {
      final url = index < images.length ? images[index] : null;
      final hasImage = url != null && url.isNotEmpty;
      return SizedBox.expand(
        child: hasImage
            ? Image.network(
                url,
                fit: BoxFit.cover,
                width: double.infinity,
                height: double.infinity,
                errorBuilder: (_, _, _) => Container(color: Colors.grey.shade300),
              )
            : Container(color: Colors.grey.shade300),
      );
    }

    return Container(
      color: Colors.white,
      child: Column(
        children: [
          Expanded(
            child: Row(children: [
              Expanded(child: slot(0)),
              const SizedBox(width: gap),
              Expanded(child: slot(1)),
            ]),
          ),
          const SizedBox(height: gap),
          Expanded(
            child: Row(children: [
              Expanded(child: slot(2)),
              const SizedBox(width: gap),
              Expanded(child: slot(3)),
            ]),
          ),
        ],
      ),
    );
  }

  void _showCollectionOptions(Map<String, dynamic> collection) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Center(
                child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                        color: Colors.grey.shade300,
                        borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: 20),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                      gradient: AppTheme.accentGradient,
                      borderRadius: BorderRadius.circular(12)),
                  child: const Icon(Icons.collections_bookmark_rounded,
                      color: Colors.white, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                    child: Text(collection['name'] ?? '',
                        style: AppTheme.headingMedium)),
              ],
            ),
            const SizedBox(height: 24),
            _buildOptionTile(Icons.edit_rounded, 'Edit Koleksi',
                'Ubah nama dan deskripsi', Colors.blue.shade600, () {
              Navigator.pop(context);
              _showEditCollectionDialog(collection);
            }),
            const SizedBox(height: 12),
            _buildOptionTile(Icons.delete_rounded, 'Hapus Koleksi',
                'Hapus koleksi ini secara permanen', Colors.red.shade600, () {
              Navigator.pop(context);
              _deleteCollection(collection);
            }),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildOptionTile(IconData icon, String title, String subtitle,
      Color color, VoidCallback onTap) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: 0.3), width: 1.5),
          ),
          child: Row(
            children: [
              Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(10)),
                  child: Icon(icon, color: color, size: 24)),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title,
                        style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: color)),
                    Text(subtitle, style: AppTheme.bodySmall),
                  ],
                ),
              ),
              Icon(Icons.chevron_right_rounded, color: color),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCollectionRecipesView() {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          // ── TRANSPARENT HEADER ──
          SliverToBoxAdapter(
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: () => setState(() {
                        _selectedCollection = null;
                        _collectionRecipes = [];
                      }),
                      icon: const Icon(Icons.arrow_back_rounded,
                          color: AppTheme.textPrimary),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _selectedCollection!['name'] ?? '',
                            style: AppTheme.headingMedium
                                .copyWith(color: AppTheme.textPrimary),
                          ),
                          Text(
                            '${_collectionRecipes.length} resep',
                            style: TextStyle(
                                fontSize: 13, color: AppTheme.textSecondary),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      onPressed: () =>
                          _showCollectionOptions(_selectedCollection!),
                      icon: const Icon(Icons.more_vert_rounded,
                          color: AppTheme.textPrimary),
                    ),
                  ],
                ),
              ),
            ),
          ),
          if (_collectionRecipes.isEmpty)
            SliverFillRemaining(
              child: AppTheme.buildEmptyState(
                icon: Icons.restaurant_rounded,
                title: 'Belum ada resep',
                subtitle: 'Tambahkan resep ke koleksi ini',
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.all(20),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final boardRecipe = _collectionRecipes[index];
                    final Map<String, dynamic> recipe = {
                      ...((boardRecipe['recipes'] as Map<String, dynamic>?) ??
                          boardRecipe),
                    };
                    return Dismissible(
                      key: Key(
                          boardRecipe['id']?.toString() ?? index.toString()),
                      direction: DismissDirection.endToStart,
                      background: Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        decoration: BoxDecoration(
                          color: Colors.red.shade500,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        alignment: Alignment.centerRight,
                        padding: const EdgeInsets.only(right: 24),
                        child: const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.delete_rounded,
                                color: Colors.white, size: 28),
                            SizedBox(height: 4),
                            Text('Hapus',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                      confirmDismiss: (direction) async =>
                          await showDialog<bool>(
                            context: context,
                            builder: (context) => AlertDialog(
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(20)),
                              title: const Text('Hapus dari Koleksi'),
                              content: Text(
                                  'Hapus "${recipe['title']}" dari koleksi ini?'),
                              actions: [
                                TextButton(
                                    onPressed: () =>
                                        Navigator.pop(context, false),
                                    child: const Text('Batal')),
                                ElevatedButton(
                                  onPressed: () =>
                                      Navigator.pop(context, true),
                                  style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.red.shade600,
                                      foregroundColor: Colors.white),
                                  child: const Text('Hapus'),
                                ),
                              ],
                            ),
                          ) ??
                          false,
                      onDismissed: (direction) async {
                        try {
                          await FavoriteClient.removeRecipeFromBoard(
                            boardId: _selectedCollection!['id'].toString(),
                            recipeId: recipe['id'].toString(),
                          );
                          _showSnackBar('Resep dihapus dari koleksi',
                              isError: false);
                          setState(() => _collectionRecipes.removeAt(index));
                        } catch (e) {
                          _showSnackBar('Error: $e', isError: true);
                          _loadCollectionRecipes(
                              _selectedCollection!['id'].toString());
                        }
                      },
                      child: Stack(
                        children: [
                          RecipeCard(
                            recipe: recipe,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                    builder: (context) => DetailScreen(
                                        recipeId: recipe['id'].toString())),
                              ).then((_) => _loadCollectionRecipes(
                                  _selectedCollection!['id'].toString()));
                            },
                          ),
                          Positioned(
                            top: 8,
                            right: 8,
                            child: Material(
                              color: Colors.transparent,
                              child: InkWell(
                                onTap: () =>
                                    _removeRecipeFromCollection(boardRecipe),
                                borderRadius: BorderRadius.circular(20),
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: Colors.red.shade500,
                                    shape: BoxShape.circle,
                                    boxShadow: [
                                      BoxShadow(
                                          color: Colors.black
                                              .withValues(alpha: 0.2),
                                          blurRadius: 4)
                                    ],
                                  ),
                                  child: const Icon(Icons.close_rounded,
                                      color: Colors.white, size: 16),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                  childCount: _collectionRecipes.length,
                ),
              ),
            ),
          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
  }

  Widget _buildEmptyCollectionsState() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TweenAnimationBuilder<double>(
              duration: const Duration(milliseconds: 1400),
              tween: Tween(begin: 0.0, end: 1.0),
              curve: Curves.elasticOut,
              builder: (context, value, child) => Transform.scale(
                scale: value,
                child: Container(
                  width: 140,
                  height: 140,
                  decoration: BoxDecoration(
                    gradient: AppTheme.cardGradient,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: AppTheme.primaryCoral.withValues(alpha: 0.2),
                        blurRadius: 30,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: const Icon(Icons.collections_bookmark_outlined,
                      size: 70, color: AppTheme.primaryCoral),
                ),
              ),
            ),
            const SizedBox(height: 32),
            const Text('Belum Ada Koleksi',
                style: AppTheme.headingMedium, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            Text(
                'Mulai kumpulkan resep favoritmu!\nBuat koleksi untuk mengorganisir resep.',
                style: AppTheme.bodyLarge
                    .copyWith(color: AppTheme.textSecondary, height: 1.5),
                textAlign: TextAlign.center),
            const SizedBox(height: 32),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppTheme.primaryCoral.withValues(alpha: 0.1),
                    AppTheme.primaryOrange.withValues(alpha: 0.05),
                  ],
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                    color: AppTheme.primaryCoral.withValues(alpha: 0.2)),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Icon(Icons.lightbulb_outline_rounded,
                          color: AppTheme.primaryCoral, size: 20),
                      const SizedBox(width: 8),
                      Text('Tips',
                          style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.primaryCoral)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Buat koleksi berdasarkan kategori seperti "Sarapan Sehat", "Menu Cepat", atau "Makanan Favorit Keluarga"',
                    style: TextStyle(
                        fontSize: 13,
                        color: AppTheme.textSecondary,
                        height: 1.4),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}