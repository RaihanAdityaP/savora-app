import 'package:flutter/material.dart';
import 'dart:math' as math;

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen>
    with TickerProviderStateMixin {
  late final AnimationController _gearController;
  late final AnimationController _gearSmallController;
  late final AnimationController _floatController;
  late final AnimationController _shimmerController;
  late final AnimationController _dotController;

  late final Animation<double> _floatAnimation;
  late final Animation<double> _shimmerAnimation;

  @override
  void initState() {
    super.initState();

    _gearController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 8),
    )..repeat();

    _gearSmallController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    )..repeat(reverse: false);

    _floatController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 3000),
    )..repeat(reverse: true);

    _shimmerController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat();

    _dotController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat(reverse: true);

    _floatAnimation = Tween<double>(begin: 0, end: -6).animate(
      CurvedAnimation(parent: _floatController, curve: Curves.easeInOut),
    );

    _shimmerAnimation = Tween<double>(begin: -1.5, end: 1.5).animate(
      CurvedAnimation(parent: _shimmerController, curve: Curves.linear),
    );
  }

  @override
  void dispose() {
    _gearController.dispose();
    _gearSmallController.dispose();
    _floatController.dispose();
    _shimmerController.dispose();
    _dotController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.grey[100],
        elevation: 0,
        title: const Text(
          'Pengaturan',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 18,
            color: Colors.black,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            _buildGradientHeader(),
            _buildComingSoonContent(),
          ],
        ),
      ),
    );
  }

  Widget _buildGradientHeader() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFE76F51), Color(0xFFF4A261)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFE76F51).withAlpha(77),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(64),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white.withAlpha(102), width: 2),
            ),
            child: const Icon(Icons.settings_outlined, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 12),
          const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Pengaturan',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                'Atur pengalaman Savora kamu',
                style: TextStyle(color: Colors.white70, fontSize: 12),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildComingSoonContent() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(10),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildGearIllustration(),
          _buildTextSection(),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildGearIllustration() {
    return Container(
      height: 180,
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFE76F51).withAlpha(15),
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Center(
        child: SizedBox(
          width: 160,
          height: 160,
          child: Stack(
            alignment: Alignment.center,
            children: [
              // Big gear
              Positioned(
                top: 20,
                left: 20,
                child: AnimatedBuilder(
                  animation: _gearController,
                  builder: (context, child) {
                    return Transform.rotate(
                      angle: _gearController.value * 2 * math.pi,
                      child: child,
                    );
                  },
                  child: CustomPaint(
                    size: const Size(100, 100),
                    painter: _GearPainter(
                      color: const Color(0xFFE76F51),
                      teeth: 12,
                    ),
                  ),
                ),
              ),
              // Small gear
              Positioned(
                top: 8,
                right: 4,
                child: AnimatedBuilder(
                  animation: _gearSmallController,
                  builder: (context, child) {
                    return Transform.rotate(
                      angle: -_gearSmallController.value * 2 * math.pi,
                      child: child,
                    );
                  },
                  child: CustomPaint(
                    size: const Size(54, 54),
                    painter: _GearPainter(
                      color: const Color(0xFFF4A261),
                      teeth: 8,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextSection() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        children: [
          const SizedBox(height: 24),
          _buildPulseBadge(),
          const SizedBox(height: 16),
          const Text(
            'Halaman Pengaturan',
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Colors.black,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            'Kami sedang menyiapkan halaman pengaturan yang lebih lengkap dan personal. Sementara itu, nikmati fitur-fitur Savora lainnya ya!',
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey[600],
              height: 1.6,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          _buildShimmerRows(),
          const SizedBox(height: 24),
          _buildBackButton(),
        ],
      ),
    );
  }

  Widget _buildPulseBadge() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xFFE76F51).withAlpha(30),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          ...List.generate(3, (i) {
            return AnimatedBuilder(
              animation: _dotController,
              builder: (context, child) {
                final delay = i * 0.3;
                final t = (_dotController.value - delay).clamp(0.0, 1.0);
                final opacity = 0.4 + 0.6 * math.sin(t * math.pi);
                return Container(
                  margin: const EdgeInsets.only(right: 4),
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE76F51).withValues(alpha: opacity),
                    shape: BoxShape.circle,
                  ),
                );
              },
            );
          }),
          const Text(
            'Segera Hadir',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFFE76F51),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShimmerRows() {
    return AnimatedBuilder(
      animation: _floatAnimation,
      builder: (context, child) {
        return Transform.translate(
          offset: Offset(0, _floatAnimation.value),
          child: child,
        );
      },
      child: Column(
        children: List.generate(3, (index) => _buildShimmerRow(index)),
      ),
    );
  }

  Widget _buildShimmerRow(int index) {
    final widths = [0.33, 0.40, 0.25];
    final subtitleWidths = [0.55, 0.45, 0.50];

    return AnimatedBuilder(
      animation: _shimmerAnimation,
      builder: (context, child) {
        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: const Color(0xFFE76F51).withAlpha(8),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFE76F51).withAlpha(25)),
          ),
          child: Row(
            children: [
              _shimmerBox(width: 36, height: 36, borderRadius: 10),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _shimmerBox(
                      width: MediaQuery.of(context).size.width * widths[index],
                      height: 10,
                      borderRadius: 5,
                    ),
                    const SizedBox(height: 6),
                    _shimmerBox(
                      width: MediaQuery.of(context).size.width * subtitleWidths[index],
                      height: 8,
                      borderRadius: 4,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              _shimmerBox(width: 40, height: 22, borderRadius: 11),
            ],
          ),
        );
      },
    );
  }

  Widget _shimmerBox({
    required double width,
    required double height,
    required double borderRadius,
  }) {
    return AnimatedBuilder(
      animation: _shimmerAnimation,
      builder: (context, _) {
        return Container(
          width: width,
          height: height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(borderRadius),
            gradient: LinearGradient(
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
              colors: [
                const Color(0xFFE76F51).withAlpha(20),
                const Color(0xFFE76F51).withAlpha(46),
                const Color(0xFFE76F51).withAlpha(20),
              ],
              stops: [
                (_shimmerAnimation.value - 0.5).clamp(0.0, 1.0),
                (_shimmerAnimation.value).clamp(0.0, 1.0),
                (_shimmerAnimation.value + 0.5).clamp(0.0, 1.0),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildBackButton() {
    return SizedBox(
      width: double.infinity,
      height: 52,
      child: ElevatedButton.icon(
        onPressed: () => Navigator.pop(context),
        icon: const Icon(Icons.home_outlined, size: 18, color: Colors.white),
        label: const Text(
          'Kembali ke Beranda',
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFE76F51),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          elevation: 0,
        ),
      ),
    );
  }
}

class _GearPainter extends CustomPainter {
  final Color color;
  final int teeth;

  const _GearPainter({required this.color, required this.teeth});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final outerRadius = size.width / 2;
    final innerRadius = outerRadius * 0.72;
    final toothHeight = outerRadius * 0.22;
    final holeRadius = outerRadius * 0.28;

    final fillPaint = Paint()
      ..color = color.withAlpha(26)
      ..style = PaintingStyle.fill;

    final strokePaint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.5
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final path = Path();
    final angleStep = (2 * math.pi) / teeth;

    for (int i = 0; i < teeth; i++) {
      final angle = i * angleStep - math.pi / 2;
      final toothAngle = angleStep * 0.35;

      // Tooth points
      final p1 = Offset(
        center.dx + innerRadius * math.cos(angle - toothAngle),
        center.dy + innerRadius * math.sin(angle - toothAngle),
      );
      final p2 = Offset(
        center.dx + (innerRadius + toothHeight) * math.cos(angle - toothAngle * 0.5),
        center.dy + (innerRadius + toothHeight) * math.sin(angle - toothAngle * 0.5),
      );
      final p3 = Offset(
        center.dx + (innerRadius + toothHeight) * math.cos(angle + toothAngle * 0.5),
        center.dy + (innerRadius + toothHeight) * math.sin(angle + toothAngle * 0.5),
      );
      final p4 = Offset(
        center.dx + innerRadius * math.cos(angle + toothAngle),
        center.dy + innerRadius * math.sin(angle + toothAngle),
      );

      if (i == 0) {
        path.moveTo(p1.dx, p1.dy);
      } else {
        path.lineTo(p1.dx, p1.dy);
      }
      path.lineTo(p2.dx, p2.dy);
      path.lineTo(p3.dx, p3.dy);
      path.lineTo(p4.dx, p4.dy);

      final nextAngle = (i + 1) * angleStep - math.pi / 2;
      final nextP1 = Offset(
        center.dx + innerRadius * math.cos(nextAngle - toothAngle),
        center.dy + innerRadius * math.sin(nextAngle - toothAngle),
      );
      path.arcToPoint(
        nextP1,
        radius: Radius.circular(innerRadius),
        clockwise: true,
      );
    }
    path.close();

    canvas.drawPath(path, fillPaint);
    canvas.drawPath(path, strokePaint);

    // Center hole
    canvas.drawCircle(center, holeRadius, fillPaint);
    canvas.drawCircle(center, holeRadius, strokePaint);
  }

  @override
  bool shouldRepaint(_GearPainter oldDelegate) =>
      oldDelegate.color != color || oldDelegate.teeth != teeth;
}