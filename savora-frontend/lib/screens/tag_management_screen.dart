import 'package:flutter/material.dart';

import '../services/tag_client.dart';

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
        const SnackBar(content: Text('Gagal membuat tag.')), 
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Tag "$tagName" berhasil dibuat.')),
    );
    Navigator.pop(context, created['name']?.toString() ?? tagName);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kelola Tag')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _createCtrl,
              decoration: InputDecoration(
                labelText: 'Tambah tag baru',
                suffixIcon: IconButton(
                  onPressed: _createTag,
                  icon: const Icon(Icons.add_circle_rounded),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _searchCtrl,
              onChanged: _search,
              decoration: const InputDecoration(
                labelText: 'Cari tag',
                prefixIcon: Icon(Icons.search_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : ListView.separated(
                      itemCount: _tags.length,
                      separatorBuilder: (_, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final tag = _tags[index];
                        final tagName = tag['name']?.toString() ?? '';
                        return ListTile(
                          leading: const Icon(Icons.tag_rounded),
                          title: Text(tagName),
                          subtitle: Text('Dipakai ${tag['usage_count'] ?? 0} resep'),
                          trailing: const Icon(Icons.chevron_right_rounded),
                          onTap: () => Navigator.pop(context, tagName),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}