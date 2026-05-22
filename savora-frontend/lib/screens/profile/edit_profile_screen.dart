import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../services/app_settings_service.dart';
import '../../services/user_client.dart';
import '../../widgets/theme.dart';

class EditProfileScreen extends StatefulWidget {
  final String userId;
  final Map<String, dynamic> initialProfile;

  const EditProfileScreen({
    super.key,
    required this.userId,
    required this.initialProfile,
  });

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late final TextEditingController _usernameController;
  late final TextEditingController _fullNameController;
  late final TextEditingController _bioController;
  final ImagePicker _picker = ImagePicker();
  bool _isSaving = false;
  bool _isUploading = false;
  String? _avatarUrl;
  String _t(String en, String id) => AppSettingsService.isEnglish ? en : id;

  @override
  void initState() {
    super.initState();
    _usernameController = TextEditingController(text: widget.initialProfile['username'] ?? '');
    _fullNameController = TextEditingController(text: widget.initialProfile['full_name'] ?? '');
    _bioController = TextEditingController(text: widget.initialProfile['bio'] ?? '');
    _avatarUrl = widget.initialProfile['avatar_url'];
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _fullNameController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  Future<void> _pickAndUploadImage() async {
    final image = await _picker.pickImage(source: ImageSource.gallery, maxWidth: 512, maxHeight: 512, imageQuality: 75);
    if (image == null) return;

    setState(() => _isUploading = true);
    final updated = await UserClient.updateProfile(userId: widget.userId, avatarPath: image.path);
    if (!mounted) return;
    setState(() {
      _avatarUrl = updated?['avatar_url'] ?? _avatarUrl;
      _isUploading = false;
    });
    _showSnackBar(
      updated != null
          ? _t('Profile photo updated', 'Foto profil berhasil diperbarui')
          : _t('Failed to upload photo', 'Gagal mengunggah foto'),
      isError: updated == null,
    );
  }

  Future<void> _saveProfile() async {
    if (_usernameController.text.trim().isEmpty) {
      _showSnackBar(_t('Username cannot be empty', 'Username tidak boleh kosong'), isError: true);
      return;
    }

    setState(() => _isSaving = true);
    final updated = await UserClient.updateProfile(
      userId: widget.userId,
      username: _usernameController.text.trim(),
      fullName: _fullNameController.text.trim(),
      bio: _bioController.text.trim(),
    );
    if (!mounted) return;
    setState(() => _isSaving = false);

    if (updated != null) {
      _showSnackBar(_t('Profile updated', 'Profil berhasil diperbarui'), isError: false);
      Navigator.pop(context, true);
    } else {
      _showSnackBar(_t('Failed to save profile', 'Gagal menyimpan profil'), isError: true);
    }
  }

  void _showSnackBar(String message, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: AppTheme.textPrimary,
        elevation: 0,
        title: Text(_t('Edit Profile', 'Edit Profil'), style: const TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Center(
            child: GestureDetector(
              onTap: _isUploading ? null : _pickAndUploadImage,
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 54,
                    backgroundImage: _avatarUrl != null ? NetworkImage(_avatarUrl!) : null,
                    child: _avatarUrl == null ? const Icon(Icons.person_rounded, size: 42) : null,
                  ),
                  Positioned(
                    right: 0,
                    bottom: 0,
                    child: CircleAvatar(
                      radius: 18,
                      backgroundColor: AppTheme.primaryCoral,
                      child: _isUploading
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 18),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          _field(_usernameController, 'Username', Icons.alternate_email_rounded),
          const SizedBox(height: 16),
          _field(_fullNameController, _t('Full Name', 'Nama Lengkap'), Icons.person_outline_rounded),
          const SizedBox(height: 16),
          _field(_bioController, 'Bio', Icons.edit_note_rounded, maxLines: 4),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _isSaving ? null : _saveProfile,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryCoral,
              foregroundColor: Colors.white,
              minimumSize: const Size.fromHeight(52),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
            icon: _isSaving
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.save_rounded),
            label: Text(_t('Save Changes', 'Simpan Perubahan'), style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _field(TextEditingController controller, String label, IconData icon, {int maxLines = 1}) {
    return TextField(
      controller: controller,
      maxLines: maxLines,
      style: AppTheme.fieldText,
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: AppTheme.textSecondary),
        prefixIcon: Icon(icon, color: AppTheme.primaryCoral),
        filled: true,
        fillColor: AppTheme.surfaceColor,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: AppTheme.borderColor),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: AppTheme.borderColor),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppTheme.primaryCoral, width: 1.5),
        ),
      ),
    );
  }
}
