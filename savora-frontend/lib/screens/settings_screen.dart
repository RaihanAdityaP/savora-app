import 'package:flutter/material.dart';
import '../services/app_settings_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  Map<String, dynamic> settings = const {};
  bool isLoading = false;

  String _t(String en, String id) => settings['language'] == 'id' ? id : en;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() => isLoading = true);
    final loadedSettings = await AppSettingsService.load();
    if (!mounted) return;
    setState(() {
      settings = loadedSettings.toMap();
      isLoading = false;
    });
  }

  Future<void> _saveSettings() async {
    setState(() => isLoading = true);
    try {
      await AppSettingsService.save(
        AppSettings(
          theme: settings['theme'] as String,
          language: settings['language'] as String,
          fontSize: settings['fontSize'] as int,
          notifyLikes: settings['notify_likes'] as bool,
          notifyComments: settings['notify_comments'] as bool,
          notifyFollows: settings['notify_follows'] as bool,
          notifyEmail: settings['notify_email'] as bool,
          allowAnalytics: settings['allow_analytics'] as bool,
          profilePublic: settings['profile_public'] as bool,
          autoSaveDrafts: settings['auto_save_drafts'] as bool,
        ),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Settings berhasil disimpan!'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = settings['theme'] == 'dark';

    return Scaffold(
      backgroundColor: isDarkMode ? const Color(0xFF0a0a0a) : Colors.white,
      appBar: AppBar(
        backgroundColor: isDarkMode ? const Color(0xFF1a1a1a) : Colors.grey[100],
        elevation: 0,
        title: Text(
          _t('Settings', 'Pengaturan'),
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.bold,
            color: isDarkMode ? Colors.white : Colors.black,
          ),
        ),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: isDarkMode ? Colors.white : Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: settings.isEmpty
          ? Center(
              child: CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation(
                  Theme.of(context).primaryColor,
                ),
              ),
            )
          : SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 16.0),
                child: Column(
                  children: [
                    _buildSectionHeader(_t('Display & Appearance', 'Tampilan'), Icons.palette_outlined, isDarkMode),
                    _buildThemeSelector(isDarkMode),
                    _buildLanguageSelector(isDarkMode),
                    _buildFontSizeSlider(isDarkMode),

                    const SizedBox(height: 24),

                    _buildSectionHeader(_t('Notifications', 'Notifikasi'), Icons.notifications_outlined, isDarkMode),
                    _buildNotificationToggle('Likes', 'Notify when someone likes my recipe', 'notify_likes', isDarkMode),
                    _buildNotificationToggle('Comments', 'Notify when someone comments on my recipe', 'notify_comments', isDarkMode),
                    _buildNotificationToggle('Follows', 'Notify when someone follows me', 'notify_follows', isDarkMode),
                    _buildNotificationToggle('Email', 'Send me email notifications', 'notify_email', isDarkMode),

                    const SizedBox(height: 24),

                    _buildSectionHeader(_t('Privacy & Data', 'Privasi & Data'), Icons.security_outlined, isDarkMode),
                    _buildPrivacyToggle(_t('Analytics', 'Analitik'), _t('Allow usage analytics to improve Savora', 'Izinkan analitik penggunaan untuk meningkatkan Savora'), 'allow_analytics', isDarkMode),
                    _buildPrivacyToggle(_t('Public Profile', 'Profil Publik'), _t('Make my profile public', 'Jadikan profil saya publik'), 'profile_public', isDarkMode),

                    const SizedBox(height: 24),

                    _buildSectionHeader(_t('Other Preferences', 'Preferensi Lain'), Icons.tune_outlined, isDarkMode),
                    _buildPreferenceToggle(_t('Auto-save Drafts', 'Simpan Draft Otomatis'), _t('Auto-save recipe drafts', 'Simpan draft resep otomatis'), 'auto_save_drafts', isDarkMode),

                    const SizedBox(height: 32),

                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16.0),
                      child: SizedBox(
                        width: double.infinity,
                        height: 56,
                        child: ElevatedButton(
                          onPressed: isLoading ? null : _saveSettings,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFE76F51),
                            disabledBackgroundColor: Colors.grey,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          child: isLoading
                              ? const SizedBox(
                                  height: 24,
                                  width: 24,
                                  child: CircularProgressIndicator(
                                    valueColor: AlwaysStoppedAnimation(Colors.white),
                                    strokeWidth: 2,
                                  ),
                                )
                              : Text(
                                  _t('Save Settings', 'Simpan Pengaturan'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon, bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? Colors.white.withAlpha(13) // 0.05 * 255 ≈ 13
                  : Colors.grey[200],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              icon,
              size: 20,
              color: isDarkMode ? Colors.white70 : Colors.black54,
            ),
          ),
          const SizedBox(width: 12),
          Text(
            title,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: isDarkMode ? Colors.white : Colors.black,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildThemeSelector(bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _t('Theme', 'Tema'),
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white70 : Colors.black54,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _buildThemeButton(_t('Dark', 'Gelap'), 'dark', isDarkMode)),
              const SizedBox(width: 12),
              Expanded(child: _buildThemeButton(_t('Light', 'Terang'), 'light', isDarkMode)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildThemeButton(String label, String value, bool isDarkMode) {
    final isSelected = settings['theme'] == value;
    return GestureDetector(
      onTap: () => setState(() => settings['theme'] = value),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected
              ? const Color(0xFFE76F51).withAlpha(51) // 0.2 * 255 ≈ 51
              : (isDarkMode ? Colors.white.withAlpha(13) : Colors.grey[200]),
          border: Border.all(
            color: isSelected ? const Color(0xFFE76F51) : Colors.transparent,
            width: 2,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                value == 'dark' ? Icons.dark_mode : Icons.light_mode,
                size: 18,
                color: isSelected
                    ? const Color(0xFFE76F51)
                    : (isDarkMode ? Colors.white70 : Colors.black54),
              ),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isSelected
                      ? const Color(0xFFE76F51)
                      : (isDarkMode ? Colors.white70 : Colors.black54),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLanguageSelector(bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _t('Language', 'Bahasa'),
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white70 : Colors.black54,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: isDarkMode ? Colors.white.withAlpha(13) : Colors.grey[100],
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isDarkMode ? Colors.white.withAlpha(26) : Colors.grey[300]!, // 0.1 * 255 ≈ 26
              ),
            ),
            child: DropdownButton<String>(
              value: settings['language'] ?? 'en',
              isExpanded: true,
              underline: const SizedBox(),
              style: TextStyle(
                color: isDarkMode ? Colors.white : Colors.black,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              dropdownColor: isDarkMode ? const Color(0xFF1a1a1a) : Colors.white,
              items: const [
                DropdownMenuItem(value: 'en', child: Text('English')),
                DropdownMenuItem(value: 'id', child: Text('Bahasa Indonesia')),
              ],
              onChanged: (value) {
                if (value != null) setState(() => settings['language'] = value);
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFontSizeSlider(bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _t('Font Size', 'Ukuran Font'),
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white70 : Colors.black54,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: SliderTheme(
                  data: SliderTheme.of(context).copyWith(
                    activeTrackColor: const Color(0xFFE76F51),
                    inactiveTrackColor: isDarkMode
                        ? Colors.white.withAlpha(26)
                        : Colors.grey[300],
                    thumbColor: const Color(0xFFE76F51),
                    overlayColor: const Color(0xFFE76F51).withAlpha(31),
                  ),
                  child: Slider(
                    value: settings['fontSize'].toDouble(),
                    min: 12,
                    max: 18,
                    divisions: 6,
                    onChanged: (value) => setState(() => settings['fontSize'] = value.toInt()),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Container(
                width: 48,
                padding: const EdgeInsets.symmetric(vertical: 6),
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white.withAlpha(13) : Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Text(
                    '${settings['fontSize']}px',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : Colors.black,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationToggle(String title, String subtitle, String key, bool isDarkMode) {
    return _buildSettingTile(
      title: title,
      subtitle: subtitle,
      trailing: Switch(
        value: settings[key] ?? false,
        onChanged: (value) => setState(() => settings[key] = value),
        activeThumbColor: const Color(0xFFE76F51),
        activeTrackColor: const Color(0xFFE76F51).withAlpha(128),
      ),
      isDarkMode: isDarkMode,
    );
  }

  Widget _buildPrivacyToggle(String title, String subtitle, String key, bool isDarkMode) {
    return _buildSettingTile(
      title: title,
      subtitle: subtitle,
      trailing: Switch(
        value: settings[key] ?? false,
        onChanged: (value) => setState(() => settings[key] = value),
        activeThumbColor: const Color(0xFFE76F51),
        activeTrackColor: const Color(0xFFE76F51).withAlpha(128),
      ),
      isDarkMode: isDarkMode,
    );
  }

  Widget _buildPreferenceToggle(String title, String subtitle, String key, bool isDarkMode) {
    return _buildSettingTile(
      title: title,
      subtitle: subtitle,
      trailing: Switch(
        value: settings[key] ?? false,
        onChanged: (value) => setState(() => settings[key] = value),
        activeThumbColor: const Color(0xFFE76F51),
        activeTrackColor: const Color(0xFFE76F51).withAlpha(128),
      ),
      isDarkMode: isDarkMode,
    );
  }

  Widget _buildSettingTile({
    required String title,
    required String subtitle,
    required Widget trailing,
    required bool isDarkMode,
  }) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? Colors.white.withAlpha(5) : Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDarkMode ? Colors.white.withAlpha(13) : Colors.grey[200]!,
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : Colors.black,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white54 : Colors.black54,
                  ),
                ),
              ],
            ),
          ),
          trailing,
        ],
      ),
    );
  }
}