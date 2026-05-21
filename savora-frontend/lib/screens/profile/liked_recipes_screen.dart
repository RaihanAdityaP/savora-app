import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/app_settings_service.dart';
import '../../services/user_client.dart';
import '../../widgets/recipe_card.dart';
import '../../widgets/theme.dart';
import '../recipes/detail_screen.dart';

class LikedRecipesScreen extends StatefulWidget {
  final String userId;
  final String? username;

  const LikedRecipesScreen({
    super.key,
    required this.userId,
    this.username,
  });

  @override
  State<LikedRecipesScreen> createState() => _LikedRecipesScreenState();
}

class _LikedRecipesScreenState extends State<LikedRecipesScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _recipes = [];
  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _loadLikedRecipes();
  }

  Future<void> _loadLikedRecipes() async {
    setState(() => _isLoading = true);
    final recipes = await UserClient.getLikedRecipes(widget.userId, limit: 50);
    if (!mounted) return;
    setState(() {
      _recipes = recipes;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: AppTheme.textPrimary,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(_t('Liked Recipes', 'Resep Disukai'), style: const TextStyle(fontWeight: FontWeight.bold)),
            if (widget.username != null)
              Text(
                widget.username!,
                style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
              ),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadLikedRecipes,
        color: AppTheme.primaryCoral,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryCoral))
            : _recipes.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(20),
                    children: [
                      const SizedBox(height: 120),
                      AppTheme.buildEmptyState(
                        icon: Icons.favorite_border_rounded,
                        title: _t('No liked recipes yet', 'Belum ada resep disukai'),
                        subtitle: _t('Liked recipes will appear here.', 'Resep yang disukai akan muncul di sini.'),
                      ),
                    ],
                  )
                : ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                    itemCount: _recipes.length,
                    itemBuilder: (context, index) {
                      final recipe = _recipes[index];
                      return RecipeCard(
                        recipe: recipe,
                        currentUserId: ApiService.currentUserId,
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => DetailScreen(recipeId: recipe['id'].toString()),
                            ),
                          ).then((_) => _loadLikedRecipes());
                        },
                      );
                    },
                  ),
      ),
    );
  }
}
