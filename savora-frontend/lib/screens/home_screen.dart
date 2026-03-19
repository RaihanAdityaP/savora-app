import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/user_client.dart';
import '../widgets/custom_app_bar.dart';
import '../widgets/custom_bottom_nav.dart';
import '../widgets/recipe_card.dart';
import '../widgets/theme.dart';
import 'detail_screen.dart';
import 'login_screen.dart';
import 'create_recipe_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with SingleTickerProviderStateMixin {
  // ── User ──
  String? _avatarUrl;
  String? _username;
  String? _currentUserId;
  int _myRecipesCount = 0;
  int _bookmarksCount = 0;
  int _followersCount = 0;

  // ── Feed ──
  final List<Map<String, dynamic>> _feed          = [];
  final Map<String, double>        _recipeRatings = {};
  bool _isLoading     = true;
  bool _isLoadingMore = false;
  bool _hasMore       = true;
  int  _currentOffset = 0;
  static const int _pageSize = 10;

  // ── Scroll ──
  final ScrollController _scrollController = ScrollController();

  // ── Animation ──
  late AnimationController _animController;
  late Animation<double>   _fadeAnim;
  late Animation<Offset>   _slideAnim;

  // ── Quotes ──
  final List<Map<String, String>> _quotes = [
    {
      'q': 'People who love to eat are always the best people.',
      'a': 'Julia Child',
    },
    {
      'q': 'Cooking is like love. It should be entered into with abandon.',
      'a': 'Harriet Van Horne',
    },
    {
      'q': 'I think food is, actually, very beautiful in itself.',
      'a': 'Delia Smith',
    },
    {
      'q': 'Learn how to cook—try new recipes, be fearless and have fun!',
      'a': 'Julia Child',
    },
    {
      'q': 'Cooking is one of the strongest ceremonies for life.',
      'a': 'Laura Esquivel',
    },
    {
      'q': "Food is everything we are. It's an extension of personal history.",
      'a': 'Anthony Bourdain',
    },
  ];

  // ────────────────────────────────────────────────────────────
  @override
  void initState() {
    super.initState();

    _animController = AnimationController(
      duration: const Duration(milliseconds: 900),
      vsync: this,
    );
    _fadeAnim = CurvedAnimation(
      parent: _animController,
      curve: Curves.easeInOut,
    );
    _slideAnim =
        Tween<Offset>(begin: const Offset(0, 0.18), end: Offset.zero).animate(
      CurvedAnimation(
        parent: _animController,
        curve: Curves.easeOutCubic,
      ),
    );

    _loadUserData();
    _loadFeed(refresh: true);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _animController.dispose();
    super.dispose();
  }

  // ── User data ────────────────────────────────────────────────
  Future<void> _loadUserData() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) {
        if (mounted) {
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const LoginScreen()),
            (route) => false,
          );
        }
        return;
      }
      _currentUserId = userId;

      final profile = await UserClient.getProfile(userId);
      if (mounted && profile != null) {
        setState(() {
          _avatarUrl      = profile['avatar_url'];
          _username       = profile['username'];
          _myRecipesCount = profile['total_recipes']  ?? 0;
          _bookmarksCount = profile['total_bookmarks'] ?? 0;
          _followersCount = profile['total_followers'] ?? 0;
        });
      }
    } catch (e) {
      debugPrint('Error loading user data: $e');
    }
  }

  Future<void> _loadUserStats() async {
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) return;
      final profile = await UserClient.getProfile(userId);
      if (mounted && profile != null) {
        setState(() {
          _myRecipesCount = profile['total_recipes']  ?? 0;
          _bookmarksCount = profile['total_bookmarks'] ?? 0;
          _followersCount = profile['total_followers'] ?? 0;
        });
      }
    } catch (e) {
      debugPrint('Error loading user stats: $e');
    }
  }

  // ── Feed ─────────────────────────────────────────────────────
  Future<void> _loadFeed({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _isLoading     = true;
        _currentOffset = 0;
        _hasMore       = true;
        _feed.clear();
        _recipeRatings.clear();
      });
    }

    try {
      final result = await _fetchPage(0);
      if (mounted) {
        setState(() {
          _feed.addAll(result.recipes);
          _recipeRatings.addAll(result.ratings);
          _currentOffset = result.recipes.length;
          _hasMore       = result.hasMore;
          _isLoading     = false;
        });
        _animController.forward(from: 0);
      }
    } catch (e) {
      debugPrint('Error loading feed: $e');
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadMore() async {
    if (_isLoadingMore || !_hasMore) return;
    setState(() => _isLoadingMore = true);

    try {
      final result = await _fetchPage(_currentOffset);
      if (mounted) {
        setState(() {
          _feed.addAll(result.recipes);
          _recipeRatings.addAll(result.ratings);
          _currentOffset += result.recipes.length;
          _hasMore        = result.hasMore;
          _isLoadingMore  = false;
        });
      }
    } catch (e) {
      debugPrint('Error loading more: $e');
      if (mounted) setState(() => _isLoadingMore = false);
    }
  }

  Future<_FeedPage> _fetchPage(int offset) async {
    final response = await ApiService.get(
      '/feed?limit=$_pageSize&offset=$offset',
    );

    if (response['success'] != true) {
      throw Exception(response['message'] ?? 'Gagal memuat feed');
    }

    final list = (response['data'] as List? ?? [])
        .map((e) => Map<String, dynamic>.from(e))
        .toList();

    final Map<String, double> ratings = {};
    for (final r in list) {
      final ri = r['rating_info'];
      if (ri != null) {
        final avg = ri['average'];
        if (avg != null) {
          ratings[r['id'].toString()] = (avg as num).toDouble();
        }
      }
    }

    final pagination = response['pagination'] as Map?;
    final hasMore    = pagination?['has_more'] ?? (list.length == _pageSize);

    return _FeedPage(
      recipes: list,
      ratings: ratings,
      hasMore: hasMore as bool,
    );
  }

  // ── Quote helpers ─────────────────────────────────────────────
  String _quote() {
    final day =
        DateTime.now().difference(DateTime(DateTime.now().year, 1, 1)).inDays;
    return _quotes[day % _quotes.length]['q']!;
  }

  String _quoteAuthor() {
    final day =
        DateTime.now().difference(DateTime(DateTime.now().year, 1, 1)).inDays;
    return _quotes[day % _quotes.length]['a']!;
  }

  // ────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: CustomAppBar(userId: _currentUserId),
      body: _isLoading
          ? _buildLoadingState()
          : RefreshIndicator(
              onRefresh: () => _loadFeed(refresh: true),
              color: AppTheme.primaryCoral,
              child: _feed.isEmpty
                  ? _buildEmptyState()
                  : _buildContent(),
            ),
      bottomNavigationBar: CustomBottomNav(
        currentIndex: 0,
        avatarUrl: _avatarUrl,
        onRefresh: () {
          _loadUserStats();
          _loadFeed(refresh: true);
        },
      ),
    );
  }

  // ── Loading ──────────────────────────────────────────────────
  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              gradient: AppTheme.cardGradient,
              shape: BoxShape.circle,
            ),
            child: const Center(
              child: CircularProgressIndicator(
                valueColor:
                    AlwaysStoppedAnimation<Color>(AppTheme.primaryCoral),
                strokeWidth: 3,
              ),
            ),
          ),
          const SizedBox(height: 20),
          Text(
            'Menyiapkan feed untukmu...',
            style: AppTheme.bodyLarge.copyWith(color: AppTheme.textSecondary),
          ),
        ],
      ),
    );
  }

  // ── Main content ─────────────────────────────────────────────
  Widget _buildContent() {
    return FadeTransition(
      opacity: _fadeAnim,
      child: CustomScrollView(
        controller: _scrollController,
        physics: const BouncingScrollPhysics(),
        slivers: [
          // Welcome card
          SliverToBoxAdapter(
            child: SlideTransition(
              position: _slideAnim,
              child: _buildWelcomeCard(),
            ),
          ),

          // Section header
          SliverToBoxAdapter(child: _buildSectionHeader()),

          // Feed list
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  final recipe = _feed[index];
                  return RecipeCard(
                    recipe: recipe,
                    rating: _recipeRatings[recipe['id'].toString()],
                    currentUserId: _currentUserId,
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            DetailScreen(recipeId: recipe['id'].toString()),
                      ),
                    ).then((_) => _loadUserStats()),
                  );
                },
                childCount: _feed.length,
              ),
            ),
          ),

          // Load More / End indicator
          SliverToBoxAdapter(child: _buildLoadMoreSection()),

          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
  }

  // ── Section header ───────────────────────────────────────────
  Widget _buildSectionHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
      child: Row(
        children: [
          // Left accent bar
          Container(
            width: 4,
            height: 24,
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 12),

          // Title — Expanded agar tidak overflow
          const Expanded(
            child: Text('Untuk Kamu', style: AppTheme.headingMedium),
          ),

          // FYP badge — teks saja, tanpa icon
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primaryCoral.withValues(alpha: 0.35),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: const Text(
              'FYP',
              style: TextStyle(
                fontSize: 11,
                color: Colors.white,
                fontWeight: FontWeight.bold,
                letterSpacing: 0.5,
              ),
            ),
          ),
          const SizedBox(width: 8),

          // Recipe count badge
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              gradient: AppTheme.cardGradient,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: AppTheme.primaryCoral.withValues(alpha: 0.3),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.restaurant_rounded,
                    size: 12, color: AppTheme.primaryCoral),
                const SizedBox(width: 4),
                Text(
                  '${_feed.length}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppTheme.primaryCoral,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── Load More section ────────────────────────────────────────
  Widget _buildLoadMoreSection() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
      child: _hasMore ? _buildLoadMoreButton() : _buildEndIndicator(),
    );
  }

  Widget _buildLoadMoreButton() {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 250),
      child: _isLoadingMore
          // ── Loading state ──
          ? Container(
              key: const ValueKey('loading'),
              height: 54,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(
                          AppTheme.primaryCoral),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Memuat resep lainnya...',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey.shade600,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            )
          // ── Button state ──
          : GestureDetector(
              key: const ValueKey('button'),
              onTap: _loadMore,
              child: Container(
                height: 54,
                decoration: BoxDecoration(
                  gradient: AppTheme.accentGradient,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color:
                          AppTheme.primaryCoral.withValues(alpha: 0.35),
                      blurRadius: 12,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.expand_more_rounded,
                        color: Colors.white, size: 22),
                    SizedBox(width: 8),
                    Text(
                      'Muat Lebih Banyak',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  // ── End indicator — fix overflow ──────────────────────────────
  Widget _buildEndIndicator() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.check_circle_outline_rounded,
            size: 16,
            color: Colors.grey.shade400,
          ),
          const SizedBox(width: 8),
          // Flexible mencegah overflow
          Flexible(
            child: Text(
              'Kamu sudah melihat semua resep untukmu',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
              textAlign: TextAlign.center,
              overflow: TextOverflow.ellipsis,
              maxLines: 2,
            ),
          ),
        ],
      ),
    );
  }

  // ── Welcome card ─────────────────────────────────────────────
  Widget _buildWelcomeCard() {
    return Container(
      margin: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppTheme.primaryCoral,
            AppTheme.primaryOrange,
            AppTheme.primaryYellow,
          ],
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryCoral.withValues(alpha: 0.4),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Stack(
        children: [
          // Decorative circles
          Positioned(
            top: -40, right: -40,
            child: _decorCircle(120, 0.1),
          ),
          Positioned(
            bottom: -60, left: -60,
            child: _decorCircle(150, 0.08),
          ),

          Column(
            children: [
              // Greeting + stats
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 24, 24, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.25),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.4),
                              width: 2,
                            ),
                          ),
                          child: const Icon(
                            Icons.waving_hand_rounded,
                            color: Colors.white,
                            size: 28,
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Halo, ${_username ?? 'Foodie'}!',
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                  letterSpacing: 0.3,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Selamat datang kembali di Savora',
                                style: TextStyle(
                                  fontSize: 14,
                                  color:
                                      Colors.white.withValues(alpha: 0.9),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: _statChip(
                            Icons.restaurant_rounded,
                            _myRecipesCount.toString(),
                            'Resep Saya',
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _statChip(
                            Icons.bookmark_rounded,
                            _bookmarksCount.toString(),
                            'Tersimpan',
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _statChip(
                            Icons.people_rounded,
                            _followersCount.toString(),
                            'Pengikut',
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Daily quote
              Container(
                margin: const EdgeInsets.fromLTRB(24, 0, 24, 24),
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.4),
                    width: 2,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color:
                                Colors.white.withValues(alpha: 0.25),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(
                            Icons.format_quote_rounded,
                            color: Colors.white,
                            size: 18,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Text(
                          'Inspirasi Hari Ini',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color:
                                Colors.white.withValues(alpha: 0.95),
                            letterSpacing: 0.5,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _quote(),
                      style: const TextStyle(
                        fontSize: 15,
                        fontStyle: FontStyle.italic,
                        color: Colors.white,
                        height: 1.5,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Container(
                          width: 3,
                          height: 16,
                          decoration: BoxDecoration(
                            color:
                                Colors.white.withValues(alpha: 0.6),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _quoteAuthor(),
                            style: TextStyle(
                              fontSize: 13,
                              color:
                                  Colors.white.withValues(alpha: 0.9),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _decorCircle(double size, double opacity) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white.withValues(alpha: opacity),
      ),
    );
  }

  Widget _statChip(IconData icon, String value, String label) {
    return Container(
      padding:
          const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.25),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.4),
          width: 2,
        ),
      ),
      child: Column(
        children: [
          Icon(icon, color: Colors.white, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
              height: 1,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.95),
              fontWeight: FontWeight.w600,
            ),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  // ── Empty state ──────────────────────────────────────────────
  Widget _buildEmptyState() {
    return Center(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding:
            const EdgeInsets.symmetric(horizontal: 32, vertical: 60),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TweenAnimationBuilder<double>(
              duration: const Duration(milliseconds: 1200),
              tween: Tween(begin: 0.0, end: 1.0),
              curve: Curves.elasticOut,
              builder: (_, v, _) => Transform.scale(
                scale: v,
                child: Container(
                  width: 140,
                  height: 140,
                  decoration: BoxDecoration(
                    gradient: AppTheme.cardGradient,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.restaurant_menu_rounded,
                    size: 70,
                    color: AppTheme.primaryCoral,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 32),
            Text(
              'Belum Ada Resep',
              style: TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.bold,
                color: Colors.grey.shade900,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              'Jadilah yang pertama membagikan\nresep lezat dan inspirasi kuliner!',
              style: AppTheme.bodyLarge.copyWith(
                color: AppTheme.textSecondary,
                height: 1.6,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 40),
            Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const CreateRecipeScreen(),
                  ),
                ),
                borderRadius: BorderRadius.circular(16),
                child: Ink(
                  decoration: AppTheme.primaryButtonDecoration,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 32,
                      vertical: 16,
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.add_circle_rounded,
                            color: Colors.white, size: 24),
                        SizedBox(width: 12),
                        Text(
                          'Buat Resep Pertama',
                          style: AppTheme.buttonText,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Data class ───────────────────────────────────────────────────────────────
class _FeedPage {
  final List<Map<String, dynamic>> recipes;
  final Map<String, double>        ratings;
  final bool                       hasMore;

  const _FeedPage({
    required this.recipes,
    required this.ratings,
    required this.hasMore,
  });
}