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
        @keydown.escape.window="close()"
        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col transform transition-all"
        style="max-height: 85vh;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <!-- Header with Gradient -->
        <div style="background: linear-gradient(135deg, #E76F51, #F4A261, #E9C46A); position: relative; overflow: hidden;" class="px-8 py-6 text-white flex-shrink-0">
            <!-- Decorative circles -->
            <div style="position:absolute; top:-40px; right:-40px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.1);"></div>
            <div style="position:absolute; bottom:-30px; left:-30px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,0.1);"></div>
            <div class="flex items-center gap-4 relative">
                <div style="background:rgba(255,255,255,0.2); border-radius:16px; padding:14px; flex-shrink:0;">
                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-black text-white">Terms & Conditions</h2>
                    <p class="text-sm font-medium" style="color:rgba(255,255,255,0.85);">Please read carefully</p>
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
                    <div style="width:8px;height:8px;border-radius:50%;background:#E76F51;flex-shrink:0;"></div>
                    <span class="text-gray-400 text-sm">Last updated: March 19, 2026</span>
                </div>

                <!-- Section 1: Penerimaan Ketentuan -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(231,111,81,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm" style="color:#E76F51;">1</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Acceptance of Terms</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">By accessing and using the Savora platform, you agree to be bound by these terms and conditions. If you do not agree, please do not use our services.</p>
                </section>

                <!-- Section 2: Konten yang Diunggah -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(42,157,143,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm" style="color:#2A9D8F;">2</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Uploaded Content</h3>
                    </div>
                    <p class="text-gray-500 text-xs mb-3 pl-11">Users are fully responsible for the content they upload, including:</p>
                    <div class="flex flex-wrap gap-2 pl-11">
                        @foreach(['Cooking recipes and tutorials', 'Uploaded photos and videos', 'Comments and reviews', 'Profile information'] as $item)
                        <div style="background:#f3f4f6;border-radius:8px;" class="px-3 py-1.5 flex items-center gap-2">
                            <div style="width:5px;height:5px;border-radius:50%;background:#2A9D8F;flex-shrink:0;"></div>
                            <span class="text-gray-600 text-xs">{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 3: Hak Kekayaan Intelektual — SANGAT PENTING -->
                <section style="background:linear-gradient(135deg, #fef2f2, #fff7ed);border:2px solid #fca5a5;border-radius:16px;" class="p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div style="width:32px;height:32px;background:rgba(239,68,68,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-red-500">3</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                                <h3 class="font-bold text-red-500 text-base">Intellectual Property Rights</h3>
                                <span style="background:#ef4444;color:white;font-size:10px;font-weight:bold;padding:3px 10px;border-radius:20px;">VERY IMPORTANT</span>
                            </div>
                        </div>
                    </div>
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;" class="p-3 mb-3">
                        <p class="text-xs font-semibold leading-relaxed" style="color:#7f1d1d;">Savora is <span style="background:#fca5a5;border-radius:4px;padding:1px 6px;font-weight:bold;">not responsible</span> for copyright, trademark, or other intellectual property violations committed by users.</p>
                    </div>
                    <p class="text-gray-500 text-xs mb-3">By uploading content, you represent that:</p>
                    <div class="space-y-2">
                        @foreach([
                            'You have full rights to the uploaded content, OR',
                            'You have obtained valid permission to share the content',
                            'The content does not infringe third-party copyrights',
                            'You are fully responsible for any legal claims related to content you upload',
                        ] as $item)
                        <div style="background:rgba(255,255,255,0.8);border-radius:8px;" class="p-2.5 flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                            <p class="text-gray-600 text-xs">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 4: Pembatasan Tanggung Jawab -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(244,162,97,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm" style="color:#F4A261;">4</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Limitation of Liability</h3>
                    </div>
                    <p class="text-gray-500 text-xs mb-3 pl-11">Savora acts as a recipe-sharing platform and is NOT responsible for:</p>
                    <div class="space-y-1.5 pl-11">
                        @foreach([
                            'The accuracy of recipe information shared by users',
                            'Losses or injuries arising from following recipes',
                            'Copyright or intellectual property violations by users',
                            'Interactions or transactions between users',
                            'Loss of data or content',
                            'Device damage caused by platform use',
                            'Costs or financial losses caused by third-party AI services',
                        ] as $item)
                        <div style="background:#f3f4f6;border-radius:8px;" class="px-3 py-2 flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                            <p class="text-gray-600 text-xs">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section: Larangan Konten -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:#fee2e2;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Prohibited Content</h3>
                    </div>
                    <p class="text-gray-500 text-xs mb-3 pl-11">Users are prohibited from uploading content that:</p>
                    <div class="space-y-1.5 pl-11">
                        @foreach([
                            'Infringes another party copyright, trademark, or intellectual property rights',
                            'Contains hate, pornography, or violence',
                            'Is misleading, false, or fraudulent',
                            'Violates applicable Indonesian law',
                            'Contains viruses, malware, or harmful code',
                        ] as $item)
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;" class="px-3 py-2 flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                            <p class="text-gray-600 text-xs">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- Section 5: Syarat Penggunaan Proxy AI — WAJIB DIBACA -->
                <section style="background:linear-gradient(135deg, #f5f3ff, #faf5ff);border:2px solid #c4b5fd;border-radius:16px;" class="p-5">
                    <div class="flex items-start gap-3 mb-4">
                        <div style="width:32px;height:32px;background:rgba(124,58,237,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-purple-600">5</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24"><path d="M1.92 7.94l10 5c.06.03.13.06.08.06s.02-.03.08-.06l10-5c.13-.06.21-.19.21-.33V7c0-.19-.14-.36-.33-.4l-10-3c-.11-.03-.23-.03-.34 0l-10 3C1.14 6.64 1 6.81 1 7v.61c0 .14.08.27.21.33z"/></svg>
                                <h3 class="font-bold text-purple-700" style="font-size:15px;">Third-Party AI Proxy Terms</h3>
                            </div>
                            <span style="background:#7c3aed;color:white;font-size:10px;font-weight:bold;padding:3px 10px;border-radius:20px;">MUST READ</span>
                        </div>
                    </div>
                    <p class="text-gray-600 text-xs leading-relaxed mb-4">Savora provides an optional feature to connect AI services from third-party providers. By using this feature, you agree to all of the following terms:</p>
                    <div class="space-y-3">
                        <!-- Item A -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #ddd6fe;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:26px;height:26px;background:rgba(220,38,38,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="font-bold text-xs text-red-600">A</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-red-600">Costs Are Fully the User's Responsibility</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Savora is not responsible for any costs, charges, quota overages, or payments arising from third-party AI services. All API usage costs, including paid models, are fully the user's responsibility. Savora will not reimburse any financial loss caused by this feature.</p>
                            </div>
                        </div>
                        <!-- Item B -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #ddd6fe;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:26px;height:26px;background:rgba(109,40,217,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="font-bold text-xs text-purple-700">B</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-purple-700">No Official Relationship with Third Parties</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Savora has no partnership, affiliation, cooperation agreement, or official relationship of any kind with third-party AI providers supported by this feature, including but not limited to OpenRouter and similar providers. Third-party names and services are displayed only to help user configuration and do not imply endorsement or official cooperation.</p>
                            </div>
                        </div>
                        <!-- Item C -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #ddd6fe;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:26px;height:26px;background:rgba(194,65,12,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="font-bold text-xs text-orange-700">C</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-orange-700">Explicit Risk Consent</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">By enabling and using the third-party AI proxy feature, you knowingly and explicitly accept all risks that may arise, including API key leakage or misuse, third-party service unavailability or disruption, provider price or policy changes without notice, conversation data loss, and financial consequences from uncontrolled usage.</p>
                            </div>
                        </div>
                        <!-- Item D -->
                        <div style="background:rgba(255,255,255,0.85);border:1px solid #ddd6fe;border-radius:10px;" class="p-3 flex items-start gap-3">
                            <div style="width:26px;height:26px;background:rgba(29,78,216,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="font-bold text-xs text-blue-700">D</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-blue-700">Compliance with Third-Party Terms</p>
                                <p class="text-gray-600 text-xs mt-1 leading-relaxed">Users must read, understand, and comply with the terms, conditions, and usage policies of each third-party AI provider they use. Savora is not responsible for user violations of third-party terms.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Info Banner -->
                    <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;" class="p-3 mt-4 flex items-start gap-2">
                        <svg class="w-4 h-4 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                        <p class="text-xs leading-relaxed text-purple-700">Savora provides a free default AI service (Groq) managed by the server. The third-party proxy feature is optional and intended for users who want to use specific AI models or providers.</p>
                    </div>
                </section>

                <!-- Section 6: Moderasi Konten -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(59,130,246,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-blue-500">6</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Content Moderation</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">Savora has the right to review, approve, reject, or remove content considered to violate these terms and conditions without prior notice.</p>
                </section>

                <!-- Section 7: Akun Pengguna -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(168,85,247,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-purple-500">7</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">User Account</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">You are responsible for keeping your account, password, and API keys confidential. Savora is not responsible for losses caused by unauthorized access to your account or API keys.</p>
                </section>

                <!-- Section 8: Penghentian Layanan -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(236,72,153,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-pink-500">8</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Service Termination</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">Savora reserves the right to suspend or terminate your access to the platform if you are proven to violate these terms and conditions, without any compensation.</p>
                </section>

                <!-- Section 9: Perubahan Ketentuan -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(99,102,241,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-indigo-500">9</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Changes to Terms</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">Savora reserves the right to change these terms and conditions at any time. Changes take effect immediately after being published on the platform.</p>
                </section>

                <!-- Section 10: Hukum yang Berlaku -->
                <section>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="width:32px;height:32px;background:rgba(100,116,139,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="font-bold text-sm text-slate-500">10</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base">Governing Law</h3>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed pl-11">These terms and conditions are governed by and interpreted under the laws of the Republic of Indonesia. Any dispute will be resolved in the competent courts of Indonesia.</p>
                </section>

                <!-- Disclaimer Banner -->
                <section style="background:linear-gradient(135deg, #fefce8, #fffbeb);border:2px solid #fcd34d;border-radius:16px;" class="p-5">
                    <div class="flex items-start gap-4 mb-4">
                        <div style="width:48px;height:48px;background:#f59e0b;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm" style="color:#78350f;">IMPORTANT DISCLAIMER</p>
                            <p class="text-xs mt-1 leading-relaxed" style="color:#92400e;">By using Savora, you <strong>release the platform and its operators</strong> from any legal claims related to user-uploaded content, including but not limited to copyright infringement, injury, or material loss.</p>
                        </div>
                    </div>
                    <!-- Proxy disclaimer -->
                    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;" class="p-3 mb-3 flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:#92400e;" fill="currentColor" viewBox="0 0 24 24"><path d="M1.92 7.94l10 5c.06.03.13.06.08.06s.02-.03.08-.06l10-5c.13-.06.21-.19.21-.33V7c0-.19-.14-.36-.33-.4l-10-3c-.11-.03-.23-.03-.34 0l-10 3C1.14 6.64 1 6.81 1 7v.61c0 .14.08.27.21.33z"/></svg>
                        <p class="text-xs font-medium leading-relaxed" style="color:#78350f;">For the AI proxy feature specifically: Savora is not responsible for usage costs, service interruptions, or any losses arising from third-party AI providers. Use of this feature is entirely at your own risk and responsibility.</p>
                    </div>
                    <!-- Kontak -->
                    <div style="background:rgba(255,255,255,0.7);border-radius:10px;" class="p-3 flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:#92400e;" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <p class="text-xs" style="color:#92400e;">Questions? Contact us: <strong>adminsavora@gmail.com</strong></p>
                    </div>
                </section>

            </div>
        </div>

        <!-- Footer with Actions -->
        <div class="border-t-2 border-gray-200 px-8 py-5 flex-shrink-0" style="background:linear-gradient(to right, #f9fafb, #f3f4f6);">
            <button 
                @click="accept()"
                style="background:linear-gradient(135deg, #E76F51, #F4A261); width:100%; border-radius:16px; padding:16px; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow: 0 6px 20px rgba(231,111,81,0.4);"
                class="text-white font-bold text-base hover:shadow-lg transform hover:scale-105 active:scale-95 transition-all"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                I Understand & Agree
            </button>
        </div>
    </div>
</div>

<script>
function termsModal() {
    return {
        isOpen: @json($show ?? false),

        init() {
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
            const event = new CustomEvent('termsAccepted', { detail: { timestamp: new Date() } });
            document.dispatchEvent(event);
            this.close();
        }
    }
}

window.TermsModal = {
    show() {
        const event = new CustomEvent('openTermsModal');
        document.dispatchEvent(event);
    }
}
</script>
