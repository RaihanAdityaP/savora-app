@props([
    'show' => false,
    'onAccept' => null,
])

<div 
    x-data="termsModal()"
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
        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-96 overflow-hidden flex flex-col transform transition-all"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <!-- Header with Gradient -->
        <div style="background: linear-gradient(135deg, #E76F51, #F4A261, #E9C46A);" class="px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Syarat & Ketentuan</h2>
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
                    <h3 class="font-bold text-gray-900 mb-2">1. Penggunaan Layanan</h3>
                    <p>Dengan menggunakan Savora, Anda menerima untuk mematuhi semua syarat dan ketentuan yang berlaku. Anda bertanggung jawab atas semua aktivitas yang terjadi di akun Anda.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">2. Konten Pengguna</h3>
                    <p>Anda mempertahankan semua hak atas konten yang Anda posting. Dengan mengunggah konten, Anda memberikan lisensi kepada kami untuk menggunakan, memodifikasi, dan mendistribusikan konten tersebut.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">3. Larangan Penggunaan</h3>
                    <p>Anda tidak boleh menggunakan Savora untuk tujuan ilegal, mengganggu, atau menyinggung. Anda tidak boleh mengunggah malware, spam, atau konten yang melanggar hak orang lain.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">4. Disclaimer Tanggung Jawab</h3>
                    <p>Savora disediakan "sebagaimana adanya". Kami tidak menjamin kelengkapan, akurasi, atau keamanan konten. Kami tidak bertanggung jawab atas kerugian yang timbul dari penggunaan aplikasi.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2">5. Perubahan Syarat</h3>
                    <p>Kami berhak mengubah syarat dan ketentuan kapan saja. Perubahan akan efektif ketika dipublikasikan di aplikasi.</p>
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
                style="background: linear-gradient(135deg, #E76F51, #F4A261, #E9C46A);"
                class="flex-1 px-4 py-3 text-white rounded-lg font-semibold transition-all hover:shadow-lg transform hover:scale-105 active:scale-95"
            >
                Saya Setuju
            </button>
        </div>
    </div>
</div>

<script>
function termsModal() {
    return {
        isOpen: @json($show ?? false),

        init() {
            // Listen untuk event pembukaan modal
            document.addEventListener('openTermsModal', () => {
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
            // Emit event untuk acceptance
            const event = new CustomEvent('termsAccepted', { detail: { timestamp: new Date() } });
            document.dispatchEvent(event);
            this.close();
        }
    }
}

// Static method untuk membuka modal
window.TermsModal = {
    show() {
        const event = new CustomEvent('openTermsModal');
        document.dispatchEvent(event);
    }
}
</script>
