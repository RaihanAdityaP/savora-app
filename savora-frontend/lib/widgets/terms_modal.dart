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
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Syarat & Ketentuan',
                        style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white)),
                    SizedBox(height: 4),
                    Text('Harap dibaca dengan seksama',
                        style: TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w500)),
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
          Text('Terakhir diperbarui: 19 Maret 2026',
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
                          child: Text('Syarat Penggunaan Proxy AI Pihak Ketiga',
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
                        child: const Text('WAJIB DIBACA',
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
            'Savora menyediakan fitur opsional untuk menghubungkan layanan AI dari provider pihak ketiga. Dengan menggunakan fitur ini, Anda setuju dengan seluruh ketentuan berikut:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5),
          ),
          const SizedBox(height: 12),

          _proxyTermItem(
            num  : 'A',
            color: Colors.red.shade600,
            title: 'Tanggung Jawab Biaya Sepenuhnya pada Pengguna',
            desc : 'Savora tidak bertanggung jawab atas biaya, tagihan, kelebihan kuota, atau pembayaran apapun yang timbul dari penggunaan layanan AI pihak ketiga. Seluruh biaya penggunaan API (termasuk model berbayar) adalah tanggung jawab pengguna sepenuhnya. Savora tidak akan mengganti kerugian finansial apapun yang diakibatkan penggunaan fitur ini.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'B',
            color: Colors.purple.shade700,
            title: 'Tidak Ada Hubungan Resmi dengan Pihak Ketiga',
            desc : 'Savora tidak memiliki kemitraan, afiliasi, perjanjian kerja sama, atau hubungan resmi dalam bentuk apapun dengan provider AI pihak ketiga yang didukung oleh fitur ini, termasuk namun tidak terbatas pada OpenRouter dan penyedia serupa. Tampilan nama dan layanan pihak ketiga dalam aplikasi semata-mata untuk kemudahan konfigurasi pengguna dan bukan merupakan bentuk endorsement atau kerja sama resmi.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'C',
            color: Colors.orange.shade700,
            title: 'Persetujuan Risiko secara Eksplisit',
            desc : 'Dengan mengaktifkan dan menggunakan fitur proxy AI pihak ketiga, Anda secara sadar dan eksplisit menyetujui semua risiko yang mungkin timbul, mencakup: risiko kebocoran atau penyalahgunaan API key, ketidaktersediaan atau gangguan layanan pihak ketiga, perubahan harga atau kebijakan penyedia tanpa pemberitahuan, kehilangan data percakapan, serta konsekuensi finansial dari penggunaan yang tidak terkontrol. Savora tidak dapat dimintai pertanggungjawaban atas risiko-risiko tersebut.',
          ),
          const SizedBox(height: 10),

          _proxyTermItem(
            num  : 'D',
            color: Colors.blue.shade700,
            title: 'Kepatuhan terhadap Syarat Pihak Ketiga',
            desc : 'Pengguna wajib membaca, memahami, dan mematuhi syarat & ketentuan serta kebijakan penggunaan yang berlaku dari masing-masing provider AI pihak ketiga yang digunakan. Savora tidak bertanggung jawab atas pelanggaran terhadap ketentuan pihak ketiga yang dilakukan oleh pengguna.',
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
                    'Savora menyediakan layanan AI default (Groq) yang gratis dan dikelola oleh server. Fitur proxy pihak ketiga bersifat opsional dan diperuntukkan bagi pengguna yang ingin menggunakan model atau provider AI tertentu.',
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
      title: 'Penerimaan Ketentuan',
      text : 'Dengan mengakses dan menggunakan platform Savora, Anda menyetujui untuk terikat oleh syarat dan ketentuan ini. Jika Anda tidak setuju dengan ketentuan ini, mohon untuk tidak menggunakan layanan kami.',
    );
  }

  Widget _buildSection2() {
    final items = [
      'Resep masakan dan tutorial memasak',
      'Foto dan video yang diunggah',
      'Komentar dan ulasan',
      'Informasi profil',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _buildNumBadge('2', const Color(0xFF2A9D8F)),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Konten yang Diunggah',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Pengguna bertanggung jawab penuh atas konten yang mereka unggah, termasuk:',
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
      'Anda memiliki hak penuh atas konten yang diunggah, ATAU',
      'Anda telah mendapatkan izin yang sah untuk membagikan konten tersebut',
      'Konten tidak melanggar hak cipta pihak ketiga',
      'Anda bertanggung jawab penuh atas segala klaim hukum terkait konten yang Anda unggah',
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
                          child: Text('Hak Kekayaan Intelektual',
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
                        child: const Text('SANGAT PENTING',
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
                      child: const Text('TIDAK bertanggung jawab',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF7B0000))),
                    ),
                  ),
                  const TextSpan(
                      text: ' atas pelanggaran hak cipta, merek dagang, atau hak kekayaan intelektual lainnya yang dilakukan oleh pengguna.'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text('Dengan mengunggah konten, Anda menyatakan bahwa:',
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
      'Keakuratan informasi resep yang dibagikan pengguna',
      'Kerugian atau cedera yang timbul dari mengikuti resep',
      'Pelanggaran hak cipta atau kekayaan intelektual oleh pengguna',
      'Interaksi atau transaksi antar pengguna',
      'Kehilangan data atau konten',
      'Kerusakan perangkat akibat penggunaan platform',
      'Biaya atau kerugian finansial akibat penggunaan layanan AI pihak ketiga',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _buildNumBadge('4', const Color(0xFFF4A261)),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Pembatasan Tanggung Jawab',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Savora bertindak sebagai platform berbagi resep dan TIDAK bertanggung jawab atas:',
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
      'Melanggar hak cipta, merek dagang, atau hak kekayaan intelektual pihak lain',
      'Mengandung unsur SARA, pornografi, atau kekerasan',
      'Menyesatkan, palsu, atau penipuan',
      'Melanggar hukum yang berlaku di Indonesia',
      'Mengandung virus, malware, atau kode berbahaya',
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
              child: Text('Larangan Konten',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text('Pengguna dilarang mengunggah konten yang:',
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
        'title': 'Moderasi Konten',
        'text': 'Savora berhak untuk meninjau, menyetujui, menolak, atau menghapus konten yang dianggap melanggar syarat dan ketentuan ini tanpa pemberitahuan sebelumnya.'
      },
      {
        'num': '7', 'color': Colors.purple,
        'title': 'Akun Pengguna',
        'text': 'Anda bertanggung jawab untuk menjaga kerahasiaan akun, password, dan API key Anda. Savora tidak bertanggung jawab atas kerugian yang timbul dari akses tidak sah ke akun atau API key Anda.'
      },
      {
        'num': '8', 'color': Colors.pink,
        'title': 'Penghentian Layanan',
        'text': 'Savora berhak untuk menangguhkan atau menghentikan akses Anda ke platform jika terbukti melanggar syarat dan ketentuan ini, tanpa kompensasi apapun.'
      },
      {
        'num': '9', 'color': Colors.indigo,
        'title': 'Perubahan Ketentuan',
        'text': 'Savora berhak untuk mengubah syarat dan ketentuan ini sewaktu-waktu. Perubahan akan berlaku segera setelah dipublikasikan di platform.'
      },
      {
        'num': '10', 'color': Colors.blueGrey,
        'title': 'Hukum yang Berlaku',
        'text': 'Syarat dan ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia. Setiap sengketa akan diselesaikan di pengadilan yang berwenang di Indonesia.'
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
                    Text('DISCLAIMER PENTING',
                        style: TextStyle(fontWeight: FontWeight.bold, color: Colors.amber.shade900, fontSize: 13)),
                    const SizedBox(height: 6),
                    RichText(
                      text: TextSpan(
                        style: TextStyle(fontSize: 13, color: Colors.amber.shade800, height: 1.5),
                        children: [
                          const TextSpan(text: 'Dengan menggunakan Savora, Anda '),
                          const TextSpan(
                            text: 'membebaskan platform dan pengelolanya',
                            style: TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const TextSpan(
                              text: ' dari segala tuntutan hukum terkait konten yang diunggah oleh pengguna, termasuk namun tidak terbatas pada pelanggaran hak cipta, cedera, atau kerugian material.'),
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
                    'Khusus fitur proxy AI: Savora tidak bertanggung jawab atas biaya penggunaan, gangguan layanan, atau kerugian apapun yang timbul dari penggunaan provider AI pihak ketiga. Penggunaan fitur ini sepenuhnya atas risiko dan tanggung jawab Anda.',
                    style: TextStyle(fontSize: 12, color: Colors.amber.shade900, height: 1.5, fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Kontak
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
                        const TextSpan(text: 'Pertanyaan? Hubungi kami: '),
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
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.shield_rounded, color: Colors.white, size: 20),
              SizedBox(width: 10),
              Text('Saya Mengerti & Menyetujui',
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
        ),
      ),
    );
  }
}