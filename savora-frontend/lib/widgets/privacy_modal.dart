import 'package:flutter/material.dart';

class PrivacyModal extends StatefulWidget {
  final VoidCallback onClose;
  final VoidCallback? onAccept;

  const PrivacyModal({
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
          colors: [Color(0xFF2A9D8F), Color(0xFF264653), Color(0xFF1a5c54)],
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
                child: const Icon(Icons.lock_rounded, color: Colors.white, size: 28),
              ),
              const SizedBox(width: 16),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Kebijakan Privasi',
                        style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white)),
                    SizedBox(height: 4),
                    Text('Perlindungan data Anda adalah prioritas kami',
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
            width: 8, height: 8,
            decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(
            'Terakhir diperbarui: 19 Maret 2026',
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
                child: const Icon(Icons.route_rounded, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'Penggunaan Proxy AI Pihak Ketiga',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF264653),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.orange.shade600,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text('PENTING',
                    style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Intro
          Text(
            'Savora menyediakan fitur opsional untuk menghubungkan layanan AI pihak ketiga (seperti OpenRouter) melalui pengaturan proxy. Dengan menggunakan fitur ini, Anda memahami dan menyetujui hal-hal berikut:',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5),
          ),
          const SizedBox(height: 14),

          // Poin 1: Tidak bertanggung jawab atas pembayaran
          _proxyPolicyItem(
            icon: Icons.credit_card_off_rounded,
            color: Colors.red.shade600,
            title: 'Tidak Ada Tanggung Jawab Pembayaran',
            desc: 'Savora tidak bertanggung jawab atas biaya, tagihan, atau pembayaran apapun yang timbul dari penggunaan layanan AI pihak ketiga. Pengguna sepenuhnya bertanggung jawab atas pengelolaan kredit, kuota, dan biaya yang dikenakan oleh provider yang dipilih.',
          ),
          const SizedBox(height: 10),

          // Poin 2: Tidak ada kerjasama
          _proxyPolicyItem(
            icon: Icons.handshake_outlined,
            color: Colors.purple.shade600,
            title: 'Tidak Ada Hubungan Resmi',
            desc: 'Savora tidak memiliki kemitraan, afiliasi, kerja sama, atau hubungan resmi apapun dengan layanan AI pihak ketiga yang dapat dikonfigurasi melalui fitur ini (termasuk namun tidak terbatas pada OpenRouter, dan penyedia lainnya). Nama dan logo pihak ketiga hanya ditampilkan sebagai referensi pengguna.',
          ),
          const SizedBox(height: 10),

          // Poin 3: Persetujuan risiko
          _proxyPolicyItem(
            icon: Icons.warning_amber_rounded,
            color: Colors.orange.shade700,
            title: 'Persetujuan Risiko Pengguna',
            desc: 'Dengan mengaktifkan dan menggunakan fitur proxy AI pihak ketiga, Anda secara eksplisit menyetujui segala risiko yang mungkin timbul, termasuk: risiko keamanan API key, ketidaktersediaan layanan, perubahan kebijakan penyedia, dan konsekuensi finansial dari penggunaan model berbayar.',
          ),
          const SizedBox(height: 10),

          // Poin 4: Data ke pihak ketiga
          _proxyPolicyItem(
            icon: Icons.send_rounded,
            color: Colors.blue.shade600,
            title: 'Pengiriman Data ke Pihak Ketiga',
            desc: 'Saat menggunakan proxy pihak ketiga, pesan dan konten yang Anda kirim akan diteruskan ke server penyedia yang bersangkutan. Savora tidak dapat menjamin bagaimana data tersebut diproses, disimpan, atau digunakan oleh pihak ketiga. Harap baca kebijakan privasi penyedia sebelum menggunakannya.',
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
                Icon(Icons.info_rounded, color: Colors.orange.shade700, size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Penggunaan fitur ini bersifat opsional. Savora menyediakan Groq sebagai layanan AI default yang gratis dan dikelola oleh server tanpa konfigurasi tambahan dari pengguna.',
                    style: TextStyle(fontSize: 12, color: Colors.orange.shade800, height: 1.5),
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
                Text(title,
                    style: TextStyle(
                        fontSize: 13, fontWeight: FontWeight.bold, color: color)),
                const SizedBox(height: 3),
                Text(desc,
                    style: TextStyle(
                        fontSize: 12, color: Colors.grey.shade700, height: 1.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection1() {
    final items = [
      {'icon': Icons.person_outline,    'title': 'Informasi Akun',   'desc': 'Email, username, nama lengkap, foto profil'},
      {'icon': Icons.visibility_outlined,'title': 'Konten',           'desc': 'Resep, foto, video, komentar, dan ulasan yang Anda unggah'},
      {'icon': Icons.share_outlined,    'title': 'Aktivitas',        'desc': 'Resep yang Anda simpan, ikuti, dan rating yang diberikan'},
      {'icon': Icons.storage_outlined,  'title': 'Data Teknis',      'desc': 'Alamat IP, browser, perangkat, dan log aktivitas'},
      {'icon': Icons.cookie_outlined,   'title': 'Cookies',          'desc': 'Data untuk menjaga sesi login dan preferensi'},
    ];

    return _buildSectionCard(
      icon: Icons.storage_outlined,
      iconBg: const Color(0xFF2A9D8F),
      title: 'Informasi yang Kami Kumpulkan',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Kami mengumpulkan informasi berikut saat Anda menggunakan Savora:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 12),
          ...items.map((item) => Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [
                    const Color(0xFF2A9D8F).withValues(alpha: 0.05),
                    Colors.transparent
                  ]),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFF2A9D8F).withValues(alpha: 0.15)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 32, height: 32,
                      decoration: BoxDecoration(
                        color: const Color(0xFF2A9D8F).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(item['icon'] as IconData, color: const Color(0xFF2A9D8F), size: 16),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(item['title'] as String,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
                          Text(item['desc'] as String,
                              style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                        ],
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }

  Widget _buildSection2() {
    final items = [
      'Menyediakan dan meningkatkan layanan platform',
      'Memproses autentikasi dan keamanan akun',
      'Menampilkan konten yang relevan dan personal',
      'Mengirim notifikasi terkait aktivitas akun',
      'Menganalisis penggunaan platform untuk perbaikan',
      'Mencegah penyalahgunaan dan aktivitas ilegal',
      'Mematuhi kewajiban hukum',
    ];

    return _buildSectionCard(
      icon: Icons.visibility_outlined,
      iconBg: Colors.blue,
      title: 'Penggunaan Informasi',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Informasi yang kami kumpulkan digunakan untuk:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: items.map((item) => Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
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
                      width: 5, height: 5,
                      decoration: const BoxDecoration(color: Colors.blue, shape: BoxShape.circle),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(child: Text(item, style: TextStyle(fontSize: 12, color: Colors.grey.shade700))),
                ],
              ),
            )).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildDataNotSoldSection() {
    final items = [
      {'title': 'Konten Publik',       'desc': 'Resep, profil, dan komentar yang Anda publikasikan dapat dilihat pengguna lain'},
      {'title': 'Penyedia Layanan',    'desc': 'Supabase (database), Vercel (hosting), dan layanan pihak ketiga terpercaya lainnya'},
      {'title': 'Kewajiban Hukum',     'desc': 'Jika diminta oleh otoritas yang berwenang'},
      {'title': 'Perlindungan Hak',    'desc': 'Untuk melindungi hak, properti, atau keamanan Savora dan penggunanya'},
    ];

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.green.shade50, Colors.green.shade50.withValues(alpha: 0.5)],
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
                width: 40, height: 40,
                decoration: BoxDecoration(color: Colors.green, borderRadius: BorderRadius.circular(10)),
                child: const Icon(Icons.share_outlined, color: Colors.white, size: 20),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text('Berbagi Informasi',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: Colors.green, borderRadius: BorderRadius.circular(20)),
                child: const Text('TIDAK DIJUAL',
                    style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(color: Colors.green.shade100, borderRadius: BorderRadius.circular(8)),
            child: const Text('Kami TIDAK menjual data pribadi Anda.',
                style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1a5c2a))),
          ),
          const SizedBox(height: 12),
          Text('Informasi Anda dapat dibagikan dalam kondisi berikut:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 8),
          ...items.map((item) => Container(
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
                    Text(item['title']!,
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
                    const SizedBox(height: 2),
                    Text(item['desc']!, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                  ],
                ),
              )),
        ],
      ),
    );
  }

  Widget _buildSecuritySection() {
    return _buildSectionCard(
      icon: Icons.shield_outlined,
      iconBg: Colors.purple,
      title: 'Keamanan Data',
      child: Text(
        'Kami menerapkan langkah-langkah keamanan yang wajar untuk melindungi data Anda, termasuk enkripsi, kontrol akses, dan monitoring keamanan. Namun, tidak ada sistem yang 100% aman dari serangan cyber. Anda bertanggung jawab untuk menjaga kerahasiaan password dan API key akun Anda.',
        style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5),
      ),
    );
  }

  Widget _buildUserRightsSection() {
    final rights = [
      {'title': 'Akses',        'desc': 'Melihat data pribadi yang kami simpan'},
      {'title': 'Koreksi',      'desc': 'Memperbarui informasi yang tidak akurat'},
      {'title': 'Penghapusan',  'desc': 'Menghapus akun dan data pribadi Anda'},
      {'title': 'Portabilitas', 'desc': 'Mengunduh data Anda dalam format terstruktur'},
      {'title': 'Keberatan',    'desc': 'Menolak pemrosesan data tertentu'},
    ];

    return _buildSectionCard(
      icon: Icons.person_outline,
      iconBg: Colors.indigo,
      title: 'Hak Pengguna',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Anda memiliki hak untuk:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 10),
          ...rights.asMap().entries.map((entry) => Container(
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
                      width: 28, height: 28,
                      decoration: BoxDecoration(color: Colors.indigo, borderRadius: BorderRadius.circular(8)),
                      child: Center(
                        child: Text('${entry.key + 1}',
                            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(entry.value['title']!,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
                          Text(entry.value['desc']!,
                              style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                        ],
                      ),
                    ),
                  ],
                ),
              )),
          const SizedBox(height: 8),
          Text('Untuk menggunakan hak ini, hubungi kami melalui email atau fitur kontak di platform.',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade500, fontStyle: FontStyle.italic)),
        ],
      ),
    );
  }

  List<Widget> _buildCondensedSections() {
    final sections = [
      {
        'icon': Icons.access_time,
        'color': Colors.orange,
        'title': 'Retensi Data',
        'text': 'Kami menyimpan data Anda selama akun Anda aktif dan periode wajar setelahnya untuk keperluan hukum dan keamanan. Data yang sudah tidak diperlukan akan dihapus secara berkala.'
      },
      {
        'icon': Icons.cookie_outlined,
        'color': Colors.amber,
        'title': 'Cookies dan Teknologi Pelacakan',
        'text': 'Kami menggunakan cookies untuk menjaga sesi login, mengingat preferensi, dan menganalisis penggunaan platform. Anda dapat mengatur browser untuk menolak cookies, namun beberapa fitur mungkin tidak berfungsi dengan baik.'
      },
      {
        'icon': Icons.child_care,
        'color': Colors.pink,
        'title': 'Privasi Anak-anak',
        'text': 'Platform ini tidak ditujukan untuk anak-anak di bawah 13 tahun. Kami tidak secara sengaja mengumpulkan data pribadi dari anak-anak. Jika kami mengetahui adanya data anak-anak, kami akan segera menghapusnya.'
      },
      {
        'icon': Icons.visibility_outlined,
        'color': Colors.blueGrey,
        'title': 'Perubahan Kebijakan',
        'text': 'Kami dapat memperbarui kebijakan privasi ini sewaktu-waktu. Perubahan signifikan akan diberitahukan melalui email atau notifikasi di platform. Penggunaan platform setelah perubahan berarti Anda menyetujui kebijakan yang baru.'
      },
    ];

    return sections.map((s) => Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: _buildSectionCard(
        icon  : s['icon'] as IconData,
        iconBg: s['color'] as Color,
        title : s['title'] as String,
        child : Text(s['text'] as String,
            style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.5)),
      ),
    )).toList();
  }

  Widget _buildContactSection() {
    return _buildSectionCard(
      icon  : Icons.mail_outline,
      iconBg: const Color(0xFF2A9D8F),
      title : 'Kontak',
      child : Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Jika Anda memiliki pertanyaan tentang kebijakan privasi ini, silakan hubungi kami:',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          const SizedBox(height: 10),
          _contactRow(Icons.mail_outline,  'Email',   'adminsavora@gmail.com'),
          const SizedBox(height: 8),
          _contactRow(Icons.language,      'Website', 'savora-web.vercel.app'),
        ],
      ),
    );
  }

  Widget _contactRow(IconData icon, String label, String value) {
    return Container(
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
                Text(label,
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.bold)),
                Text(value,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF264653))),
              ],
            ),
          ),
        ],
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
            width: 48, height: 48,
            decoration: BoxDecoration(color: Colors.blue, borderRadius: BorderRadius.circular(12)),
            child: const Icon(Icons.lock_rounded, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Komitmen Kami',
                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue.shade900, fontSize: 14)),
                const SizedBox(height: 4),
                Text(
                  'Kami berkomitmen untuk melindungi privasi Anda dan menggunakan data Anda secara bertanggung jawab sesuai dengan hukum yang berlaku di Indonesia.',
                  style: TextStyle(fontSize: 12, color: Colors.blue.shade800, height: 1.5),
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
    required Color    iconBg,
    required String   title,
    required Widget   child,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(
                color: iconBg.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: iconBg, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(title,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF264653))),
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
        gradient: LinearGradient(colors: [Colors.grey.shade50, Colors.grey.shade100]),
        border: Border(top: BorderSide(color: Colors.grey.shade200, width: 2)),
      ),
      child: GestureDetector(
        onTap: () => widget.onAccept != null ? widget.onAccept!() : widget.onClose(),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFF2A9D8F), Color(0xFF264653)]),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(color: const Color(0xFF2A9D8F).withValues(alpha: 0.4), blurRadius: 12, offset: const Offset(0, 6)),
            ],
          ),
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.lock_rounded, color: Colors.white, size: 20),
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