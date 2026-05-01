@props([
    'show' => false,
    'onAccept' => null,
])

<div 
    x-data="privacyModal()"
    x-show="isOpen"
    @click="close()"
    class="fixed inset-0 z-50 bg-black bg-opacity-70 flex items-center justify-center p-4 backdrop-blur-sm"
    style="display: none;"
    x-transition
>
    <!-- Modal Container -->
    <div 
        @click.stop
        x-show="isOpen"
        @keydown.escape.window="close()"
        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col transform transition-all"
        style="max-height: 85vh;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <!-- Header with Gradient -->
        <div style="background: linear-gradient(135deg, #2A9D8F, #264653, #1a5c54); position: relative; overflow: hidden;" class="px-8 py-6 text-white flex-shrink-0">
            <!-- Decorative circles -->
            <div style="position:absolute; top:-40px; right:-40px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.1);"></div>
            <div style="position:absolute; bottom:-30px; left:-30px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,0.1);"></div>
            <div class="flex items-center gap-4 relative">
                <div style="background:rgba(255,255,255,0.2); border-radius:16px; padding:14px; flex-shrink:0;">
                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4a3 3 0 110 6 3 3 0 010-6zm0 13c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08A7.232 7.232 0 0112 18z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-black text-white">Kebijakan Privasi</h2>
                    <p class="text-sm font-medium" style="color:rgba(255,255,255,0.85);">Perlindungan data Anda adalah prioritas kami</p>
                </div>
                <button 
                    @click="close()"
                    style="background:rgba(255,255,255,0.2); border-radius:12px; width:40px; height:40px; display:flex; align-items:center; justify-content:center; flex-shrink:0;"
                    class="hover:bg-white hover:bg-opacity-30 transition-colors"
                >
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-y-auto px-8 py-6 text-gray-700">
            <div class="space-y-5 text-sm leading-relaxed">

                <!-- Last Updated -->
                <div class="pb-4 border-b border-gray-200 flex items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:#2A9D8F;flex-shrink:0;"></div>
                    <span class="text-gray-400 text-sm">Terakhir diperbarui: 19 Maret 2026</span>
                </div>

                <!-- Section 1: Informasi yang Kami Kumpulkan -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(42,157,143,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5" style="color:#2A9D8F;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Informasi yang Kami Kumpulkan</h3>
                    </div>
                    <p class="text-gray-500 mb-3">Kami mengumpulkan informasi berikut saat Anda menggunakan Savora:</p>
                    <div class="space-y-2">
                        @foreach([
                            ['icon' => 'person', 'title' => 'Informasi Akun', 'desc' => 'Email, username, nama lengkap, foto profil'],
                            ['icon' => 'visibility', 'title' => 'Konten', 'desc' => 'Resep, foto, video, komentar, dan ulasan yang Anda unggah'],
                            ['icon' => 'share', 'title' => 'Aktivitas', 'desc' => 'Resep yang Anda simpan, ikuti, dan rating yang diberikan'],
                            ['icon' => 'storage', 'title' => 'Data Teknis', 'desc' => 'Alamat IP, browser, perangkat, dan log aktivitas'],
                            ['icon' => 'cookie', 'title' => 'Cookies', 'desc' => 'Data untuk menjaga sesi login dan preferensi'],
                        ] as $item)
                        <div style="background:linear-gradient(to right, rgba(42,157,143,0.05), transparent);border:1px solid rgba(42,157,143,0.15);border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:32px;height:32px;background:rgba(42,157,143,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg class="w-4 h-4" style="color:#2A9D8F;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800 text-xs">{{ $item['title'] }}</p>
                                <p class="text-gray-500 text-xs">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 2: Penggunaan Informasi -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(59,130,246,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Penggunaan Informasi</h3>
                    </div>
                    <p class="text-gray-500 mb-3">Informasi yang kami kumpulkan digunakan untuk:</p>
                    <div class="space-y-2">
                        @foreach([
                            'Menyediakan dan meningkatkan layanan platform',
                            'Memproses autentikasi dan keamanan akun',
                            'Menampilkan konten yang relevan dan personal',
                            'Mengirim notifikasi terkait aktivitas akun',
                            'Menganalisis penggunaan platform untuk perbaikan',
                            'Mencegah penyalahgunaan dan aktivitas ilegal',
                            'Mematuhi kewajiban hukum',
                        ] as $item)
                        <div style="background:#eff6ff;border-radius:8px;" class="px-3 py-2 flex items-start gap-2">
                            <div style="width:5px;height:5px;border-radius:50%;background:#3b82f6;margin-top:7px;flex-shrink:0;"></div>
                            <p class="text-gray-600 text-xs">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 3: Berbagi Informasi (TIDAK DIJUAL) -->
                <section style="background:linear-gradient(135deg, #f0fdf4, rgba(240,253,244,0.5));border:2px solid #86efac;border-radius:16px;" class="p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#22c55e;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base flex-1">Berbagi Informasi</h3>
                        <span style="background:#22c55e;color:white;font-size:10px;font-weight:bold;padding:3px 10px;border-radius:20px;">TIDAK DIJUAL</span>
                    </div>
                    <div style="background:#dcfce7;border-radius:8px;" class="px-3 py-2 mb-3">
                        <p class="font-bold text-xs" style="color:#14532d;">Kami TIDAK menjual data pribadi Anda.</p>
                    </div>
                    <p class="text-gray-500 text-xs mb-3">Informasi Anda dapat dibagikan dalam kondisi berikut:</p>
                    <div class="space-y-2">
                        @foreach([
                            ['title' => 'Konten Publik', 'desc' => 'Resep, profil, dan komentar yang Anda publikasikan dapat dilihat pengguna lain'],
                            ['title' => 'Penyedia Layanan', 'desc' => 'Supabase (database), Vercel (hosting), dan layanan pihak ketiga terpercaya lainnya'],
                            ['title' => 'Kewajiban Hukum', 'desc' => 'Jika diminta oleh otoritas yang berwenang'],
                            ['title' => 'Perlindungan Hak', 'desc' => 'Untuk melindungi hak, properti, atau keamanan Savora dan penggunanya'],
                        ] as $item)
                        <div style="background:rgba(255,255,255,0.8);border:1px solid #86efac;border-radius:10px;" class="p-3">
                            <p class="font-bold text-xs text-gray-800">{{ $item['title'] }}</p>
                            <p class="text-gray-500 text-xs mt-1">{{ $item['desc'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 4: Keamanan Data -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(168,85,247,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Keamanan Data</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">Kami menerapkan langkah-langkah keamanan yang wajar untuk melindungi data Anda, termasuk enkripsi, kontrol akses, dan monitoring keamanan. Namun, tidak ada sistem yang 100% aman dari serangan cyber. Anda bertanggung jawab untuk menjaga kerahasiaan password dan API key akun Anda.</p>
                </section>

                <!-- Section 5: Hak Pengguna -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(99,102,241,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Hak Pengguna</h3>
                    </div>
                    <p class="text-gray-500 text-xs mb-3">Anda memiliki hak untuk:</p>
                    <div class="space-y-2">
                        @foreach([
                            ['num' => '1', 'title' => 'Akses', 'desc' => 'Melihat data pribadi yang kami simpan'],
                            ['num' => '2', 'title' => 'Koreksi', 'desc' => 'Memperbarui informasi yang tidak akurat'],
                            ['num' => '3', 'title' => 'Penghapusan', 'desc' => 'Menghapus akun dan data pribadi Anda'],
                            ['num' => '4', 'title' => 'Portabilitas', 'desc' => 'Mengunduh data Anda dalam format terstruktur'],
                            ['num' => '5', 'title' => 'Keberatan', 'desc' => 'Menolak pemrosesan data tertentu'],
                        ] as $item)
                        <div style="background:#eef2ff;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:28px;height:28px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="text-white text-xs font-bold">{{ $item['num'] }}</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-gray-800">{{ $item['title'] }}</p>
                                <p class="text-gray-500 text-xs">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-gray-400 text-xs italic mt-2">Untuk menggunakan hak ini, hubungi kami melalui email atau fitur kontak di platform.</p>
                </section>

                <!-- Section 6: Proxy AI Pihak Ketiga — PENTING -->
                <section style="background:linear-gradient(135deg, #fff7ed, #fffbeb);border:2px solid #fdba74;border-radius:16px;" class="p-5">
                    <div class="flex items-start gap-3 mb-4">
                        <div style="width:40px;height:40px;background:#ea580c;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M1.92 7.94l10 5c.06.03.13.06.08.06s.02-.03.08-.06l10-5c.13-.06.21-.19.21-.33V7c0-.19-.14-.36-.33-.4l-10-3c-.11-.03-.23-.03-.34 0l-10 3C1.14 6.64 1 6.81 1 7v.61c0 .14.08.27.21.33zM12 15.5l-10-5v3c0 .14.08.27.21.33l10 5c.06.03.13.06.08.06s.02-.03.08-.06l10-5c.13-.06.21-.19.21-.33v-3l-10 5z"/></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800" style="font-size:15px;">Penggunaan Proxy AI Pihak Ketiga</h3>
                        </div>
                        <span style="background:#ea580c;color:white;font-size:10px;font-weight:bold;padding:3px 10px;border-radius:20px;flex-shrink:0;">PENTING</span>
                    </div>
                    <p class="text-gray-600 text-xs leading-relaxed mb-4">Savora menyediakan fitur opsional untuk menghubungkan layanan AI pihak ketiga (seperti OpenRouter) melalui pengaturan proxy. Dengan menggunakan fitur ini, Anda memahami dan menyetujui hal-hal berikut:</p>
                    <div class="space-y-3">
                        <!-- Item A -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #fdba74;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="background:rgba(220,38,38,0.12);border-radius:8px;padding:6px;flex-shrink:0;">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5V6h14v12zm-7-1h2v-4h2V9h-2V7h-2v2H10v4h2z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-red-600">Tidak Ada Tanggung Jawab Pembayaran</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Savora tidak bertanggung jawab atas biaya, tagihan, atau pembayaran apapun yang timbul dari penggunaan layanan AI pihak ketiga. Pengguna sepenuhnya bertanggung jawab atas pengelolaan kredit, kuota, dan biaya yang dikenakan oleh provider yang dipilih.</p>
                            </div>
                        </div>
                        <!-- Item B -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #fdba74;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="background:rgba(126,34,206,0.12);border-radius:8px;padding:6px;flex-shrink:0;">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-purple-600">Tidak Ada Hubungan Resmi</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Savora tidak memiliki kemitraan, afiliasi, kerja sama, atau hubungan resmi apapun dengan layanan AI pihak ketiga yang dapat dikonfigurasi melalui fitur ini (termasuk namun tidak terbatas pada OpenRouter, dan penyedia lainnya). Nama dan logo pihak ketiga hanya ditampilkan sebagai referensi pengguna.</p>
                            </div>
                        </div>
                        <!-- Item C -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #fdba74;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="background:rgba(194,65,12,0.12);border-radius:8px;padding:6px;flex-shrink:0;">
                                <svg class="w-4 h-4 text-orange-700" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-orange-700">Persetujuan Risiko Pengguna</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Dengan mengaktifkan dan menggunakan fitur proxy AI pihak ketiga, Anda secara eksplisit menyetujui segala risiko yang mungkin timbul, termasuk: risiko keamanan API key, ketidaktersediaan layanan, perubahan kebijakan penyedia, dan konsekuensi finansial dari penggunaan model berbayar.</p>
                            </div>
                        </div>
                        <!-- Item D -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #fdba74;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="background:rgba(29,78,216,0.12);border-radius:8px;padding:6px;flex-shrink:0;">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-blue-600">Pengiriman Data ke Pihak Ketiga</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Saat menggunakan proxy pihak ketiga, pesan dan konten yang Anda kirim akan diteruskan ke server penyedia yang bersangkutan. Savora tidak dapat menjamin bagaimana data tersebut diproses, disimpan, atau digunakan oleh pihak ketiga. Harap baca kebijakan privasi penyedia sebelum menggunakannya.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Info Banner -->
                    <div style="background:#ffedd5;border:1px solid #fdba74;border-radius:10px;" class="p-3 mt-4 flex items-start gap-2">
                        <svg class="w-4 h-4 text-orange-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        <p class="text-xs leading-relaxed" style="color:#9a3412;">Penggunaan fitur ini bersifat opsional. Savora menyediakan Groq sebagai layanan AI default yang gratis dan dikelola oleh server tanpa konfigurasi tambahan dari pengguna.</p>
                    </div>
                </section>

                <!-- Section 7: Retensi Data -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(249,115,22,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Retensi Data</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">Kami menyimpan data Anda selama akun Anda aktif dan periode wajar setelahnya untuk keperluan hukum dan keamanan. Data yang sudah tidak diperlukan akan dihapus secara berkala.</p>
                </section>

                <!-- Section 8: Cookies -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(234,179,8,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Cookies dan Teknologi Pelacakan</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">Kami menggunakan cookies untuk menjaga sesi login, mengingat preferensi, dan menganalisis penggunaan platform. Anda dapat mengatur browser untuk menolak cookies, namun beberapa fitur mungkin tidak berfungsi dengan baik.</p>
                </section>

                <!-- Section 9: Privasi Anak -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(236,72,153,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-pink-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Privasi Anak-anak</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">Platform ini tidak ditujukan untuk anak-anak di bawah 13 tahun. Kami tidak secara sengaja mengumpulkan data pribadi dari anak-anak. Jika kami mengetahui adanya data anak-anak, kami akan segera menghapusnya.</p>
                </section>

                <!-- Section 10: Perubahan Kebijakan -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(100,116,139,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Perubahan Kebijakan</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">Kami dapat memperbarui kebijakan privasi ini sewaktu-waktu. Perubahan signifikan akan diberitahukan melalui email atau notifikasi di platform. Penggunaan platform setelah perubahan berarti Anda menyetujui kebijakan yang baru.</p>
                </section>

                <!-- Kontak -->
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div style="width:40px;height:40px;background:rgba(42,157,143,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-5 h-5" style="color:#2A9D8F;" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Kontak</h3>
                    </div>
                    <p class="text-gray-500 text-xs mb-3">Jika Anda memiliki pertanyaan tentang kebijakan privasi ini, silakan hubungi kami:</p>
                    <div class="space-y-2">
                        <div style="background:rgba(42,157,143,0.08);border-radius:10px;" class="p-3 flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" style="color:#2A9D8F;" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            <div>
                                <p class="text-gray-400 text-xs font-bold">Email</p>
                                <p class="font-semibold text-sm text-gray-800">adminsavora@gmail.com</p>
                            </div>
                        </div>
                        <div style="background:rgba(42,157,143,0.08);border-radius:10px;" class="p-3 flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" style="color:#2A9D8F;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            <div>
                                <p class="text-gray-400 text-xs font-bold">Website</p>
                                <a href="https://savora-app-productions.up.railway.app" target="_blank" rel="noopener noreferrer" class="font-semibold text-sm flex items-center gap-1 hover:underline" style="color:#2A9D8F;">
                                    savora-app-productions.up.railway.app
                                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Komitmen Banner -->
                <section style="background:linear-gradient(135deg, #eff6ff, #ecfeff);border:2px solid #93c5fd;border-radius:16px;" class="p-5 flex items-start gap-4">
                    <div style="width:48px;height:48px;background:#3b82f6;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-sm" style="color:#1e3a8a;">Komitmen Kami</p>
                        <p class="text-xs mt-1 leading-relaxed" style="color:#1d4ed8;">Kami berkomitmen untuk melindungi privasi Anda dan menggunakan data Anda secara bertanggung jawab sesuai dengan hukum yang berlaku di Indonesia.</p>
                    </div>
                </section>

            </div>
        </div>

        <!-- Footer with Actions -->
        <div class="border-t-2 border-gray-200 px-8 py-5 flex-shrink-0" style="background:linear-gradient(to right, #f9fafb, #f3f4f6);">
            <button 
                @click="accept()"
                style="background:linear-gradient(135deg, #2A9D8F, #264653); width:100%; border-radius:16px; padding:16px; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow: 0 6px 20px rgba(42,157,143,0.4);"
                class="text-white font-bold text-base hover:shadow-lg transform hover:scale-105 active:scale-95 transition-all"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                Saya Mengerti & Menyetujui
            </button>
        </div>
    </div>
</div>

<script>
function privacyModal() {
    return {
        isOpen: @json($show ?? false),

        init() {
            document.addEventListener('openPrivacyModal', () => {
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            });
        },

        open() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = 'auto';
        },

        accept() {
            const event = new CustomEvent('privacyAccepted', { detail: { timestamp: new Date() } });
            document.dispatchEvent(event);
            this.close();
        }
    }
}

window.PrivacyModal = {
    show() {
        const event = new CustomEvent('openPrivacyModal');
        document.dispatchEvent(event);
    }
}
</script>