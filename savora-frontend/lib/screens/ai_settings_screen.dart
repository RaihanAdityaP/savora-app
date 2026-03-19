import 'package:flutter/material.dart';
import '../services/ai_chat_client.dart';
import '../widgets/theme.dart';

class AISettingsScreen extends StatefulWidget {
  const AISettingsScreen({super.key});

  @override
  State<AISettingsScreen> createState() => _AISettingsScreenState();
}

class _AISettingsScreenState extends State<AISettingsScreen> {
  final _openRouterKeyController   = TextEditingController();
  final _openRouterModelController = TextEditingController();

  bool   _isLoading      = true;
  bool   _isSaving       = false;
  bool   _isTesting      = false;
  bool   _obscureApiKey  = true;
  String _testResult     = '';
  bool   _testSuccess    = false;

  String _activeProvider      = 'default';
  bool   _hasOpenRouterApiKey = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _openRouterKeyController.dispose();
    _openRouterModelController.dispose();
    super.dispose();
  }

  // ─────────────────────────────────────────────
  // LOAD
  // ─────────────────────────────────────────────

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final settings = await AIChatClient.getSettings();
    if (mounted) {
      setState(() {
        if (settings != null) {
          // Backend simpan 'groq' tapi UI tampilkan sebagai 'default'
          final provider = settings['is_active_provider'] ?? 'groq';
          _activeProvider      = (provider == 'groq') ? 'default' : provider;
          _hasOpenRouterApiKey = settings['openrouter_api_key'] == '***SAVED***';
          final savedModel     = settings['openrouter_model'] ?? '';
          if (savedModel.isNotEmpty) {
            _openRouterModelController.text = savedModel;
          }
        }
        _isLoading = false;
      });
    }
  }

  // ─────────────────────────────────────────────
  // SAVE
  // ─────────────────────────────────────────────

  Future<void> _save() async {
    if (_activeProvider == 'openrouter') {
      final model  = _openRouterModelController.text.trim();
      final apiKey = _openRouterKeyController.text.trim();
      if (model.isEmpty) {
        _showSnackBar('Model name wajib diisi untuk OpenRouter', isError: true);
        return;
      }
      if (!_hasOpenRouterApiKey && apiKey.isEmpty) {
        _showSnackBar('API key wajib diisi untuk OpenRouter', isError: true);
        return;
      }
    }

    setState(() { _isSaving = true; _testResult = ''; });

    final apiKey  = _openRouterKeyController.text.trim();
    final orModel = _openRouterModelController.text.trim();

    final success = await AIChatClient.saveSettings(
      // UI pakai 'default' tapi backend tetap expect 'groq'
      activeProvider   : _activeProvider == 'default' ? 'groq' : _activeProvider,
      openRouterModel  : orModel.isNotEmpty
          ? orModel
          : 'meta-llama/llama-3.3-70b-instruct:free',
      openRouterApiKey : apiKey.isNotEmpty ? apiKey : null,
    );

    if (mounted) {
      setState(() => _isSaving = false);
      _showSnackBar(
        success ? 'Settings berhasil disimpan!' : 'Gagal menyimpan settings',
        isError: !success,
      );
      if (success && apiKey.isNotEmpty) {
        setState(() {
          _hasOpenRouterApiKey = true;
          _openRouterKeyController.clear();
        });
      }
    }
  }

  // ─────────────────────────────────────────────
  // TEST CONNECTION
  // ─────────────────────────────────────────────

  Future<void> _testConnection() async {
    if (_activeProvider == 'openrouter') {
      final model  = _openRouterModelController.text.trim();
      final apiKey = _openRouterKeyController.text.trim();
      if (model.isEmpty) {
        _showSnackBar('Isi model name dulu sebelum test', isError: true);
        return;
      }
      if (!_hasOpenRouterApiKey && apiKey.isEmpty) {
        _showSnackBar('Isi API key dulu sebelum test', isError: true);
        return;
      }
    }

    setState(() { _isTesting = true; _testResult = ''; });

    final backendProvider = _activeProvider == 'default' ? 'groq' : _activeProvider;
    final model  = _activeProvider == 'openrouter'
        ? _openRouterModelController.text.trim()
        : 'llama-3.3-70b-versatile';
    final apiKey = _openRouterKeyController.text.trim();

    final result = await AIChatClient.testConnection(
      provider         : backendProvider,
      model            : model,
      openRouterApiKey : apiKey.isNotEmpty ? apiKey : null,
    );

    if (mounted) {
      setState(() {
        _isTesting   = false;
        _testSuccess = result['success'] == true;
        _testResult  = _testSuccess
            ? '✅ Koneksi berhasil! Provider siap digunakan.'
            : '❌ ${result['message']}';
      });
    }
  }

  // ─────────────────────────────────────────────
  // RESET
  // ─────────────────────────────────────────────

  Future<void> _reset() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape  : RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title  : const Text('Reset Settings'),
        content: const Text('Reset ke default? Akan kembali menggunakan Groq dari server.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style    : ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child    : const Text('Reset'),
          ),
        ],
      ),
    );
    if (confirm == true) {
      await AIChatClient.resetSettings();
      _openRouterKeyController.clear();
      _openRouterModelController.clear();
      _loadData();
    }
  }

  void _showSnackBar(String msg, {required bool isError}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content        : Text(msg),
      backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
      behavior       : SnackBarBehavior.floating,
      shape          : RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin         : const EdgeInsets.all(16),
    ));
  }

  // ─────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundLight,
      appBar         : AppBar(
        backgroundColor: Colors.white,
        elevation      : 1,
        leading        : IconButton(
          icon     : const Icon(Icons.arrow_back_rounded, color: AppTheme.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'AI Settings',
          style: TextStyle(color: AppTheme.textPrimary, fontWeight: FontWeight.bold),
        ),
        actions: [
          TextButton(
            onPressed: _isLoading || _isSaving ? null : _reset,
            child    : Text('Reset', style: TextStyle(color: Colors.grey.shade600)),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryCoral))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child  : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children          : [
                  _buildProviderSelector(),
                  const SizedBox(height: 20),
                  if (_activeProvider == 'default')     _buildDefaultInfo(),
                  if (_activeProvider == 'openrouter') _buildOpenRouterSettings(),
                  const SizedBox(height: 20),
                  _buildTestSection(),
                  const SizedBox(height: 20),
                  _buildSaveButton(),
                  const SizedBox(height: 40),
                ],
              ),
            ),
    );
  }

  // ─────────────────────────────────────────────
  // PROVIDER SELECTOR — Column bukan Row, fix overflow
  // ─────────────────────────────────────────────

  Widget _buildProviderSelector() {
    return Container(
      padding   : const EdgeInsets.all(20),
      decoration: AppTheme.cardDecoration,
      child     : Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children          : [
          AppTheme.buildSectionHeader('Provider AI', Icons.hub_rounded),
          const SizedBox(height: 16),
          Column(
            children: [
              _providerChip(
                value   : 'default',
                label   : 'Default',
                subtitle: 'Gratis — Groq (teks) + HuggingFace (gambar) dari server',
                icon    : Icons.verified_rounded,
                color   : AppTheme.primaryTeal,
              ),
              const SizedBox(height: 10),
              _providerChip(
                value   : 'openrouter',
                label   : 'OpenRouter',
                subtitle: 'API key & model konfigurasi sendiri',
                icon    : Icons.route_rounded,
                color   : Colors.purple,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _providerChip({
    required String   value,
    required String   label,
    required String   subtitle,
    required IconData icon,
    required Color    color,
  }) {
    final isSelected = _activeProvider == value;
    return GestureDetector(
      onTap: () => setState(() { _activeProvider = value; _testResult = ''; }),
      child: AnimatedContainer(
        duration : const Duration(milliseconds: 200),
        width    : double.infinity,
        padding  : const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        decoration: BoxDecoration(
          gradient    : isSelected
              ? LinearGradient(colors: [color, color.withValues(alpha: 0.75)])
              : null,
          color       : isSelected ? null : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(14),
          border      : Border.all(
            color: isSelected ? color : Colors.grey.shade300,
            width: isSelected ? 2 : 1,
          ),
          boxShadow: isSelected
              ? [BoxShadow(color: color.withValues(alpha: 0.25), blurRadius: 8, offset: const Offset(0, 4))]
              : null,
        ),
        child: Row(
          children: [
            Container(
              padding   : const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color       : isSelected
                    ? Colors.white.withValues(alpha: 0.2)
                    : color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: isSelected ? Colors.white : color, size: 20),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children          : [
                  Text(
                    label,
                    style: TextStyle(
                      color     : isSelected ? Colors.white : AppTheme.textPrimary,
                      fontWeight: FontWeight.bold,
                      fontSize  : 15,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color  : isSelected
                          ? Colors.white.withValues(alpha: 0.8)
                          : Colors.grey.shade500,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            if (isSelected)
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────
  // DEFAULT INFO — Groq (chat) + HuggingFace (gambar), semua dari server
  // ─────────────────────────────────────────────

  Widget _buildDefaultInfo() {
    return Container(
      padding   : const EdgeInsets.all(20),
      decoration: AppTheme.cardDecoration,
      child     : Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children          : [
          AppTheme.buildSectionHeader('Konfigurasi Default', Icons.info_outline_rounded),
          const SizedBox(height: 16),
          _infoRow(
            icon : Icons.flash_on_rounded,
            color: AppTheme.primaryTeal,
            title: 'Chat Teks',
            value: 'Groq — llama-3.3-70b-versatile',
          ),
          const SizedBox(height: 10),
          _infoRow(
            icon : Icons.image_search_rounded,
            color: Colors.orange,
            title: 'Analisis Gambar',
            value: 'Hugging Face — vit-gpt2',
          ),
          const SizedBox(height: 16),
          Container(
            padding   : const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color       : AppTheme.primaryTeal.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border      : Border.all(color: AppTheme.primaryTeal.withValues(alpha: 0.25)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children          : [
                Icon(Icons.check_circle_rounded, color: AppTheme.primaryTeal, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Semua API key dikelola server — tidak perlu konfigurasi apapun. '
                    'Tinggal pilih Default dan langsung chat!',
                    style: TextStyle(
                      fontSize: 13,
                      color   : AppTheme.primaryTeal.withValues(alpha: 0.9),
                      height  : 1.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow({
    required IconData icon,
    required Color    color,
    required String   title,
    required String   value,
  }) {
    return Container(
      padding   : const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color       : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(10),
        border      : Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Container(
            padding   : const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color       : color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children          : [
                Text(
                  title,
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(fontSize: 13, color: AppTheme.textPrimary, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // OPENROUTER SETTINGS
  // ─────────────────────────────────────────────

  Widget _buildOpenRouterSettings() {
    return Column(
      children: [
        // Peringatan biaya
        Container(
          padding   : const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color       : Colors.amber.shade50,
            borderRadius: BorderRadius.circular(14),
            border      : Border.all(color: Colors.amber.shade300, width: 1.5),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children          : [
              Icon(Icons.warning_amber_rounded, color: Colors.amber.shade700, size: 22),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children          : [
                    Text(
                      'Perhatian Biaya',
                      style: TextStyle(fontWeight: FontWeight.bold, color: Colors.amber.shade800, fontSize: 14),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Model berbayar memerlukan saldo di akun openrouter.ai kamu. '
                      'Model berlabel FREE bisa digunakan tanpa biaya.',
                      style: TextStyle(fontSize: 12, color: Colors.amber.shade800, height: 1.5),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // API Key card
        Container(
          padding   : const EdgeInsets.all(20),
          decoration: AppTheme.cardDecoration,
          child     : Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children          : [
              AppTheme.buildSectionHeader('API Key', Icons.key_rounded),
              const SizedBox(height: 6),
              RichText(
                text: TextSpan(
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500, height: 1.5),
                  children: [
                    const TextSpan(text: 'Daftar gratis di '),
                    TextSpan(
                      text : 'openrouter.ai',
                      style: TextStyle(color: Colors.purple.shade600, fontWeight: FontWeight.bold),
                    ),
                    const TextSpan(text: ' → Settings → API Keys → Create Key.'),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              if (_hasOpenRouterApiKey)
                Container(
                  padding   : const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color       : Colors.green.shade50,
                    borderRadius: BorderRadius.circular(10),
                    border      : Border.all(color: Colors.green.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.check_circle_rounded, color: Colors.green.shade600, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'API key tersimpan di server',
                          style: TextStyle(fontWeight: FontWeight.w600, color: Colors.green.shade800),
                        ),
                      ),
                      TextButton(
                        onPressed: () => setState(() => _hasOpenRouterApiKey = false),
                        child    : const Text('Ganti'),
                      ),
                    ],
                  ),
                )
              else
                Container(
                  decoration: AppTheme.inputDecoration(Colors.purple),
                  child     : TextField(
                    controller  : _openRouterKeyController,
                    obscureText : _obscureApiKey,
                    style       : const TextStyle(fontSize: 14),
                    decoration  : InputDecoration(
                      hintText      : 'sk-or-v1-...',
                      hintStyle     : TextStyle(color: Colors.grey.shade400),
                      prefixIcon    : const Icon(Icons.key_rounded, color: Colors.purple, size: 20),
                      suffixIcon    : IconButton(
                        icon     : Icon(_obscureApiKey ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                        onPressed: () => setState(() => _obscureApiKey = !_obscureApiKey),
                      ),
                      border        : InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                    ),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // Model name card
        Container(
          padding   : const EdgeInsets.all(20),
          decoration: AppTheme.cardDecoration,
          child     : Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children          : [
              AppTheme.buildSectionHeader('Model Name', Icons.auto_awesome_rounded),
              const SizedBox(height: 6),
              Text(
                'Masukkan model ID dari openrouter.ai/models',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
              ),
              const SizedBox(height: 14),
              Container(
                decoration: AppTheme.inputDecoration(Colors.purple),
                child     : TextField(
                  controller : _openRouterModelController,
                  style      : const TextStyle(fontSize: 13),
                  decoration : InputDecoration(
                    hintText      : 'contoh: meta-llama/llama-3.3-70b-instruct:free',
                    hintStyle     : TextStyle(color: Colors.grey.shade400, fontSize: 12),
                    prefixIcon    : const Icon(Icons.psychology_rounded, color: Colors.purple, size: 20),
                    border        : InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Contoh model (tap untuk isi):',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing   : 8,
                runSpacing: 8,
                children  : [
                  _modelSuggestion('meta-llama/llama-3.3-70b-instruct:free', isFree: true),
                  _modelSuggestion('deepseek/deepseek-chat:free',             isFree: true),
                  _modelSuggestion('google/gemma-3-27b-it:free',              isFree: true),
                  _modelSuggestion('mistralai/mistral-7b-instruct:free',      isFree: true),
                  _modelSuggestion('anthropic/claude-3.5-sonnet',             isFree: false),
                  _modelSuggestion('openai/gpt-4o',                           isFree: false),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _modelSuggestion(String modelId, {required bool isFree}) {
    final isActive = _openRouterModelController.text == modelId;
    return GestureDetector(
      onTap: () => setState(() => _openRouterModelController.text = modelId),
      child: Container(
        padding   : const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color       : isActive
              ? Colors.purple.shade100
              : isFree ? Colors.green.shade50 : Colors.orange.shade50,
          borderRadius: BorderRadius.circular(8),
          border      : Border.all(
            color: isActive
                ? Colors.purple.shade400
                : isFree ? Colors.green.shade200 : Colors.orange.shade200,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children    : [
            Icon(
              isFree ? Icons.lock_open_rounded : Icons.paid_rounded,
              size : 12,
              color: isActive
                  ? Colors.purple.shade700
                  : isFree ? Colors.green.shade700 : Colors.orange.shade700,
            ),
            const SizedBox(width: 4),
            Text(
              modelId,
              style: TextStyle(
                fontSize  : 11,
                color     : isActive
                    ? Colors.purple.shade800
                    : isFree ? Colors.green.shade800 : Colors.orange.shade800,
                fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────
  // TEST SECTION
  // ─────────────────────────────────────────────

  Widget _buildTestSection() {
    return Container(
      padding   : const EdgeInsets.all(20),
      decoration: AppTheme.cardDecoration,
      child     : Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children          : [
          AppTheme.buildSectionHeader('Test Koneksi', Icons.wifi_tethering_rounded),
          const SizedBox(height: 6),
          Text(
            'Pastikan konfigurasi benar sebelum menyimpan.',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _isTesting ? null : _testConnection,
              icon     : _isTesting
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.bolt_rounded),
              label    : Text(_isTesting ? 'Testing...' : 'Test Sekarang'),
              style    : OutlinedButton.styleFrom(
                padding        : const EdgeInsets.symmetric(vertical: 14),
                side           : const BorderSide(color: AppTheme.primaryCoral),
                foregroundColor: AppTheme.primaryCoral,
                shape          : RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
          if (_testResult.isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding   : const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color       : _testSuccess ? Colors.green.shade50 : Colors.red.shade50,
                borderRadius: BorderRadius.circular(10),
                border      : Border.all(
                  color: _testSuccess ? Colors.green.shade200 : Colors.red.shade200,
                ),
              ),
              child: Text(
                _testResult,
                style: TextStyle(
                  color     : _testSuccess ? Colors.green.shade800 : Colors.red.shade800,
                  fontSize  : 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // SAVE BUTTON
  // ─────────────────────────────────────────────

  Widget _buildSaveButton() {
    return Container(
      width     : double.infinity,
      height    : 54,
      decoration: BoxDecoration(
        gradient    : AppTheme.accentGradient,
        borderRadius: BorderRadius.circular(16),
        boxShadow   : AppTheme.buttonShadow,
      ),
      child: ElevatedButton(
        onPressed: _isSaving ? null : _save,
        style    : ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          shadowColor    : Colors.transparent,
          shape          : RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: _isSaving
            ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
            : const Text('Simpan Settings', style: AppTheme.buttonText),
      ),
    );
  }
}