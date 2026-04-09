// ignore_for_file: prefer_final_fields, use_build_context_synchronously
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:typed_data';
import 'dart:io' show File;
import 'dart:async';
import '../services/api_service.dart';
import '../services/category_client.dart';
import '../services/draft_service.dart';
import '../widgets/custom_bottom_nav.dart';
import '../widgets/theme.dart';
import 'tag_management_screen.dart';

class CreateRecipeScreen extends StatefulWidget {
  const CreateRecipeScreen({super.key});

  @override
  State<CreateRecipeScreen> createState() => _CreateRecipeScreenState();
}

class _CreateRecipeScreenState extends State<CreateRecipeScreen> {
  final _titleController       = TextEditingController();
  final _descriptionController = TextEditingController();
  final _cookingTimeController = TextEditingController();
  final _servingsController    = TextEditingController();
  final _caloriesController    = TextEditingController();

  final List<String> _ingredients = [];
  final List<String> _steps       = [];
  File?       _imageFile;
  Uint8List?  _webImageBytes;
  File?       _videoFile;
  Uint8List?  _webVideoBytes;
  String?     _videoFileName;
  int?        _selectedCategoryId;
  String      _selectedDifficulty = 'mudah';
  bool        _isLoading   = false;
  bool        _isUploading = false;
  String?     _userAvatarUrl;

  List<String>              _selectedTags     = [];
  final _tagInputController = TextEditingController();
  List<Map<String, dynamic>> _popularTags     = [];
  List<Map<String, dynamic>> _userCreatedTags = [];
  bool   _isSearchingTags = false;
  Timer? _debounce;

  List<Map<String, dynamic>> _categories = [];
  final ImagePicker _picker               = ImagePicker();
  final _tempIngredientController         = TextEditingController();
  final _tempStepController               = TextEditingController();

  // ── Draft auto-save timer ──
  Timer? _draftTimer;

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadUserAvatar();
    _loadPopularTags();
    _restoreDraft();          // ← restore draft on open

