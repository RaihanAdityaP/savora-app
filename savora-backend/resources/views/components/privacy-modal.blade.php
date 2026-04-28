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
        @keydown.escape="close()"
        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col transform transition-all"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <!-- Header with Gradient -->
        <div style="background: linear-gradient(135deg, #2A9D8F, #264653, #1a5c54);" class="px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Kebijakan Privasi</h2>
                <button 
                    @click="close()"
                    class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors"
                >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-y-auto px-8 py-6 text-gray-700">
            <div class="space-y-4 text-sm leading-relaxed">
                <section>
                    <h3 class="font-bold text-gray-900 mb-2">1. Pengantar Kebijakan</h3>
                    <p>Privasi Anda penting bagi kami. Savora hanya menggunakan data yang diperlukan untuk meningkatkan pengalaman Anda di aplikasi.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">2. Bagaimana Kami Menggunakan Data</h3>
                    <p>Data Anda digunakan untuk memberikan layanan yang lebih baik, personalisasi pengalaman, dan meningkatkan fitur aplikasi Savora.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">3. Keamanan Data</h3>
                    <p>Kami menerapkan enkripsi dan protokol keamanan tingkat industri untuk melindungi informasi pribadi Anda dari akses tidak sah.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">4. Berbagi dengan Pihak Ketiga</h3>
                    <p>Kami tidak menjual atau membagikan data pribadi Anda kepada pihak ketiga tanpa persetujuan eksplisit Anda.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">5. Hak Anda</h3>
                    <p>Anda memiliki hak untuk mengakses, memperbaiki, atau menghapus data pribadi Anda kapan saja dengan menghubungi tim dukungan kami.</p>
                </section>
            </div>
        </div>

        <!-- Footer with Actions -->
        <div class="border-t border-gray-200 px-8 py-4 bg-gray-50 flex gap-3">
            <button 
                @click="close()"
                class="flex-1 px-4 py-3 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-semibold transition-colors"
            >
                Tutup
            </button>
            <button 
                @click="accept()"
                style="background: linear-gradient(135deg, #2A9D8F, #264653, #1a5c54);"
                class="flex-1 px-4 py-3 text-white rounded-lg font-semibold transition-all hover:shadow-lg transform hover:scale-105 active:scale-95"
            >
                Saya Setuju
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

