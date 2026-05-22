  import 'package:flutter/material.dart';
  import '../screens/home_screen.dart';
  import '../screens/searching_screen.dart';
  import '../screens/recipes/create_screen.dart';
  import '../screens/favorites/favorites_screen.dart';
  import '../screens/profile_screen.dart';
  import '../services/app_settings_service.dart';
  import '../widgets/theme.dart';

  class CustomBottomNav extends StatefulWidget {
    final int currentIndex;
    final String? avatarUrl;
    final VoidCallback? onRefresh;

    const CustomBottomNav({
      super.key,
      required this.currentIndex,
      this.avatarUrl,
      this.onRefresh,
    });

    @override
    State<CustomBottomNav> createState() => _CustomBottomNavState();
  }

  class _CustomBottomNavState extends State<CustomBottomNav> with TickerProviderStateMixin {
    late List<AnimationController> _controllers;
    late List<Animation<double>> _scaleAnimations;

    @override
    void initState() {
      super.initState();
      _controllers = List.generate(
        5,
        (index) => AnimationController(
          duration: const Duration(milliseconds: 200),
          vsync: this,
        ),
      );

      _scaleAnimations = _controllers
          .map((controller) => Tween<double>(begin: 1.0, end: 0.92).animate(
                CurvedAnimation(parent: controller, curve: Curves.easeInOut),
              ))
          .toList();
    }

    @override
    void dispose() {
      for (var controller in _controllers) {
        controller.dispose();
      }
      super.dispose();
    }

    @override
    Widget build(BuildContext context) {
      final isEnglish = AppSettingsService.isEnglish;
      return Container(
        decoration: BoxDecoration(
          color: AppTheme.surfaceColor,
          boxShadow: [
            BoxShadow(
              color: AppTheme.isDarkMode
                  ? Colors.black.withValues(alpha: 0.35)
                  : AppTheme.primaryCoral.withValues(alpha: 0.08),
              blurRadius: 20,
              offset: const Offset(0, -4),
            ),
          ],
          border: Border(
            top: BorderSide(
              color: AppTheme.borderColor,
              width: 1,
            ),
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(8, 10, 8, 6),
            child: Row(
              children: [
                _buildNavItem(
                  index: 0,
                  icon: Icons.home_rounded,
                  label: 'Home',
                ),
                _buildNavItem(
                  index: 1,
                  icon: Icons.search_rounded,
                  label: isEnglish ? 'Search' : 'Cari',
                ),
                _buildCenterButton(),
                _buildNavItem(
                  index: 3,
                  icon: Icons.bookmark_rounded,
                  label: isEnglish ? 'Saved' : 'Simpan',
                ),
                _buildProfileButton(),
              ],
            ),
          ),
        ),
      );
    }

    Widget _buildNavItem({
      required int index,
      required IconData icon,
      required String label,
    }) {
      final isActive = widget.currentIndex == index;
      final activeColor = AppTheme.primaryCoral;
      final inactiveColor = AppTheme.textSecondary;

      return Expanded(
        child: GestureDetector(
          onTapDown: (_) => _controllers[index].forward(),
          onTapUp: (_) {
            _controllers[index].reverse();
            _navigateTo(context, index);
          },
          onTapCancel: () => _controllers[index].reverse(),
          child: ScaleTransition(
            scale: _scaleAnimations[index],
            child: Container(
              padding: const EdgeInsets.only(top: 8, bottom: 6),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox(
                    height: 28,
                    child: Center(
                      child: Icon(
                        icon,
                        color: isActive ? activeColor : inactiveColor,
                        size: isActive ? 26 : 24,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Container(
                    height: 3,
                    width: isActive ? 20 : 0,
                    decoration: BoxDecoration(
                      color: activeColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    label,
                    style: TextStyle(
                      color: isActive ? activeColor : inactiveColor,
                      fontSize: 11,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    Widget _buildCenterButton() {
      return GestureDetector(
        onTapDown: (_) => _controllers[2].forward(),
        onTapUp: (_) {
          _controllers[2].reverse();
          _navigateTo(context, 2);
        },
        onTapCancel: () => _controllers[2].reverse(),
        child: ScaleTransition(
          scale: _scaleAnimations[2],
          child: Container(
            width: 56,
            height: 56,
            margin: const EdgeInsets.symmetric(horizontal: 8),
            decoration: BoxDecoration(
              gradient: AppTheme.accentGradient,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primaryCoral.withValues(alpha: 0.35),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: const Icon(
              Icons.add_rounded,
              color: Colors.white,
              size: 28,
            ),
          ),
        ),
      );
    }

    Widget _buildProfileButton() {
      final isActive = widget.currentIndex == 4;
      final activeColor = AppTheme.primaryCoral;
      final inactiveColor = AppTheme.textSecondary;

      return Expanded(
        child: GestureDetector(
          onTapDown: (_) => _controllers[4].forward(),
          onTapUp: (_) {
            _controllers[4].reverse();
            _navigateTo(context, 4);
          },
          onTapCancel: () => _controllers[4].reverse(),
          child: ScaleTransition(
            scale: _scaleAnimations[4],
            child: Container(
              padding: const EdgeInsets.only(top: 8, bottom: 6),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isActive 
                            ? activeColor
                            : inactiveColor.withValues(alpha: 0.35),
                        width: isActive ? 2.2 : 1.8,
                      ),
                    ),
                    child: Container(
                      margin: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: AppTheme.subtleSurfaceColor,
                      ),
                      child: ClipOval(
                        child: widget.avatarUrl != null
                            ? Image.network(
                                widget.avatarUrl!,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) => Icon(
                                  Icons.person_rounded,
                                  color: inactiveColor,
                                  size: 14,
                                ),
                              )
                            : Icon(
                                Icons.person_rounded,
                                color: inactiveColor,
                                size: 14,
                              ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Container(
                    height: 3,
                    width: isActive ? 20 : 0,
                    decoration: BoxDecoration(
                      color: activeColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                  AppSettingsService.isEnglish ? 'Profile' : 'Profil',
                    style: TextStyle(
                      color: isActive ? activeColor : inactiveColor,
                      fontSize: 11,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    void _navigateTo(BuildContext context, int index) {
      if (index == widget.currentIndex && index != 2) return;

      Widget destination;
      switch (index) {
        case 0:
          destination = const HomeScreen();
          break;
        case 1:
          destination = const SearchingScreen();
          break;
        case 2:
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const CreateRecipeScreen()),
          ).then((_) {
            if (widget.onRefresh != null) widget.onRefresh!();
          });
          return;
        case 3:
          destination = const FavoritesScreen();
          break;
        case 4:
          destination = const ProfileScreen();
          break;
        default:
          return;
      }

      Navigator.pushReplacement(
        context,
        PageRouteBuilder(
          pageBuilder: (context, animation, secondaryAnimation) => destination,
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            var fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
              CurvedAnimation(
                parent: animation,
                curve: Curves.easeInOut,
              ),
            );

            return FadeTransition(
              opacity: fadeAnimation,
              child: child,
            );
          },
          transitionDuration: const Duration(milliseconds: 250),
        ),
      );
    }
  }