    // Listen changes → auto-save draft
    _titleController.addListener(_scheduleDraftSave);
    _descriptionController.addListener(_scheduleDraftSave);
    _cookingTimeController.addListener(_scheduleDraftSave);
    _servingsController.addListener(_scheduleDraftSave);
    _caloriesController.addListener(_scheduleDraftSave);
  }

  @override
  void dispose() {
    _draftTimer?.cancel();
    _titleController.dispose();
    _descriptionController.dispose();
    _tempIngredientController.dispose();
    _tempStepController.dispose();
    _cookingTimeController.dispose();
    _servingsController.dispose();
    _caloriesController.dispose();
    _tagInputController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  // ─────────────────────────────────────────────
  // DRAFT — SAVE & RESTORE
  // ─────────────────────────────────────────────

  /// Jadwalkan auto-save 1.5 detik setelah input berhenti
  void _scheduleDraftSave() {
    _draftTimer?.cancel();
    _draftTimer = Timer(const Duration(milliseconds: 1500), _saveDraft);
  }

  Future<void> _saveDraft() async {
    // Jangan simpan kalau form masih kosong total
    if (_titleController.text.isEmpty &&
        _descriptionController.text.isEmpty &&
        _ingredients.isEmpty &&
        _steps.isEmpty) {
      return;
    }

    await DraftService.saveCreateDraft({
      'title'       : _titleController.text,
      'description' : _descriptionController.text,
      'cooking_time': _cookingTimeController.text,
      'servings'    : _servingsController.text,
      'calories'    : _caloriesController.text,
      'ingredients' : _ingredients,
      'steps'       : _steps,
      'tags'        : _selectedTags,
      'category_id' : _selectedCategoryId,
      'difficulty'  : _selectedDifficulty,
    });
  }

  Future<void> _restoreDraft() async {
    final draft = await DraftService.loadCreateDraft();
    if (draft == null || !mounted) return;

    // Tanya user apakah mau restore
    final restore = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.restore_page_rounded, color: Colors.white),
            ),
            const SizedBox(width: 12),
            const Text('Draft Ditemukan'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Ada draft resep yang belum selesai:',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 8),
            if ((draft['title'] as String?)?.isNotEmpty == true)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: AppTheme.primaryCoral.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '"${draft['title']}"',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primaryCoral,
                  ),
                ),
              ),
            const SizedBox(height: 8),
            if (draft['saved_at'] != null)
              Text(
                'Disimpan: ${_formatDraftTime(draft['saved_at'])}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
              ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Mulai Baru'),
          ),
          ElevatedButton.icon(
            onPressed: () => Navigator.pop(context, true),
            icon: const Icon(Icons.restore_rounded, size: 18),
            label: const Text('Lanjutkan'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryCoral,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ],
      ),
    );

    if (restore == true && mounted) {
      setState(() {
        _titleController.text       = draft['title']        ?? '';
        _descriptionController.text = draft['description']  ?? '';
        _cookingTimeController.text = draft['cooking_time'] ?? '';
        _servingsController.text    = draft['servings']     ?? '';
        _caloriesController.text    = draft['calories']     ?? '';
        _ingredients
          ..clear()
          ..addAll(List<String>.from(draft['ingredients'] ?? []));
        _steps
          ..clear()
          ..addAll(List<String>.from(draft['steps'] ?? []));
        _selectedTags     = List<String>.from(draft['tags'] ?? []);
        _selectedCategoryId = draft['category_id'] as int?;
        _selectedDifficulty = draft['difficulty'] ?? 'mudah';
      });
    } else if (restore == false) {
      // User pilih mulai baru → hapus draft lama
      await DraftService.clearCreateDraft();
    }
  }

  String _formatDraftTime(String? isoString) {
  if (isoString == null) return '';
  try {
    final dt   = DateTime.parse(isoString).toLocal();
    final now  = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inMinutes < 1) { return 'Baru saja'; }
    if (diff.inHours < 1)   { return '${diff.inMinutes} menit lalu'; }
    if (diff.inDays < 1)    { return '${diff.inHours} jam lalu'; }
    return '${diff.inDays} hari lalu';
  } catch (_) {
    return isoString;
  }
}

  // ─────────────────────────────────────────────
  // EXISTING METHODS (tidak berubah)
  // ─────────────────────────────────────────────

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

  Future<void> _loadCategories() async {
    try {
      final categories = await CategoryClient.getCategories();
      if (mounted) setState(() => _categories = categories);
    } catch (e) {
      debugPrint('Error loading categories: $e');
    }
  }

  Future<void> _loadPopularTags() async {
    try {
      final response = await ApiService.get('/tags/popular?limit=15');
      if (mounted && response['success'] == true) {
        final list = response['data'] as List;
        setState(() =>
            _popularTags = list.map((e) => Map<String, dynamic>.from(e)).toList());
      }
    } catch (e) {
      debugPrint('Error loading popular tags: $e');
    }
  }

  Future<void> _searchTags(String query) async {
    if (query.isEmpty) {
      setState(() { _isSearchingTags = false; _userCreatedTags.clear(); });
      return;
    }
    setState(() => _isSearchingTags = true);
    try {
      final response = await ApiService.get(
          '/tags/search?q=${Uri.encodeComponent(query)}&limit=10');
      if (mounted && response['success'] == true) {
        final list = response['data'] as List;
        setState(() =>
            _userCreatedTags = list.map((e) => Map<String, dynamic>.from(e)).toList());
      }
    } catch (e) {
      debugPrint('Error searching tags: $e');
    } finally {
      if (mounted) setState(() => _isSearchingTags = false);
    }
  }

  void _addTag(String tagName) {
    if (_selectedTags.length >= 10) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Maksimal 10 tag')));
      return;
    }
    if (_selectedTags.contains(tagName)) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tag sudah ditambahkan')));
      return;
    }
    setState(() {
      _selectedTags.add(tagName);
      _tagInputController.clear();
      _userCreatedTags.clear();
      _isSearchingTags = false;
    });
    FocusScope.of(context).unfocus();
    _scheduleDraftSave();
  }

  void _removeTag(int index) {
    setState(() => _selectedTags.removeAt(index));
    _scheduleDraftSave();
  }

  Future<void> _pickImage() async {
    if (kIsWeb) {
      await _pickImageFromGallery();
      return;
    }

    if (!mounted) return;
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: const Text('Ambil dari Kamera'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: const Text('Pilih dari Galeri'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );

    if (source == null) return;
    if (source == ImageSource.camera) {
      await _pickImageFromCamera();
      return;
    }
    await _pickImageFromGallery();
  }

  Future<void> _pickImageFromGallery() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery, maxWidth: 800, maxHeight: 800, imageQuality: 80);
      if (image != null) {
        if (kIsWeb) {
          final bytes = await image.readAsBytes();
          if (mounted) setState(() => _webImageBytes = bytes);
        } else {
          if (mounted) setState(() => _imageFile = File(image.path));
        }
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  Future<void> _pickImageFromCamera() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.camera,
        maxWidth: 800,
        maxHeight: 800,
        imageQuality: 80,
      );
      if (image != null && mounted) {
        setState(() => _imageFile = File(image.path));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal membuka kamera: $e')),
        );
      }
    }
  }

  Future<void> _openTagManagementPage() async {
    final selected = await Navigator.push<String>(
      context,
      MaterialPageRoute(builder: (_) => const TagManagementScreen()),
    );
    if (selected != null && selected.trim().isNotEmpty) {
      _addTag(selected.trim());
    }
  }

  Future<void> _pickVideo() async {
    try {
      final XFile? video = await _picker.pickVideo(
        source: ImageSource.gallery, maxDuration: const Duration(minutes: 5));
      if (video != null) {
        final fileSize = await video.length();
        if (fileSize > 50 * 1024 * 1024) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Video terlalu besar! Maksimal 50MB')));
          }
          return;
        }
        if (kIsWeb) {
          final bytes = await video.readAsBytes();
          if (mounted) setState(() { _webVideoBytes = bytes; _videoFileName = video.name; });
        } else {
          if (mounted) setState(() { _videoFile = File(video.path); _videoFileName = video.name; });
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error memilih video: $e')));
      }
    }
  }

  void _removeVideo() => setState(() {
    _videoFile = null; _webVideoBytes = null; _videoFileName = null;
  });

  void _addIngredient() {
    if (_tempIngredientController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Bahan tidak boleh kosong')));
      return;
    }
    setState(() {
      _ingredients.add(_tempIngredientController.text.trim());
      _tempIngredientController.clear();
    });
    _scheduleDraftSave();
  }

  void _addStep() {
    if (_tempStepController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Langkah tidak boleh kosong')));
      return;
    }
    setState(() {
      _steps.add(_tempStepController.text.trim());
      _tempStepController.clear();
    });
    _scheduleDraftSave();
  }

  void _removeIngredient(int index) {
    setState(() => _ingredients.removeAt(index));
    _scheduleDraftSave();
  }
  void _removeStep(int index) {
    setState(() => _steps.removeAt(index));
    _scheduleDraftSave();
  }

  Future<void> _submitRecipe() async {
    if (_titleController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Judul resep harus diisi')));
      return;
    }
    if (_selectedCategoryId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pilih kategori terlebih dahulu')));
      return;
    }
    if (_imageFile == null && _webImageBytes == null) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pilih gambar resep terlebih dahulu')));
      return;
    }
    if (_ingredients.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tambahkan minimal 1 bahan')));
      return;
    }
    if (_steps.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tambahkan minimal 1 langkah')));
      return;
    }

    int? calories;
    if (_caloriesController.text.trim().isNotEmpty) {
      final parsed = int.tryParse(_caloriesController.text.trim());
      if (parsed == null || parsed < 0) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Kalori harus berupa angka positif')));
        return;
      }
      calories = parsed;
    }

    setState(() => _isUploading = true);
    try {
      final userId = ApiService.currentUserId;
      if (userId == null) throw Exception('User not authenticated');

      String? imagePath;
      if (!kIsWeb && _imageFile != null) imagePath = _imageFile!.path;

      String? videoPath;
      if (!kIsWeb && _videoFile != null) videoPath = _videoFile!.path;

      final fields = <String, String>{
        'user_id'    : userId,
        'title'      : _titleController.text.trim(),
        'description': _descriptionController.text.trim(),
        'category_id': _selectedCategoryId.toString(),
        'cooking_time': (int.tryParse(_cookingTimeController.text) ?? 0).toString(),
        'servings'   : (int.tryParse(_servingsController.text) ?? 1).toString(),
        'difficulty' : _selectedDifficulty,
        if (calories != null) 'calories': calories.toString(),
      };

      Map<String, dynamic> jsonBody = {
        'user_id'    : userId,
        'title'      : _titleController.text.trim(),
        'description': _descriptionController.text.trim(),
        'category_id': _selectedCategoryId,
        'cooking_time': int.tryParse(_cookingTimeController.text) ?? 0,
        'servings'   : int.tryParse(_servingsController.text) ?? 1,
        'difficulty' : _selectedDifficulty,
        'ingredients': _ingredients,
        'steps'      : _steps,
        if (calories != null) 'calories': calories,
        if (_selectedTags.isNotEmpty) 'tags': _selectedTags,
      };

      Map<String, dynamic> response;
      if (imagePath != null) {
        response = await ApiService.uploadImage(
          '/recipes', imagePath,
          fields: {
            ...fields,
            'ingredients': _ingredients.join('||'),
            'steps'      : _steps.join('||'),
            if (_selectedTags.isNotEmpty) 'tags': _selectedTags.join(','),
          },
        );
      } else {
        response = await ApiService.post('/recipes', jsonBody);
      }

      if (response['success'] == true && videoPath != null) {
        final recipeId = response['data']?['id'];
        if (recipeId != null) {
          await ApiService.uploadImage('/recipes/$recipeId/video', videoPath);
        }
      }

      if (mounted) {
        if (response['success'] == true) {
          // ── Hapus draft setelah submit berhasil ──
          await DraftService.clearCreateDraft();

          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Resep berhasil dibuat! Menunggu persetujuan admin...'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pop(context, true);
        } else {
          throw Exception(response['message'] ?? 'Gagal membuat resep');
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')));
      }
    } finally {
      if (mounted) setState(() => _isUploading = false);
    }
  }

  // ─────────────────────────────────────────────
  // BUILD (sama persis dengan file asli — hanya tambahkan draft save indicator)
  // ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : CustomScrollView(
              slivers: [
                _buildAppBar(),
                SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      _buildHeroCard(),
                      const SizedBox(height: 16),
                      _buildContentCard(),
                      const SizedBox(height: 16),
                      _buildSubmitCard(),
                      const SizedBox(height: 100),
                    ]),
                  ),
                ),
              ],
            ),
      bottomNavigationBar:
          CustomBottomNav(currentIndex: 2, avatarUrl: _userAvatarUrl),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 160,
      floating: false,
      pinned: true,
      backgroundColor: Colors.transparent,
      leading: Padding(
        padding: const EdgeInsets.all(8.0),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white, shape: BoxShape.circle,
            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 8)],
          ),
          child: IconButton(
            icon: const Icon(Icons.arrow_back_rounded, color: AppTheme.primaryDark),
            onPressed: () async {
              // Auto-save draft sebelum keluar
              await _saveDraft();
              if (mounted) Navigator.pop(context);
            },
          ),
        ),
      ),
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
                          border: Border.all(color: Colors.white.withValues(alpha: 0.5), width: 2),
                        ),
                        child: const Icon(Icons.restaurant_menu_rounded, color: Colors.white, size: 32),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Buat Resep Baru', style: AppTheme.headingLarge),
                            const SizedBox(height: 4),
                            // Draft indicator
                            Row(
                              children: [
                                const Icon(Icons.auto_awesome_rounded,
                                    color: Colors.white70, size: 12),
                                const SizedBox(width: 4),
                                Text(
                                  'Draft tersimpan otomatis',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.white.withValues(alpha: 0.8),
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
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeroCard() {
    Widget imagePreview;
    if (kIsWeb && _webImageBytes != null) {
      imagePreview = Image.memory(_webImageBytes!, fit: BoxFit.cover);
    } else if (!kIsWeb && _imageFile != null) {
      imagePreview = Image.file(_imageFile!, fit: BoxFit.cover);
    } else {
      imagePreview = Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: [
                AppTheme.primaryCoral.withValues(alpha: 0.2),
                AppTheme.primaryOrange.withValues(alpha: 0.2)
              ]),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.add_photo_alternate_rounded, size: 60, color: AppTheme.primaryCoral),
          ),
          const SizedBox(height: 16),
          Text('Tap untuk memilih gambar resep',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 15, fontWeight: FontWeight.w600)),
        ],
      );
    }

    return Container(
      decoration: AppTheme.cardDecoration,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: _pickImage,
            child: ClipRRect(
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              child: Stack(
                children: [
                  Container(
                    width: double.infinity, height: 240,
                    decoration: BoxDecoration(
                        gradient: LinearGradient(colors: [Colors.grey.shade100, Colors.grey.shade50])),
                    child: imagePreview,
                  ),
                  Positioned(
                    bottom: 16, right: 16,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                      decoration: BoxDecoration(
                        gradient: AppTheme.accentGradient,
                        borderRadius: BorderRadius.circular(14),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 12, offset: const Offset(0, 4))],
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.add_a_photo_rounded, color: Colors.white, size: 20),
                          const SizedBox(width: 8),
                          Text(
                            _imageFile != null || _webImageBytes != null ? 'Ganti Gambar' : 'Pilih Gambar',
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppTheme.buildSectionHeader('Informasi Dasar', Icons.info_outline_rounded),
                const SizedBox(height: 20),
                _buildTextField(controller: _titleController, hint: 'Judul Resep (contoh: Nasi Goreng Spesial)', icon: Icons.restaurant_rounded),
                const SizedBox(height: 16),
                _buildTextField(controller: _descriptionController, hint: 'Deskripsi singkat resep Anda...', icon: Icons.description_rounded, maxLines: 3),
                const SizedBox(height: 20),
                Row(children: [
                  Expanded(child: _buildCategoryDropdown()),
                  const SizedBox(width: 12),
                  Expanded(child: _buildDifficultyDropdown()),
                ]),
                const SizedBox(height: 20),
                Row(children: [
                  Expanded(child: _buildQuickInfoChip(controller: _cookingTimeController, label: 'Waktu (min)', hint: '30', icon: Icons.access_time_rounded, color: AppTheme.primaryTeal)),
                  const SizedBox(width: 12),
                  Expanded(child: _buildQuickInfoChip(controller: _servingsController, label: 'Porsi', hint: '4', icon: Icons.restaurant_menu_rounded, color: AppTheme.primaryCoral)),
                ]),
                const SizedBox(height: 12),
                _buildQuickInfoChip(controller: _caloriesController, label: 'Kalori (kcal) - Opsional', hint: '250', icon: Icons.local_fire_department_rounded, color: AppTheme.primaryOrange),
              ],
            ),
          ),
        ],
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
          AppTheme.buildSectionHeader('Bahan-bahan', Icons.restaurant_menu_rounded),
          const SizedBox(height: 16),
          _buildIngredientInput(),
          const SizedBox(height: 12),
          _buildIngredientsList(),
          const SizedBox(height: 28),
          const Divider(height: 1),
          const SizedBox(height: 28),
          AppTheme.buildSectionHeader('Langkah-langkah', Icons.format_list_numbered_rounded),
          const SizedBox(height: 16),
          _buildStepInput(),
          const SizedBox(height: 12),
          _buildStepsList(),
          const SizedBox(height: 28),
          const Divider(height: 1),
          const SizedBox(height: 28),
          AppTheme.buildSectionHeader('Video Tutorial (Opsional)', Icons.videocam_rounded),
          const SizedBox(height: 16),
          _buildVideoUpload(),
          const SizedBox(height: 28),
          const Divider(height: 1),
          const SizedBox(height: 28),
          AppTheme.buildSectionHeader('Tag Resep', Icons.label_rounded),
          const SizedBox(height: 16),
          _buildTagInput(),
        ],
      ),
    );
  }

  Widget _buildSubmitCard() {
    return Container(
      decoration: AppTheme.cardDecoration,
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          // Draft indicator
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: AppTheme.primaryTeal.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: AppTheme.primaryTeal.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                Icon(Icons.save_outlined, color: AppTheme.primaryTeal, size: 18),
                const SizedBox(width: 8),
                Text(
                  'Form ini tersimpan otomatis sebagai draft',
                  style: TextStyle(fontSize: 12, color: AppTheme.primaryTeal, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          AppTheme.buildInfoBanner('Resep Anda akan ditinjau oleh admin sebelum dipublikasikan'),
          const SizedBox(height: 20),
          Container(
            width: double.infinity,
            height: 54,
            decoration: BoxDecoration(
              gradient: AppTheme.orangeGradient,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [BoxShadow(color: AppTheme.primaryOrange.withValues(alpha: 0.4), blurRadius: 15, offset: const Offset(0, 8))],
            ),
            child: ElevatedButton(
              onPressed: _isUploading ? null : _submitRecipe,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.transparent, shadowColor: Colors.transparent,
                disabledBackgroundColor: Colors.grey.shade300,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: _isUploading
                  ? const SizedBox(height: 24, width: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                  : const Text('Publikasikan Resep', style: AppTheme.buttonText),
            ),
          ),
        ],
      ),
    );
  }

  // ── Helper widgets (sama dengan asli) ──

  Widget _buildTextField({required TextEditingController controller, required String hint, required IconData icon, int maxLines = 1}) {
    return Container(
      decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
      child: TextField(
        controller: controller, maxLines: maxLines,
        decoration: AppTheme.buildInputDecoration(hint: hint, icon: icon, maxLines: maxLines),
      ),
    );
  }

  Widget _buildCategoryDropdown() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
      child: DropdownButton<int>(
        value: _selectedCategoryId, isExpanded: true, underline: Container(),
        hint: Text('Kategori', style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
        items: _categories.map((cat) => DropdownMenuItem<int>(
            value: cat['id'],
            child: Text(cat['name'], style: const TextStyle(fontSize: 14)))).toList(),
        onChanged: (value) {
          setState(() => _selectedCategoryId = value);
          _scheduleDraftSave();
        },
      ),
    );
  }

  Widget _buildDifficultyDropdown() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
      child: DropdownButton<String>(
        value: _selectedDifficulty, isExpanded: true, underline: Container(),
        items: const [
          DropdownMenuItem(value: 'mudah',  child: Text('Mudah',  style: TextStyle(fontSize: 14))),
          DropdownMenuItem(value: 'sedang', child: Text('Sedang', style: TextStyle(fontSize: 14))),
          DropdownMenuItem(value: 'sulit',  child: Text('Sulit',  style: TextStyle(fontSize: 14))),
        ],
        onChanged: (value) {
          if (value != null) {
            setState(() => _selectedDifficulty = value);
            _scheduleDraftSave();
          }
        },
      ),
    );
  }

  Widget _buildQuickInfoChip({required TextEditingController controller, required String label, required String hint, required IconData icon, required Color color}) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [color.withValues(alpha: 0.1), color.withValues(alpha: 0.05)]),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
            const SizedBox(height: 4),
            TextField(
              controller: controller, keyboardType: TextInputType.number,
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: color),
              decoration: InputDecoration(hintText: hint, hintStyle: TextStyle(color: Colors.grey.shade400), border: InputBorder.none, contentPadding: EdgeInsets.zero, isDense: true),
            ),
          ])),
        ],
      ),
    );
  }

  Widget _buildIngredientInput() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
      child: Row(children: [
        Expanded(
          child: TextField(
            controller: _tempIngredientController,
            decoration: InputDecoration(
              hintText: 'Tambah bahan (contoh: 2 siung bawang putih)',
              hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
          ),
        ),
        Container(
          width: 42, height: 42,
          decoration: BoxDecoration(gradient: AppTheme.accentGradient, borderRadius: BorderRadius.circular(10)),
          child: IconButton(onPressed: _addIngredient, icon: const Icon(Icons.add, color: Colors.white, size: 20), padding: EdgeInsets.zero),
        ),
      ]),
    );
  }

  Widget _buildIngredientsList() {
    if (_ingredients.isEmpty) return AppTheme.buildEmptyState(icon: Icons.restaurant_menu_rounded, title: 'Belum ada bahan');
    return Column(
      children: _ingredients.asMap().entries.map((entry) {
        final index = entry.key; final ingredient = entry.value;
        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
          child: Row(children: [
            Container(width: 32, height: 32, decoration: const BoxDecoration(gradient: AppTheme.accentGradient, shape: BoxShape.circle),
                child: Center(child: Text('${index + 1}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)))),
            const SizedBox(width: 12),
            Expanded(child: Text(ingredient, style: const TextStyle(fontSize: 14, color: AppTheme.textPrimary, fontWeight: FontWeight.w500))),
            GestureDetector(
              onTap: () => _removeIngredient(index),
              child: Container(padding: const EdgeInsets.all(4), decoration: BoxDecoration(color: Colors.red.shade50, shape: BoxShape.circle),
                  child: Icon(Icons.close, size: 16, color: Colors.red.shade600)),
            ),
          ]),
        );
      }).toList(),
    );
  }

  Widget _buildStepInput() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Expanded(
          child: TextField(
            controller: _tempStepController, maxLines: 2,
            decoration: InputDecoration(
              hintText: 'Tambah langkah (contoh: Panaskan minyak di wajan...)',
              hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
          ),
        ),
        Container(
          width: 42, height: 42,
          decoration: BoxDecoration(gradient: AppTheme.accentGradient, borderRadius: BorderRadius.circular(10)),
          child: IconButton(onPressed: _addStep, icon: const Icon(Icons.add, color: Colors.white, size: 20), padding: EdgeInsets.zero),
        ),
      ]),
    );
  }

  Widget _buildStepsList() {
    if (_steps.isEmpty) return AppTheme.buildEmptyState(icon: Icons.format_list_numbered_rounded, title: 'Belum ada langkah');
    return Column(
      children: _steps.asMap().entries.map((entry) {
        final index = entry.key; final step = entry.value;
        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(colors: [AppTheme.primaryCoral.withValues(alpha: 0.1), AppTheme.primaryOrange.withValues(alpha: 0.05)]),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppTheme.primaryCoral.withValues(alpha: 0.2)),
          ),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Container(width: 36, height: 36, decoration: BoxDecoration(gradient: AppTheme.accentGradient, borderRadius: BorderRadius.circular(10)),
                child: Center(child: Text('${index + 1}', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)))),
            const SizedBox(width: 14),
            Expanded(child: Text(step, style: const TextStyle(fontSize: 14, color: AppTheme.textPrimary, height: 1.5))),
            GestureDetector(
              onTap: () => _removeStep(index),
              child: Container(padding: const EdgeInsets.all(4), decoration: BoxDecoration(color: Colors.red.shade50, shape: BoxShape.circle),
                  child: Icon(Icons.close, size: 16, color: Colors.red.shade600)),
            ),
          ]),
        );
      }).toList(),
    );
  }

  Widget _buildTagInput() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
          child: Row(children: [
            Expanded(
              child: TextField(
                controller: _tagInputController, onChanged: _searchTags,
                decoration: InputDecoration(
                  hintText: 'Cari atau tambahkan tag (max 10)',
                  hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                  prefixIcon: Icon(Icons.tag_rounded, color: Colors.grey.shade600, size: 20),
                  suffixIcon: _tagInputController.text.isNotEmpty
                      ? IconButton(onPressed: () => setState(() { _tagInputController.clear(); _userCreatedTags.clear(); _isSearchingTags = false; }), icon: const Icon(Icons.clear, size: 20))
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                ),
              ),
            ),
            Container(
              width: 42, height: 42,
              decoration: BoxDecoration(gradient: AppTheme.accentGradient, borderRadius: BorderRadius.circular(10)),
              child: IconButton(
                onPressed: () { if (_tagInputController.text.trim().isNotEmpty) _addTag(_tagInputController.text.trim()); },
                icon: const Icon(Icons.add, color: Colors.white, size: 20), padding: EdgeInsets.zero,
              ),
            ),
          ]),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _openTagManagementPage,
                icon: const Icon(Icons.manage_search_rounded, size: 18),
                label: const Text('Kelola Tag Komunitas'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (_selectedTags.isNotEmpty)
          Wrap(spacing: 8, runSpacing: 8,
              children: _selectedTags.asMap().entries.map((entry) {
                final index = entry.key; final tag = entry.value;
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: AppTheme.selectedTagDecoration,
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    const Icon(Icons.tag_rounded, size: 14, color: Colors.white),
                    const SizedBox(width: 6),
                    Text(tag, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                    const SizedBox(width: 6),
                    GestureDetector(onTap: () => _removeTag(index), child: const Icon(Icons.close, size: 16, color: Colors.white)),
                  ]),
                );
              }).toList()),
        if (_isSearchingTags && _userCreatedTags.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(top: 12),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(gradient: LinearGradient(colors: [Colors.grey.shade100, Colors.grey.shade50]), borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey.shade200)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Hasil Pencarian:', style: TextStyle(fontSize: 12, color: Colors.grey.shade700, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              ..._userCreatedTags.map((tag) => InkWell(
                onTap: () => _addTag(tag['name']),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(children: [
                    Icon(Icons.tag_rounded, size: 16, color: Colors.grey.shade600),
                    const SizedBox(width: 8),
                    Expanded(child: Text(tag['name'], style: const TextStyle(fontSize: 13))),
                    tag['is_approved'] == true
                        ? const Icon(Icons.check_circle, color: Colors.green, size: 16)
                        : const Icon(Icons.pending, color: Colors.orange, size: 16),
                  ]),
                ),
              )),
            ]),
          ),
        if (!_isSearchingTags && _popularTags.isNotEmpty && _tagInputController.text.isEmpty)
          Container(
            margin: const EdgeInsets.only(top: 12),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(gradient: LinearGradient(colors: [Colors.grey.shade100, Colors.grey.shade50]), borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey.shade200)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Tag Populer:', style: TextStyle(fontSize: 12, color: Colors.grey.shade700, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Wrap(spacing: 8, runSpacing: 8,
                  children: _popularTags.take(8).map((tag) {
                    final isSelected = _selectedTags.contains(tag['name']);
                    return GestureDetector(
                      onTap: () { isSelected ? setState(() => _selectedTags.remove(tag['name'])) : _addTag(tag['name']); },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: isSelected ? AppTheme.selectedTagDecoration : AppTheme.unselectedTagDecoration,
                        child: Text(tag['name'], style: TextStyle(fontSize: 12, color: isSelected ? Colors.white : Colors.grey.shade700, fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal)),
                      ),
                    );
                  }).toList()),
            ]),
          ),
      ],
    );
  }

  Widget _buildVideoUpload() {
    if (_videoFile != null || _webVideoBytes != null) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: [AppTheme.primaryTeal.withValues(alpha: 0.1), AppTheme.primaryTeal.withValues(alpha: 0.2)]),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppTheme.primaryTeal.withValues(alpha: 0.3)),
        ),
        child: Row(children: [
          Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(gradient: AppTheme.tealGradient, borderRadius: BorderRadius.circular(10)),
              child: const Icon(Icons.videocam, color: Colors.white, size: 24)),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Video berhasil dipilih', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
            const SizedBox(height: 4),
            Text(_videoFileName ?? 'video.mp4', style: TextStyle(fontSize: 12, color: Colors.grey.shade600), maxLines: 1, overflow: TextOverflow.ellipsis),
          ])),
          IconButton(onPressed: _removeVideo, icon: Icon(Icons.close, color: Colors.red.shade600)),
        ]),
      );
    }
    return GestureDetector(
      onTap: _pickVideo,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: [Colors.grey.shade100, Colors.grey.shade50]),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade300, width: 2),
        ),
        child: Column(children: [
          Icon(Icons.video_library_rounded, size: 48, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text('Tap untuk upload video tutorial', style: TextStyle(fontSize: 14, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
          const SizedBox(height: 6),
          Text('Format: MP4 | Max: 50MB | Max durasi: 5 menit', style: TextStyle(fontSize: 11, color: Colors.grey.shade500), textAlign: TextAlign.center),
        ]),
      ),
    );
  }
}