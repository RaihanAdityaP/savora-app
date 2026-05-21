import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import '../services/app_settings_service.dart';
import '../services/user_client.dart';
import '../widgets/custom_app_bar.dart';
import '../widgets/custom_bottom_nav.dart';
import '../widgets/recipe_card.dart';
import '../widgets/theme.dart';
import 'profile/edit_profile_screen.dart';
import 'profile/follow_list_screen.dart';
import 'profile/liked_recipes_screen.dart';
import 'recipes/detail_screen.dart';

class ProfileScreen extends StatefulWidget {
  final String? userId;
  const ProfileScreen({super.key, this.userId});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen>
    with SingleTickerProviderStateMixin {
  final _usernameController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _bioController = TextEditingController();

  bool _isLoading = true;
  bool _isUploadingImage = false;
  String? _avatarUrl;
  String _userRole = 'user';
  bool _isPremium = false;
  String? _currentUserId;
  bool _isOwnProfile = false;

  bool _isFollowing = false;
  bool _isFollowLoading = false;
  int _followerCount = 0;
  int _followingCount = 0;
  int _likedRecipesCount = 0;

  List<Map<String, dynamic>> _userRecipes = [];
  final Map<String, double> _recipeRatings = {};

  final ImagePicker _picker = ImagePicker();
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  bool get _useGradientHeader => false;

  List<Color> get _primaryGradient {
    if (_useGradientHeader) return AppTheme.getRoleGradient('admin');
    return [AppTheme.primaryCoral, AppTheme.primaryOrange];
  }

  Color get _primaryColor {
    if (_useGradientHeader) return const Color(0xFFFFD700);
    return AppTheme.primaryCoral;
  }

  Color get _secondaryColor {
    if (_useGradientHeader) return const Color(0xFFFFA500);
    return AppTheme.primaryOrange;
  }

  // ignore: unused_element
  LinearGradient get _headerGradient => const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color(0xFFFFD700),
          Color(0xFFFFA500),
          Color(0xFFFF8C00),
          Color(0xFFFFD700),
        ],
      );

  @override
  void initState() {
    super.initState();
    _currentUserId = ApiService.currentUserId;
    _isOwnProfile = widget.userId == null || widget.userId == _currentUserId;

    _animationController = AnimationController(
        duration: const Duration(milliseconds: 1000), vsync: this);
    _fadeAnimation = CurvedAnimation(
        parent: _animationController, curve: Curves.easeInOut);
    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.2),
      end: Offset.zero,
    ).animate(CurvedAnimation(
        parent: _animationController, curve: Curves.easeOutCubic));

    _loadProfile();
    _loadUserRecipes();
    if (!_isOwnProfile) _checkIfFollowing();
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _fullNameController.dispose();
    _bioController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    try {
      final targetUserId = widget.userId ?? _currentUserId;
      if (targetUserId == null) return;

      final profile = await UserClient.getProfile(targetUserId);
      if (!mounted) return;

      if (profile != null) {
        setState(() {
          _usernameController.text = profile['username'] ?? '';
          _fullNameController.text = profile['full_name'] ?? '';
          _bioController.text = profile['bio'] ?? '';
          _avatarUrl = profile['avatar_url'];
          _userRole = 'user';
          _isPremium = profile['is_premium'] ?? false;
          _followerCount =
              ((profile['followers_count'] ?? profile['total_followers'])
                          as num?)
                      ?.toInt() ??
                  0;
          _followingCount =
              ((profile['following_count'] ?? profile['total_following'])
                          as num?)
                      ?.toInt() ??
                  0;
          _likedRecipesCount =
              (profile['liked_recipes_count'] as num?)?.toInt() ?? 0;
          _isLoading = false;
        });
        _animationController.forward();
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      if (mounted) {
        _showSnackBar('${_t('Failed to load profile', 'Gagal memuat profil')}: $e', isError: true);
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadUserRecipes() async {
    try {
      final targetUserId = widget.userId ?? _currentUserId;
      if (targetUserId == null) return;

      final recipes = await UserClient.getUserRecipes(targetUserId);
      if (!mounted) return;

      for (var recipe in recipes) {
        final avg = recipe['average_rating'];
        if (avg != null) {
          _recipeRatings[recipe['id']] = (avg as num).toDouble();
        }
      }

      if (mounted) setState(() => _userRecipes = recipes);
    } catch (e) {
      debugPrint('Error loading user recipes: $e');
    }
  }

  Future<void> _checkIfFollowing() async {
    if (_currentUserId == null || _isOwnProfile) return;
    try {
      final isFollowing = await UserClient.isFollowing(
        targetUserId: widget.userId!,
        myUserId: _currentUserId!,
      );
      if (mounted) setState(() => _isFollowing = isFollowing);
    } catch (e) {
      debugPrint('Error checking follow status: $e');
    }
  }

  Future<void> _toggleFollow() async {
    if (_currentUserId == null || _isOwnProfile) return;
    setState(() => _isFollowLoading = true);
    try {
      bool success;
      if (_isFollowing) {
        success = await UserClient.unfollow(
          targetUserId: widget.userId!,
          followerId: _currentUserId!,
        );
        if (mounted && success) {
          setState(() => _isFollowing = false);
          _showSnackBar(_t('Unfollowed', 'Berhenti mengikuti'), isError: false);
        }
      } else {
        success = await UserClient.follow(
          targetUserId: widget.userId!,
          followerId: _currentUserId!,
        );
        if (mounted && success) {
          setState(() => _isFollowing = true);
          _showSnackBar(_t('Followed successfully', 'Berhasil mengikuti'), isError: false);
        }
      }
      await _loadProfile();
    } catch (e) {
      if (mounted) _showSnackBar('Error: $e', isError: true);
    } finally {
      if (mounted) setState(() => _isFollowLoading = false);
    }
  }

  void _openLikedRecipes() {
    final targetUserId = widget.userId ?? _currentUserId;
    if (targetUserId == null) return;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => LikedRecipesScreen(
          userId: targetUserId,
          username: _usernameController.text.isEmpty
              ? null
              : _usernameController.text,
        ),
      ),
    ).then((_) => _loadProfile());
  }

  void _openEditProfile() {
    final userId = _currentUserId;
    if (userId == null) return;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => EditProfileScreen(
          userId: userId,
          initialProfile: {
            'username': _usernameController.text,
            'full_name': _fullNameController.text,
            'bio': _bioController.text,
            'avatar_url': _avatarUrl,
          },
        ),
      ),
    ).then((changed) {
      if (changed == true && mounted) {
        _loadProfile();
      }
    });
  }

  void _openFollowList({required bool followers}) {
    final targetUserId = widget.userId ?? _currentUserId;
    if (targetUserId == null) return;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => FollowListScreen(
          userId: targetUserId,
          followers: followers,
          username: _usernameController.text.isEmpty ? null : _usernameController.text,
        ),
      ),
    );
  }

  Future<void> _pickAndUploadImage() async {
    if (!_isOwnProfile) return;
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        maxWidth: 512,
        maxHeight: 512,
        imageQuality: 75,
      );
      if (image == null) return;

      setState(() => _isUploadingImage = true);
      final userId = _currentUserId;
      if (userId == null) return;

      final updated = await UserClient.updateProfile(
        userId: userId,
        avatarPath: image.path,
      );

      if (mounted) {
        setState(() {
          _avatarUrl = updated?['avatar_url'] ?? _avatarUrl;
          _isUploadingImage = false;
        });
        if (updated != null) {
          _showSnackBar(_t('Profile photo updated!', 'Foto profil berhasil diperbarui!'), isError: false);
        } else {
          _showSnackBar(_t('Failed to upload photo', 'Gagal mengunggah foto'), isError: true);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isUploadingImage = false);
        _showSnackBar('${_t('Failed to upload photo', 'Gagal mengunggah foto')}: $e', isError: true);
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
                isError ? Icons.error_outline : Icons.check_circle_outline,
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: CustomAppBar(showBackButton: !_isOwnProfile),
      body: _isLoading
          ? _buildLoadingState()
          : FadeTransition(
              opacity: _fadeAnimation,
              child: SlideTransition(
                position: _slideAnimation,
                child: CustomScrollView(
                  physics: const BouncingScrollPhysics(),
                  slivers: [
                    _buildProfileHeader(),
                    _buildProfileContent(),
                  ],
                ),
              ),
            ),
      bottomNavigationBar: CustomBottomNav(
        currentIndex: _isOwnProfile ? 4 : 0,
        avatarUrl: _avatarUrl,
        onRefresh: _loadProfile,
      ),
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
              gradient: LinearGradient(colors: _primaryGradient),
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                    color: _primaryColor.withValues(alpha: 0.4),
                    blurRadius: 20,
                    offset: const Offset(0, 10)),
              ],
            ),
            child: const CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                strokeWidth: 3),
          ),
          const SizedBox(height: 24),
          Text(_t('Loading profile...', 'Memuat profil...'),
              style: TextStyle(
                  color: Colors.grey.shade700,
                  fontSize: 16,
                  fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────
  // Profile Header
  // Admin own profile  → gradient header (style dok. 2)
  // Semua lainnya      → layout polos tanpa gradient (style dok. 1)
  // ─────────────────────────────────────────────────────────────
  Widget _buildProfileHeader() {
    return _buildPlainHeader();
  }

  // ── Header gradient khusus admin own profile ──
  // ignore: unused_element
  Widget _buildAdminGradientHeader() {
    return SliverToBoxAdapter(
      child: Container(
        decoration: BoxDecoration(
          gradient: _headerGradient,
          boxShadow: [
            BoxShadow(
                color: _primaryColor.withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 10)),
          ],
        ),
        child: SafeArea(
          bottom: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 30),
            child: Column(
              children: [
                // Avatar
                _buildAvatar(onGradient: true),
                const SizedBox(height: 16),

                // Username
                Text(
                  _usernameController.text.isEmpty
                      ? 'Unknown'
                      : _usernameController.text,
                  style: AppTheme.headingLarge,
                ),
                if (_fullNameController.text.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(_fullNameController.text,
                      style: TextStyle(
                          fontSize: 14,
                          color: Colors.white.withValues(alpha: 0.9))),
                ],
                const SizedBox(height: 12),

                // Role badge
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: AppTheme.getRoleGradient(_userRole)
                          .map((c) => c.withValues(alpha: 0.3))
                          .toList(),
                    ),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: Colors.white.withValues(alpha: 0.5), width: 1.5),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.admin_panel_settings_rounded,
                          color: Colors.white, size: 16),
                      const SizedBox(width: 8),
                      Text(
                        AppTheme.getRoleLabel(_userRole),
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.5),
                      ),
                    ],
                  ),
                ),

                // Bio
                if (_bioController.text.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                          color: Colors.white.withValues(alpha: 0.3),
                          width: 1.5),
                    ),
                    child: Text(
                      _bioController.text,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.95),
                          fontSize: 14,
                          height: 1.5),
                    ),
                  ),
                ],
                const SizedBox(height: 20),

                // Stats
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildStatItem(_t('Recipes', 'Resep'), _userRecipes.length.toString(),
                        onGradient: true),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.white.withValues(alpha: 0.3)),
                    GestureDetector(
                      onTap: () => _openFollowList(followers: true),
                      child: _buildStatItem(
                          _t('Followers', 'Pengikut'), _followerCount.toString(),
                          onGradient: true),
                    ),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.white.withValues(alpha: 0.3)),
                    GestureDetector(
                      onTap: () => _openFollowList(followers: false),
                      child: _buildStatItem(
                          _t('Following', 'Mengikuti'), _followingCount.toString(),
                          onGradient: true),
                    ),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.white.withValues(alpha: 0.3)),
                    GestureDetector(
                      onTap: _openLikedRecipes,
                      child: _buildStatItem(
                          _t('Likes', 'Like'), _likedRecipesCount.toString(),
                          onGradient: true),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ── Header polos (non-admin / other profile) ──
  Widget _buildPlainHeader() {
    return SliverToBoxAdapter(
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
          child: Column(
            children: [
              // Avatar
              _buildAvatar(onGradient: false),
              const SizedBox(height: 16),

              // Username
              Text(
                _usernameController.text.isEmpty
                    ? 'Unknown'
                    : _usernameController.text,
                style: AppTheme.headingMedium
                    .copyWith(color: AppTheme.textPrimary, fontSize: 22),
              ),
              if (_fullNameController.text.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(_fullNameController.text,
                    style: TextStyle(
                        fontSize: 14, color: AppTheme.textSecondary)),
              ],
              const SizedBox(height: 12),

              // Role badge
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: _primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                      color: _primaryColor.withValues(alpha: 0.35), width: 1.5),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      _userRole == 'admin'
                          ? Icons.admin_panel_settings_rounded
                          : _isPremium
                              ? Icons.workspace_premium_rounded
                              : Icons.person_rounded,
                      color: _primaryColor,
                      size: 16,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      AppTheme.getRoleLabel(_userRole),
                      style: TextStyle(
                          color: _primaryColor,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.5),
                    ),
                  ],
                ),
              ),

              // Bio
              if (_bioController.text.isNotEmpty) ...[
                const SizedBox(height: 14),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  child: Text(
                    _bioController.text,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        color: AppTheme.textSecondary,
                        fontSize: 14,
                        height: 1.5),
                  ),
                ),
              ],
              const SizedBox(height: 20),

              // Stats card
              Container(
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                        color: Colors.black.withValues(alpha: 0.06),
                        blurRadius: 12,
                        offset: const Offset(0, 4)),
                  ],
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildStatItem(_t('Recipes', 'Resep'), _userRecipes.length.toString()),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.grey.shade200),
                    GestureDetector(
                      onTap: () => _openFollowList(followers: true),
                      child: _buildStatItem(
                          _t('Followers', 'Pengikut'), _followerCount.toString()),
                    ),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.grey.shade200),
                    GestureDetector(
                      onTap: () => _openFollowList(followers: false),
                      child: _buildStatItem(
                          _t('Following', 'Mengikuti'), _followingCount.toString()),
                    ),
                    Container(
                        width: 1,
                        height: 40,
                        color: Colors.grey.shade200),
                    GestureDetector(
                      onTap: _openLikedRecipes,
                      child: _buildStatItem(
                          _t('Likes', 'Like'), _likedRecipesCount.toString()),
                    ),
                  ],
                ),
              ),

              // Follow button (other user's profile)
              if (!_isOwnProfile) ...[
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  height: 52,
                  decoration: BoxDecoration(
                    gradient: _isFollowing
                        ? null
                        : LinearGradient(colors: _primaryGradient),
                    color: _isFollowing ? Colors.grey.shade100 : null,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                        color: _isFollowing
                            ? Colors.grey.shade300
                            : Colors.transparent,
                        width: 1.5),
                    boxShadow: _isFollowing
                        ? null
                        : [
                            BoxShadow(
                                color: _primaryColor.withValues(alpha: 0.35),
                                blurRadius: 12,
                                offset: const Offset(0, 6))
                          ],
                  ),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: _isFollowLoading ? null : _toggleFollow,
                      borderRadius: BorderRadius.circular(16),
                      child: Center(
                        child: _isFollowLoading
                            ? SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2.5,
                                    color: _isFollowing
                                        ? _primaryColor
                                        : Colors.white))
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    _isFollowing
                                        ? Icons.person_remove_rounded
                                        : Icons.person_add_rounded,
                                    color: _isFollowing
                                        ? AppTheme.textSecondary
                                        : Colors.white,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    _isFollowing
                                        ? _t('Unfollow', 'Berhenti Mengikuti')
                                        : _t('Follow', 'Ikuti'),
                                    style: _isFollowing
                                        ? TextStyle(
                                            color: AppTheme.textSecondary,
                                            fontSize: 15,
                                            fontWeight: FontWeight.bold)
                                        : AppTheme.buttonText,
                                  ),
                                ],
                              ),
                      ),
                    ),
                  ),
                ),
              ] else ...[
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: OutlinedButton.icon(
                    onPressed: _openEditProfile,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: _primaryColor,
                      side: BorderSide(color: _primaryColor.withValues(alpha: 0.35), width: 1.5),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    icon: const Icon(Icons.edit_rounded),
                    label: Text(_t('Edit Profile', 'Edit Profil'), style: const TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProfileContent() {
    return SliverPadding(
      padding: EdgeInsets.fromLTRB(20, _useGradientHeader ? 20 : 0, 20, 0),
      sliver: SliverList(
        delegate: SliverChildListDelegate([
          // ── Admin Dashboard Button ──
          // ── Recipes Section Header ──
          Row(
            children: [
              Container(
                width: 4,
                height: 24,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                      colors: _primaryGradient,
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  _isOwnProfile ? _t('My Recipes', 'Resep Saya') : _t('Recipes', 'Resep'),
                  style: AppTheme.headingMedium,
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [
                    _primaryColor.withValues(alpha: 0.1),
                    _secondaryColor.withValues(alpha: 0.1),
                  ]),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                      color: _primaryColor.withValues(alpha: 0.3), width: 1),
                ),
                child: Text(
                  '${_userRecipes.length}',
                  style: TextStyle(
                      fontSize: 13,
                      color: _primaryColor,
                      fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          if (_userRecipes.isEmpty)
            AppTheme.buildEmptyState(
              icon: Icons.restaurant_menu_rounded,
              title: _isOwnProfile
                  ? _t('No recipes yet', 'Belum ada resep')
                  : _t('This user has no recipes yet', 'Pengguna ini belum memiliki resep'),
              subtitle: _isOwnProfile
                  ? _t('Start sharing your favorite recipes!', 'Mulai berbagi resep favorit Anda!')
                  : _t('Check back when they share a recipe.', 'Tunggu hingga mereka membagikan kreasi kuliner'),
            )
          else
            ..._userRecipes.map((recipe) {
              return RecipeCard(
                recipe: recipe,
                rating: _recipeRatings[recipe['id']],
                currentUserId: _currentUserId,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) =>
                          DetailScreen(recipeId: recipe['id'].toString()),
                    ),
                  ).then((_) {
                    if (mounted) _loadUserRecipes();
                  });
                },
              );
            }),
          const SizedBox(height: 100),
        ]),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────
  // Reusable widgets
  // ─────────────────────────────────────────────────────────────

  /// Avatar dengan camera button & admin badge.
  /// [onGradient] → true berarti berada di atas background gelap/gradient.
  Widget _buildAvatar({required bool onGradient}) {
    return Stack(
      children: [
        Container(
          width: 110,
          height: 110,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient:
                LinearGradient(colors: AppTheme.getRoleGradient(_userRole)),
            boxShadow: [
              BoxShadow(
                  color: onGradient
                      ? Colors.black.withValues(alpha: 0.3)
                      : _primaryColor.withValues(alpha: 0.3),
                  blurRadius: 20,
                  offset: const Offset(0, 8)),
            ],
          ),
        ),
        Container(
          width: 110,
          height: 110,
          decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(
                  color: onGradient
                      ? Colors.white
                      : _primaryColor.withValues(alpha: 0.4),
                  width: 3)),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(55),
            child: _avatarUrl != null
                ? Image.network(
                    _avatarUrl!,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => _buildDefaultAvatar(),
                  )
                : _buildDefaultAvatar(),
          ),
        ),
        if (_isUploadingImage)
          Positioned.fill(
            child: Container(
              decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.black.withValues(alpha: 0.6)),
              child: const Center(
                  child: CircularProgressIndicator(
                      color: Colors.white, strokeWidth: 3)),
            ),
          ),
        if (_isOwnProfile)
          Positioned(
            bottom: 0,
            right: 0,
            child: GestureDetector(
              onTap: _isUploadingImage ? null : _pickAndUploadImage,
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: _primaryGradient),
                  shape: BoxShape.circle,
                  border: Border.all(
                      color: onGradient
                          ? Colors.white
                          : AppTheme.backgroundLight,
                      width: 3),
                  boxShadow: [
                    BoxShadow(
                        color: Colors.black.withValues(alpha: 0.2),
                        blurRadius: 8)
                  ],
                ),
                child: const Icon(Icons.camera_alt_rounded,
                    color: Colors.white, size: 16),
              ),
            ),
          ),
        if (_userRole == 'admin')
          Positioned(
            top: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                gradient: AppTheme.adminGradient,
                shape: BoxShape.circle,
                border: Border.all(
                    color: onGradient ? Colors.white : AppTheme.backgroundLight,
                    width: 2),
                boxShadow: [
                  BoxShadow(
                      color: Colors.black.withValues(alpha: 0.2),
                      blurRadius: 8)
                ],
              ),
              child: const Icon(Icons.verified_rounded,
                  color: Colors.white, size: 16),
            ),
          ),
      ],
    );
  }

  Widget _buildDefaultAvatar() {
    return Container(
      decoration: BoxDecoration(
          gradient:
              LinearGradient(colors: AppTheme.getRoleGradient(_userRole))),
      child: const Icon(Icons.person_rounded, size: 50, color: Colors.white),
    );
  }

  /// [onGradient] → true = teks putih (di atas gradient admin header)
  Widget _buildStatItem(String label, String value,
      {bool onGradient = false}) {
    return SizedBox(
      width: 64,
      child: Column(
        children: [
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: onGradient ? Colors.white : AppTheme.textPrimary)),
          const SizedBox(height: 4),
          Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontSize: 12,
                  color: onGradient
                      ? Colors.white.withValues(alpha: 0.9)
                      : AppTheme.textSecondary,
                  fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

}
