import 'package:flutter/material.dart';

class TermsModal extends StatefulWidget {
  final VoidCallback onClose;
  final VoidCallback? onAccept;

  const TermsModal({
    super.key,
    required this.onClose,
    this.onAccept,
  });

  static Future<bool?> show(BuildContext context) {
    return showDialog<bool>(
      context: context,
      barrierDismissible: true,
      barrierColor: Colors.black.withValues(alpha: 0.7),
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: TermsModal(
          onClose: () => Navigator.of(ctx).pop(false),
          onAccept: () => Navigator.of(ctx).pop(true),
        ),
      ),
    );
  }

  @override
  State<TermsModal> createState() => _TermsModalState();
}

class _TermsModalState extends State<TermsModal>
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
    _fadeAnim = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeIn),
    );
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
          colors: [Color(0xFFE76F51), Color(0xFFF4A261), Color(0xFFE9C46A)],
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            top: -40, right: -40,
            child: Container(
              width: 120, height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.1),
              ),
            ),
          ),
          Positioned(
            bottom: -30, left: -30,
            child: Container(
              width: 90, height: 90,
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
                child: const Icon(Icons.description_rounded, color: Colors.white, size: 28),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Terms & Conditions',
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white)),
                    const SizedBox(height: 4),
                    Text('Please read carefully',
                        style: const TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w500)),
                  ],
                ),
              ),
              GestureDetector(
                onTap: widget.onClose,
                child: Container(
                  width: 40, height: 40,
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
          _buildLastUpdated(),
          const SizedBox(height: 20),
          _buildSection1(),
          const SizedBox(height: 20),
          _buildSection2(),
          const SizedBox(height: 20),
          _buildIPSection(),
          const SizedBox(height: 20),
          _buildLiabilitySection(),
          const SizedBox(height: 20),
          _buildProhibitedSection(),
          const SizedBox(height: 20),
          // ── SECTION BARU: Syarat Penggunaan Proxy AI ──
          _buildProxyTermsSection(),
          const SizedBox(height: 20),
          ..._buildOtherSections(),
          const SizedBox(height: 20),
          _buildDisclaimerBanner(),
        ],
      ),
    );
  }

  Widget _buildLastUpdated() {
    return Container(
      padding: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        children: [
          Container(
            width: 8, height: 8,
            decoration: const BoxDecoration(color: Color(0xFFE76F51), shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text('Last updated: March 19, 2026',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // SECTION BARU: SYARAT PROXY AI
  // ─────────────────────────────────────────────
  Widget _buildProxyTermsSection() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.deepPurple.shade50, Colors.purple.shade50],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.purple.shade300, width: 2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildNumBadge('5', Colors.purple.shade600),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.route_rounded, color: Colors.purple.shade600, size: 18),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text('Third-Party AI Proxy Terms',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.purple.shade700)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.purple.shade600,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text('MUST READ',
                            style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          Text(
            'Savora provides an optional feature to connect AI services from third-party providers. By using this feature, you agree to all of the following terms:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5),
          ),
          const SizedBox(height: 12),

          _proxyTermItem(
            num  : 'A',
            color: Colors.red.shade600,
            title: 'Costs Are Fully the User\'s Responsibility',
            desc : 'Savora tidak bertanggung jawab atas biaya, tagihan, kelebihan kuota, atau pembayaran apapun yang timbul dari penggunaan layanan AI pihak ketiga. Seluruh biaya penggunaan API (termasuk model berbayar) adalah tanggung jawab pengguna sepenuhnya. Savora tidak akan mengganti kerugian finansial apapun yang diakibatkan penggunaan fitur ini.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'B',
            color: Colors.purple.shade700,
            title: 'No Official Relationship with Third Parties',
            desc : 'Savora tidak memiliki kemitraan, afiliasi, perjanjian kerja sama, atau hubungan resmi dalam bentuk apapun dengan provider AI pihak ketiga yang didukung oleh fitur ini, termasuk namun tidak terbatas pada OpenRouter dan penyedia serupa. Tampilan nama dan layanan pihak ketiga dalam aplikasi semata-mata untuk kemudahan konfigurasi pengguna dan bukan merupakan bentuk endorsement atau kerja sama resmi.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'C',
            color: Colors.orange.shade700,
            title: 'Persetujuan Risiko secara Eksplisit',
            desc : 'By enabling and using the third-party AI proxy feature, you knowingly and explicitly accept all risks that may arise, including API key leakage or misuse, third-party service unavailability or disruption, provider price or policy changes without notice, conversation data loss, and financial consequences from uncontrolled usage. Savora cannot be held responsible for these risks.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'D',
            color: Colors.blue.shade700,
            title: 'Compliance with Third-Party Terms',
            desc : 'Users must read, understand, and comply with the terms, conditions, and usage policies of each third-party AI provider they use. Savora is not responsible for user violations of third-party terms.',
          ),
          const SizedBox(height: 14),

          // Banner info
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.purple.shade50,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.purple.shade200),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.lightbulb_outline_rounded, color: Colors.purple.shade600, size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Savora provides a free default AI service (Groq) managed by the server. The third-party proxy feature is optional and intended for users who want to use specific AI models or providers.',
                    style: TextStyle(fontSize: 12, color: Colors.purple.shade700, height: 1.5),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _proxyTermItem({
    required String num,
    required Color  color,
    required String title,
    required String desc,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.85),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.purple.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 26, height: 26,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(7),
            ),
            child: Center(
              child: Text(num,
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color)),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: color)),
                const SizedBox(height: 4),
                Text(desc,
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade700, height: 1.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection1() {
    return _buildSimpleSection(
      num  : '1',
      color: const Color(0xFFE76F51),
      title: 'Acceptance of Terms',
      text : 'By accessing and using the Savora platform, you agree to be bound by these terms and conditions. If you do not agree, please do not use our services.',
    );
  }

  Widget _buildSection2() {
    final items = [
      'Cooking recipes and tutorials',
      'Uploaded photos and videos',
      'Comments and reviews',
      'Profile information',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _buildNumBadge('2', const Color(0xFF2A9D8F)),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Uploaded Content',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Users are fully responsible for the content they upload, including:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8, runSpacing: 8,
          children: items.map((item) => Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(8)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 5, height: 5,
                  decoration: const BoxDecoration(color: Color(0xFF2A9D8F), shape: BoxShape.circle),
                ),
                const SizedBox(width: 6),
                Text(item, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
              ],
            ),
          )).toList(),
        ),
      ],
    );
  }

  Widget _buildIPSection() {
    final items = [
      'You have full rights to the uploaded content, OR',
      'You have obtained valid permission to share the content',
      'The content does not infringe third-party copyrights',
      'You are fully responsible for any legal claims related to content you upload',
    ];

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.red.shade50, Colors.orange.shade50],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red.shade200, width: 2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildNumBadge('3', Colors.red),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.shield_outlined, color: Colors.red, size: 18),
                        const SizedBox(width: 6),
                        const Expanded(
                          child: Text('Intellectual Property Rights',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.red)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: Colors.red, borderRadius: BorderRadius.circular(20)),
                        child: const Text('VERY IMPORTANT',
                            style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.red.shade200),
            ),
            child: RichText(
              text: TextSpan(
                style: const TextStyle(fontSize: 13, color: Color(0xFF7B0000), fontWeight: FontWeight.w600, height: 1.5),
                children: [
                  const TextSpan(text: 'Savora '),
                  WidgetSpan(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(color: Colors.red.shade200, borderRadius: BorderRadius.circular(4)),
                      child: const Text('not responsible',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF7B0000))),
                    ),
                  ),
                  const TextSpan(
                      text: ' for copyright, trademark, or other intellectual property violations committed by users.'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text('By uploading content, you represent that:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 8),
          ...items.map((item) => Container(
                margin: const EdgeInsets.only(bottom: 6),
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.8),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Padding(
                      padding: EdgeInsets.only(top: 1),
                      child: Icon(Icons.warning_amber_rounded, color: Colors.red, size: 16),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(item, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }

  Widget _buildLiabilitySection() {
    final items = [
      'The accuracy of recipe information shared by users',
      'Losses or injuries arising from following recipes',
      'Copyright or intellectual property violations by users',
      'Interactions or transactions between users',
      'Loss of data or content',
      'Device damage caused by platform use',
      'Costs or financial losses caused by third-party AI services',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _buildNumBadge('4', const Color(0xFFF4A261)),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Limitation of Liability',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Savora acts as a recipe-sharing platform and is NOT responsible for:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
        const SizedBox(height: 8),
        ...items.map((item) => Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 2),
                    child: Icon(Icons.close, color: Colors.grey, size: 14),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(item, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _buildProhibitedSection() {
    final items = [
      'Infringes another party copyright, trademark, or intellectual property rights',
      'Contains hate, pornography, or violence',
      'Is misleading, false, or fraudulent',
      'Violates applicable Indonesian law',
      'Contains viruses, malware, or harmful code',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 32, height: 32,
              decoration: BoxDecoration(color: Colors.red.shade100, borderRadius: BorderRadius.circular(8)),
              child: const Icon(Icons.block_rounded, color: Colors.red, size: 18),
            ),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Prohibited Content',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Users are prohibited from uploading content that:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
        const SizedBox(height: 8),
        ...items.map((item) => Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.red.shade200),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 2),
                    child: Icon(Icons.block_rounded, color: Colors.red, size: 14),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(item, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  List<Widget> _buildOtherSections() {
    final sections = [
      {
        'num': '6', 'color': Colors.blue,
        'title': 'Content Moderation',
        'text': 'Savora has the right to review, approve, reject, or remove content considered to violate these terms and conditions without prior notice.'
      },
      {
        'num': '7', 'color': Colors.purple,
        'title': 'User Account',
        'text': 'You are responsible for keeping your account, password, and API keys confidential. Savora is not responsible for losses caused by unauthorized access to your account or API keys.'
      },
      {
        'num': '8', 'color': Colors.pink,
        'title': 'Service Termination',
        'text': 'Savora reserves the right to suspend or terminate your access to the platform if you are proven to violate these terms and conditions, without any compensation.'
      },
      {
        'num': '9', 'color': Colors.indigo,
        'title': 'Changes to Terms',
        'text': 'Savora reserves the right to change these terms and conditions at any time. Changes take effect immediately after being published on the platform.'
      },
      {
        'num': '10', 'color': Colors.blueGrey,
        'title': 'Governing Law',
        'text': 'These terms and conditions are governed by and interpreted under the laws of the Republic of Indonesia. Any dispute will be resolved in the competent courts of Indonesia.'
      },
    ];

    return sections.map((s) => Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: _buildSimpleSection(
        num  : s['num'] as String,
        color: s['color'] as Color,
        title: s['title'] as String,
        text : s['text'] as String,
      ),
    )).toList();
  }

  Widget _buildDisclaimerBanner() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.yellow.shade50, Colors.amber.shade50],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.amber.shade300, width: 2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 48, height: 48,
                decoration: BoxDecoration(color: Colors.amber, borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.warning_rounded, color: Colors.white, size: 26),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('IMPORTANT DISCLAIMER',
                        style: TextStyle(fontWeight: FontWeight.bold, color: Colors.amber.shade900, fontSize: 13)),
                    const SizedBox(height: 6),
                    RichText(
                      text: TextSpan(
                        style: TextStyle(fontSize: 13, color: Colors.amber.shade800, height: 1.5),
                        children: [
                          const TextSpan(text: 'By using Savora, you '),
                          const TextSpan(
                            text: 'release the platform and its operators',
                            style: TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const TextSpan(
                              text: ' from any legal claims related to user-uploaded content, including but not limited to copyright infringement, injury, or material loss.'),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Tambahan disclaimer proxy
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.amber.shade100,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.amber.shade400),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.route_rounded, color: Colors.amber.shade800, size: 16),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'For the AI proxy feature specifically: Savora is not responsible for usage costs, service interruptions, or any losses arising from third-party AI providers. Use of this feature is entirely at your own risk and responsibility.',
                    style: TextStyle(fontSize: 12, color: Colors.amber.shade900, height: 1.5, fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Contact
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.7),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                Icon(Icons.mail_outline, color: Colors.amber.shade800, size: 16),
                const SizedBox(width: 8),
                Expanded(
                  child: RichText(
                    text: TextSpan(
                      style: TextStyle(fontSize: 12, color: Colors.amber.shade800),
                      children: [
                        const TextSpan(text: 'Questions? Contact us: '),
                        TextSpan(
                          text: 'adminsavora@gmail.com',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
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

  Widget _buildSimpleSection({
    required String num,
    required Color  color,
    required String title,
    required String text,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _buildNumBadge(num, color),
            const SizedBox(width: 12),
            Expanded(
              child: Text(title,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.only(left: 44),
          child: Text(text,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5)),
        ),
      ],
    );
  }

  Widget _buildNumBadge(String num, Color color) {
    return Container(
      width: 32, height: 32,
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Center(
        child: Text(num,
            style: TextStyle(color: color, fontSize: 13, fontWeight: FontWeight.bold)),
      ),
    );
  }

  Widget _buildFooter() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [Colors.grey.shade50, Colors.grey.shade100]),
        border: Border(top: BorderSide(color: Colors.grey.shade200, width: 2)),
      ),
      child: GestureDetector(
        onTap: () => widget.onAccept != null ? widget.onAccept!() : widget.onClose(),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFFE76F51), Color(0xFFF4A261)]),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFFE76F51).withValues(alpha: 0.4),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.shield_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 10),
              Text('I Understand & Agree',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
        ),
      ),
    );
  }
}
