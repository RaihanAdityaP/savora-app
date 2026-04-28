import 'package:flutter/material.dart';

import '../services/tag_client.dart';
import '../widgets/theme.dart';

class TagManagementScreen extends StatefulWidget {
  const TagManagementScreen({super.key});

  @override
  State<TagManagementScreen> createState() => _TagManagementScreenState();
}

class _TagManagementScreenState extends State<TagManagementScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  final TextEditingController _createCtrl = TextEditingController();

  List<Map<String, dynamic>> _tags = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadPopular();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _createCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadPopular() async {
    setState(() => _isLoading = true);
    final tags = await TagClient.popularTags();
    if (!mounted) return;
    setState(() {
      _tags = tags;
      _isLoading = false;
    });
  }

  Future<void> _search(String text) async {
    if (text.trim().isEmpty) {
      _loadPopular();
      return;
    }

    setState(() => _isLoading = true);
    final tags = await TagClient.searchTags(text.trim());
    if (!mounted) return;
    setState(() {
      _tags = tags;
      _isLoading = false;
    });
  }

  Future<void> _createTag() async {
    final tagName = _createCtrl.text.trim();
    if (tagName.isEmpty) return;

    final created = await TagClient.createTag(tagName);
    if (!mounted) return;

    if (created == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(children: [
            Icon(Icons.error_outline_rounded, color: Colors.white),
            SizedBox(width: 12),
            Text('Gagal membuat tag.'),
          ]),
          backgroundColor: Colors.red.shade600,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.all(16),
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(children: [
          const Icon(Icons.check_circle_outline_rounded, color: Colors.white),
          const SizedBox(width: 12),
          Text('Tag "$tagName" berhasil dibuat.'),
        ]),
        backgroundColor: Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
    Navigator.pop(context, created['name']?.toString() ?? tagName);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      body: CustomScrollView(
        slivers: [
          _buildAppBar(),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                _buildCreateField(),
                const SizedBox(height: 16),
                _buildSearchField(),
                const SizedBox(height: 24),
                _buildTagList(),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 140,
      pinned: true,
      backgroundColor: Colors.transparent,
      leading: Padding(
        padding: const EdgeInsets.all(8),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 8,
              ),
            ],
          ),
          child: IconButton(
            icon: const Icon(Icons.arrow_back_rounded, color: AppTheme.primaryDark),
            onPressed: () => Navigator.pop(context),
          ),
        ),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(gradient: AppTheme.accentGradient),
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
                        child: const Icon(Icons.label_rounded, color: Colors.white, size: 28),
                      ),
                      const SizedBox(width: 16),
                      const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Kelola Tag', style: AppTheme.headingLarge),
                          SizedBox(height: 4),
                          Text(
                            'Buat & cari tag komunitas',
                            style: TextStyle(fontSize: 14, color: Colors.white70),
                          ),
                        ],
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

  Widget _buildCreateField() {
    return Container(
      decoration: AppTheme.cardDecoration,
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppTheme.buildSectionHeader('Tambah Tag Baru', Icons.add_circle_outline_rounded),
          const SizedBox(height: 16),
          Container(
            decoration: AppTheme.inputDecoration(AppTheme.primaryCoral),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _createCtrl,
                    decoration: AppTheme.buildInputDecoration(
                      hint: 'Nama tag baru (cth: sarapan, vegan)',
                      icon: Icons.tag_rounded,
                      iconColor: AppTheme.primaryCoral,
                    ),
                    onSubmitted: (_) => _createTag(),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: AppTheme.accentGradient,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: IconButton(
                      icon: const Icon(Icons.add_rounded, color: Colors.white),
                      onPressed: _createTag,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          AppTheme.buildInfoBanner('Tag baru akan menunggu persetujuan admin sebelum bisa digunakan.'),
        ],
      ),
    );
  }

  Widget _buildSearchField() {
    return Container(
      decoration: AppTheme.inputDecoration(AppTheme.primaryTeal),
      child: TextField(
        controller: _searchCtrl,
        onChanged: _search,
        decoration: InputDecoration(
          hintText: 'Cari tag yang sudah ada...',
          hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
          prefixIcon: const Icon(Icons.search_rounded, color: AppTheme.primaryTeal, size: 22),
          suffixIcon: _searchCtrl.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear_rounded, size: 20),
                  onPressed: () {
                    _searchCtrl.clear();
                    _loadPopular();
                    setState(() {});
                  },
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        ),
      ),
    );
  }

  Widget _buildTagList() {
    if (_isLoading) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(40),
          child: CircularProgressIndicator(color: AppTheme.primaryCoral),
        ),
      );
    }

    if (_tags.isEmpty) {
      return AppTheme.buildEmptyState(
        icon: Icons.label_off_rounded,
        title: _searchCtrl.text.isNotEmpty ? 'Tag tidak ditemukan' : 'Belum ada tag',
        subtitle: _searchCtrl.text.isNotEmpty
            ? 'Coba kata kunci lain atau buat tag baru'
            : 'Buat tag pertama di atas',
      );
    }

    final label = _searchCtrl.text.isNotEmpty ? 'Hasil Pencarian' : 'Tag Populer';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 4,
              height: 20,
              decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(width: 10),
            Text(
              label,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                gradient: AppTheme.accentGradient,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '${_tags.length}',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Container(
          decoration: AppTheme.cardDecoration,
          clipBehavior: Clip.hardEdge,
          child: ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _tags.length,
            separatorBuilder: (_, _) => Divider(
              height: 1,
              color: Colors.grey.shade100,
            ),
            itemBuilder: (context, index) {
              final tag = _tags[index];
              final tagName = tag['name']?.toString() ?? '';
              final usageCount = tag['usage_count'] ?? 0;
              final isApproved = tag['is_approved'] == true;

              return Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () => Navigator.pop(context, tagName),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            gradient: AppTheme.accentGradient,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.tag_rounded, color: Colors.white, size: 16),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                tagName,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: AppTheme.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                'Dipakai $usageCount resep',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: isApproved
                                ? Colors.green.shade50
                                : Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isApproved
                                  ? Colors.green.shade200
                                  : Colors.orange.shade200,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                isApproved
                                    ? Icons.check_circle_rounded
                                    : Icons.pending_rounded,
                                size: 12,
                                color: isApproved
                                    ? Colors.green.shade600
                                    : Colors.orange.shade600,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                isApproved ? 'Approved' : 'Pending',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: isApproved
                                      ? Colors.green.shade600
                                      : Colors.orange.shade600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Icon(
                          Icons.chevron_right_rounded,
                          color: Colors.grey.shade400,
                          size: 18,
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}