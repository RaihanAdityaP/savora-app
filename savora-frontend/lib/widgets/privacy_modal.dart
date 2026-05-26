import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class PrivacyModal extends StatefulWidget {
  final VoidCallback onClose;
  final VoidCallback? onAccept;

  const PrivacyModal({super.key, required this.onClose, this.onAccept});

  static Future<bool?> show(BuildContext context) {
    return showDialog<bool>(
      context: context,
      barrierDismissible: true,
      barrierColor: Colors.black.withValues(alpha: 0.7),
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: PrivacyModal(
          onClose: () => Navigator.of(ctx).pop(false),
          onAccept: () => Navigator.of(ctx).pop(true),
        ),
      ),
    );
  }

  @override
  State<PrivacyModal> createState() => _PrivacyModalState();
}

class _PrivacyModalState extends State<PrivacyModal>
    with SingleTickerProviderStateMixin {
  late AnimationController _animController;
  late Animation<double> _scaleAnim;
  late Animation<double> _fadeAnim;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      duration: const Duration(milliseconds: 250),
      vsync: this,
    );
    _scaleAnim = Tween<double>(begin: 0.85, end: 1.0).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeOutBack),
    );
    _fadeAnim = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(parent: _animController, curve: Curves.easeIn));
    _animController.forward();
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnim,
      child: ScaleTransition(
        scale: _scaleAnim,
        child: Container(
          constraints: const BoxConstraints(maxWidth: 600, maxHeight: 720),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.3),
                blurRadius: 30,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildHeader(),
                Flexible(child: _buildContent()),
                _buildFooter(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF2A9D8F), Color(0xFF264653), Color(0xFF1a5c54)],
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            top: -40,
            right: -40,
            child: Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.1),
              ),
            ),
          ),
          Positioned(
            bottom: -30,
            left: -30,
            child: Container(
              width: 90,
              height: 90,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.1),
              ),
            ),
          ),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(
                  Icons.lock_rounded,
                  color: Colors.white,
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Privacy Policy',
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Protecting your data is our priority',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Colors.white,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              GestureDetector(
                onTap: widget.onClose,
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.close, color: Colors.white, size: 20),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildContent() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildLastUpdated(const Color(0xFF2A9D8F)),
          const SizedBox(height: 20),
          _buildSection1(),
          const SizedBox(height: 20),
          _buildSection2(),
          const SizedBox(height: 20),
          _buildDataNotSoldSection(),
          const SizedBox(height: 20),
          _buildSecuritySection(),
          const SizedBox(height: 20),
          _buildUserRightsSection(),
          const SizedBox(height: 20),
          // ── SECTION BARU: Penggunaan Proxy AI Pihak Ketiga ──
          _buildThirdPartyProxySection(),
          const SizedBox(height: 20),
          ..._buildCondensedSections(),
          const SizedBox(height: 20),
          _buildContactSection(),
          const SizedBox(height: 20),
          _buildCommitmentBanner(),
        ],
      ),
    );
  }

  Widget _buildLastUpdated(Color dotColor) {
    return Container(
      padding: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(
            'Last updated: March 19, 2026',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // SECTION BARU: PROXY AI PIHAK KETIGA
  // ─────────────────────────────────────────────
  Widget _buildThirdPartyProxySection() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.orange.shade50, Colors.amber.shade50],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.orange.shade300, width: 2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.orange.shade600,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.route_rounded,
                  color: Colors.white,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'Third-Party AI Proxy Usage',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF264653),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: Colors.orange.shade600,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  'IMPORTANT',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Intro
          Text(
            'Savora provides an optional feature to connect third-party AI services (such as OpenRouter) through proxy settings. By using this feature, you understand and agree to the following:',
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey.shade700,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 14),

          // Poin 1: Tidak bertanggung jawab atas pembayaran
          _proxyPolicyItem(
            icon: Icons.credit_card_off_rounded,
            color: Colors.red.shade600,
            title: 'No Payment Responsibility',
            desc:
                'Savora is not responsible for any costs, charges, or payments arising from third-party AI services. Users are fully responsible for managing credits, quotas, and fees charged by the selected provider.',
          ),
          const SizedBox(height: 10),

          // Poin 2: Tidak ada kerjasama
          _proxyPolicyItem(
            icon: Icons.handshake_outlined,
            color: Colors.purple.shade600,
            title: 'No Official Relationship',
            desc:
                'Savora tidak memiliki kemitraan, afiliasi, kerja sama, atau hubungan resmi apapun dengan layanan AI pihak ketiga yang dapat dikonfigurasi melalui fitur ini (termasuk namun tidak terbatas pada OpenRouter, dan penyedia lainnya). Nama dan logo pihak ketiga hanya ditampilkan sebagai referensi pengguna.',
          ),
          const SizedBox(height: 10),

          // Poin 3: Persetujuan risiko
          _proxyPolicyItem(
            icon: Icons.warning_amber_rounded,
            color: Colors.orange.shade700,
            title: 'User Risk Consent',
            desc:
                'By enabling and using the third-party AI proxy feature, you explicitly accept all risks that may arise, including API key security risks, service unavailability, provider policy changes, and financial consequences from using paid models.',
          ),
          const SizedBox(height: 10),

          // Poin 4: Data ke pihak ketiga
          _proxyPolicyItem(
            icon: Icons.send_rounded,
            color: Colors.blue.shade600,
            title: 'Pengiriman Data ke Pihak Ketiga',
            desc:
                'When using a third-party proxy, messages and content you send will be forwarded to the relevant provider servers. Savora cannot guarantee how that data is processed, stored, or used by third parties. Please read the provider privacy policy before using it.',
          ),
          const SizedBox(height: 14),

          // Banner persetujuan
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.orange.shade100,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.orange.shade300),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  Icons.info_rounded,
                  color: Colors.orange.shade700,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'This feature is optional. Savora provides Groq as the default free AI service managed by the server without additional user configuration.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.orange.shade800,
                      height: 1.5,
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

  Widget _proxyPolicyItem({
    required IconData icon,
    required Color color,
    required String title,
    required String desc,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.85),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.orange.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  desc,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade700,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection1() {
    final items = [
      {
        'icon': Icons.person_outline,
        'title': 'Account Information',
        'desc': 'Email, username, full name, profile photo',
      },
      {
        'icon': Icons.visibility_outlined,
        'title': 'Content',
        'desc': 'Recipes, photos, videos, comments, and reviews you upload',
      },
      {
        'icon': Icons.share_outlined,
        'title': 'Activity',
        'desc': 'Recipes you save, follow activity, and ratings you submit',
      },
      {
        'icon': Icons.storage_outlined,
        'title': 'Technical Data',
        'desc': 'IP address, browser, device, and activity logs',
      },
      {
        'icon': Icons.cookie_outlined,
        'title': 'Cookies',
        'desc': 'Data used to keep login sessions and preferences',
      },
    ];

    return _buildSectionCard(
      icon: Icons.storage_outlined,
      iconBg: const Color(0xFF2A9D8F),
      title: 'Information We Collect',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'We collect the following information when you use Savora:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 12),
          ...items.map(
            (item) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    const Color(0xFF2A9D8F).withValues(alpha: 0.05),
                    Colors.transparent,
                  ],
                ),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: const Color(0xFF2A9D8F).withValues(alpha: 0.15),
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: const Color(0xFF2A9D8F).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      item['icon'] as IconData,
                      color: const Color(0xFF2A9D8F),
                      size: 16,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item['title'] as String,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF264653),
                          ),
                        ),
                        Text(
                          item['desc'] as String,
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection2() {
    final items = [
      'Menyediakan dan meningkatkan layanan platform',
      'Process authentication and account security',
      'Menampilkan konten yang relevan dan personal',
      'Mengirim notifikasi terkait aktivitas akun',
      'Menganalisis penggunaan platform untuk perbaikan',
      'Mencegah penyalahgunaan dan aktivitas ilegal',
      'Mematuhi kewajiban hukum',
    ];

    return _buildSectionCard(
      icon: Icons.visibility_outlined,
      iconBg: Colors.blue,
      title: 'Use of Information',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'We use the information we collect to:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: items
                .map(
                  (item) => Container(
                    margin: const EdgeInsets.only(bottom: 6),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(top: 5),
                          child: Container(
                            width: 5,
                            height: 5,
                            decoration: const BoxDecoration(
                              color: Colors.blue,
                              shape: BoxShape.circle,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            item,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
                .toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildDataNotSoldSection() {
    final items = [
      {
        'title': 'Public Content',
        'desc':
            'Recipes, profiles, and comments you publish may be visible to other users',
      },
      {
        'title': 'Service Providers',
        'desc':
            'Supabase (database), Vercel (hosting), and other trusted third-party services',
      },
      {
        'title': 'Legal Obligations',
        'desc': 'When requested by authorized authorities',
      },
      {
        'title': 'Rights Protection',
        'desc':
            'To protect the rights, property, or safety of Savora and its users',
      },
    ];

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Colors.green.shade50,
            Colors.green.shade50.withValues(alpha: 0.5),
          ],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.green.shade200, width: 2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.green,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.share_outlined,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'Information Sharing',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF264653),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: Colors.green,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  'NOT SOLD',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.green.shade100,
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Text(
              'We do NOT sell your personal data.',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: Color(0xFF1a5c2a),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Your information may be shared in the following situations:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 8),
          ...items.map(
            (item) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.8),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.green.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['title']!,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF264653),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    item['desc']!,
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSecuritySection() {
    return _buildSectionCard(
      icon: Icons.shield_outlined,
      iconBg: Colors.purple,
      title: 'Data Security',
      child: Text(
        'We apply reasonable security measures to protect your data, including encryption, access controls, and security monitoring. However, no system is 100% secure from cyberattacks. You are responsible for keeping your account password and API keys confidential.',
        style: TextStyle(
          fontSize: 13,
          color: Colors.grey.shade700,
          height: 1.5,
        ),
      ),
    );
  }

  Widget _buildUserRightsSection() {
    final rights = [
      {'title': 'Access', 'desc': 'View the personal data we store'},
      {'title': 'Correction', 'desc': 'Update inaccurate information'},
      {'title': 'Deletion', 'desc': 'Delete your account and personal data'},
      {
        'title': 'Portability',
        'desc': 'Download your data in a structured format',
      },
      {'title': 'Objection', 'desc': 'Object to certain data processing'},
    ];

    return _buildSectionCard(
      icon: Icons.person_outline,
      iconBg: Colors.indigo,
      title: 'User Rights',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'You have the right to:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 10),
          ...rights.asMap().entries.map(
            (entry) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.indigo.shade50,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: Colors.indigo,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Center(
                      child: Text(
                        '${entry.key + 1}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          entry.value['title']!,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF264653),
                          ),
                        ),
                        Text(
                          entry.value['desc']!,
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'To exercise these rights, contact us by email or through the platform contact feature.',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade500,
              fontStyle: FontStyle.italic,
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _buildCondensedSections() {
    final sections = [
      {
        'icon': Icons.access_time,
        'color': Colors.orange,
        'title': 'Data Retention',
        'text':
            'We retain your data while your account is active and for a reasonable period afterward for legal and security purposes. Data that is no longer needed will be deleted periodically.',
      },
      {
        'icon': Icons.cookie_outlined,
        'color': Colors.amber,
        'title': 'Cookies and Tracking Technologies',
        'text':
            'We use cookies to maintain login sessions, remember preferences, and analyze platform usage. You can configure your browser to reject cookies, but some features may not work properly.',
      },
      {
        'icon': Icons.child_care,
        'color': Colors.pink,
        'title': 'Children\'s Privacy',
        'text':
            'This platform is not intended for children under 13. We do not knowingly collect personal data from children. If we become aware of children\'s data, we will delete it promptly.',
      },
      {
        'icon': Icons.visibility_outlined,
        'color': Colors.blueGrey,
        'title': 'Policy Changes',
        'text':
            'We may update this privacy policy from time to time. Significant changes will be communicated through email or platform notifications. Continued use of the platform after changes means you accept the updated policy.',
      },
    ];

    return sections
        .map(
          (s) => Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: _buildSectionCard(
              icon: s['icon'] as IconData,
              iconBg: s['color'] as Color,
              title: s['title'] as String,
              child: Text(
                s['text'] as String,
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey.shade700,
                  height: 1.5,
                ),
              ),
            ),
          ),
        )
        .toList();
  }

  Widget _buildContactSection() {
    return _buildSectionCard(
      icon: Icons.mail_outline,
      iconBg: const Color(0xFF2A9D8F),
      title: 'Contact',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'If you have questions about this privacy policy, please contact us:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 10),
          _contactRow(Icons.mail_outline, 'Email', 'adminsavora@gmail.com'),
          const SizedBox(height: 8),
          _contactRow(Icons.language, 'Website', 'savora-app.up.railway.app'),
        ],
      ),
    );
  }

  Widget _contactRow(IconData icon, String label, String value) {
    final isWebsite = label == 'Website';
    return GestureDetector(
      onTap: isWebsite
          ? () => launchUrl(
              Uri.parse('https://$value'),
              mode: LaunchMode.externalApplication,
            )
          : null,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF2A9D8F).withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          children: [
            Icon(icon, color: const Color(0xFF2A9D8F), size: 20),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey.shade500,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    value,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: isWebsite
                          ? const Color(0xFF2A9D8F)
                          : const Color(0xFF264653),
                      decoration: isWebsite
                          ? TextDecoration.underline
                          : TextDecoration.none,
                      decorationColor: isWebsite
                          ? const Color(0xFF2A9D8F)
                          : null,
                    ),
                  ),
                ],
              ),
            ),
            if (isWebsite)
              const Icon(Icons.open_in_new, size: 14, color: Color(0xFF2A9D8F)),
          ],
        ),
      ),
    );
  }

  Widget _buildCommitmentBanner() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.blue.shade50, Colors.cyan.shade50],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.blue.shade300, width: 2),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.blue,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.lock_rounded,
              color: Colors.white,
              size: 24,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Our Commitment',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.blue.shade900,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'We are committed to protecting your privacy and using your data responsibly under applicable Indonesian law.',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.blue.shade800,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard({
    required IconData icon,
    required Color iconBg,
    required String title,
    required Widget child,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: iconBg.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: iconBg, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF264653),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        child,
      ],
    );
  }

  Widget _buildFooter() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.grey.shade50, Colors.grey.shade100],
        ),
        border: Border(top: BorderSide(color: Colors.grey.shade200, width: 2)),
      ),
      child: GestureDetector(
        onTap: () =>
            widget.onAccept != null ? widget.onAccept!() : widget.onClose(),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF2A9D8F), Color(0xFF264653)],
            ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF2A9D8F).withValues(alpha: 0.4),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.lock_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 10),
              Text(
                'I Understand & Agree',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
