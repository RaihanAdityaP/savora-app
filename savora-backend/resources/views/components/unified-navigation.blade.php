@props([
    'avatarUrl' => null,
    'unreadCount' => 0,
    'username' => null,
])

@php
$resolvedAvatar   = $avatarUrl ?: null;
$resolvedUsername = $username ?: (auth()->user()->username ?? auth()->user()->name ?? 'User');
$resolvedUserId   = session('user_id') ?? auth()->id();
$isEnglish        = session('user_language', 'en') === 'en';
@endphp

<div>
    <form id="logout-form" action="{{ route('app.logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <div x-data="{
        userId: '{{ $resolvedUserId }}',
        unreadCount: {{ (int) $unreadCount }},
        showProfileMenu: false,
        showMobileMenu: false,
        username: @js($resolvedUsername),
        avatarUrl: @js($resolvedAvatar),
        pathname: window.location.pathname,
        handleLogout() {
            if (!confirm('Keluar dari akun Savora?')) return;
            document.getElementById('logout-form').submit();
        },
        profileUrl() {
            return this.userId ? '/profile/' + this.userId : '#';
        }
    }" @click.outside="showProfileMenu = false; showMobileMenu = false">

    {{-- ═══════════════════════════════════════════════════════
         TOP NAVIGATION
    ══════════════════════════════════════════════════════════ --}}
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- LOGO --}}
                <a href="{{ route('app.home') }}" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                    <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-[#2B6CB0] to-[#FF6B35] p-1 shadow-lg">
                        <div class="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('storage/images/logo.png') }}" alt="Savora" class="object-cover w-8 h-8"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <span style="display:none" class="text-[#E76F51] font-black text-sm">S</span>
                        </div>
                    </div>
                    <span class="text-gray-900 text-xl md:text-2xl font-bold tracking-tight">Savora</span>
                </a>

                {{-- RIGHT ACTIONS --}}
                <div class="flex items-center gap-1">

                    {{-- NOTIFICATION BUTTON — visible on BOTH mobile & desktop --}}
                    <a href="{{ route('app.notifications') }}"
                       class="relative p-2 rounded-full hover:bg-gray-100 transition-colors
                              {{ request()->routeIs('app.notifications*') ? 'text-[#E76F51]' : 'text-gray-500' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="unreadCount > 0"
                              x-text="unreadCount > 99 ? '99+' : unreadCount"
                              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full px-1">
                        </span>
                    </a>

                    {{-- PROFILE BUTTON + DROPDOWN — desktop only --}}
                    <div class="relative hidden md:block">
                        <button @click.stop="showProfileMenu = !showProfileMenu"
                                class="flex items-center gap-1.5 p-1 rounded-full hover:bg-gray-100 transition-colors focus:outline-none">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-200 border-2 border-gray-300 shrink-0">
                                <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                    <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200"
                                 :class="showProfileMenu ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- DESKTOP DROPDOWN --}}
                        <div x-show="showProfileMenu"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.stop
                             class="absolute right-0 top-full mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 py-1 z-50 overflow-hidden">

                            {{-- User info header --}}
                            <a :href="profileUrl()" @click="showProfileMenu = false"
                               class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <div class="w-11 h-11 rounded-full overflow-hidden bg-gray-200 shrink-0">
                                    <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                        <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 truncate text-sm" x-text="username"></p>
                                            <p class="text-xs text-gray-500">{{ $isEnglish ? 'View profile' : 'Lihat profil' }}</p>
                                </div>
                            </a>

                            {{-- Menu items --}}
                            @php
                                $menuItems = [
                                    ['label' => 'Home',       'route' => 'app.home',           'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                                    ['label' => $isEnglish ? 'Search' : 'Cari',        'route' => 'app.search',         'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                                    ['label' => $isEnglish ? 'Saved' : 'Koleksi',      'route' => 'app.favorites',      'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'],
                                    ['label' => $isEnglish ? 'Create Recipe' : 'Buat Resep', 'route' => 'app.recipe.create',  'icon' => 'M12 4v16m8-8H4'],
                                    ['label' => $isEnglish ? 'Notifications' : 'Notifikasi', 'route' => 'app.notifications',  'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'badge' => true],
                                    ['label' => 'Chef AI',    'route' => 'app.ai',             'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                                    ['label' => $isEnglish ? 'Profile' : 'Profil',     'route' => null,                 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'dynamic' => true],
                                    ['label' => $isEnglish ? 'Settings' : 'Pengaturan','route' => 'app.settings',       'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                                ];
                            @endphp

                            @foreach($menuItems as $item)
                                @if(!empty($item['dynamic']))
                                    <a :href="profileUrl()" @click="showProfileMenu = false"
                                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-gray-700">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="font-medium text-sm">{{ $item['label'] }}</span>
                                    </a>
                                @elseif(!empty($item['route']))
                                    <a href="{{ route($item['route']) }}" @click="showProfileMenu = false"
                                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors
                                              {{ request()->routeIs($item['route']) ? 'text-[#E76F51] bg-orange-50' : 'text-gray-700' }}">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="font-medium text-sm flex-1">{{ $item['label'] }}</span>
                                        @if(!empty($item['badge']))
                                            <span x-show="unreadCount > 0"
                                                  x-text="unreadCount > 99 ? '99+' : unreadCount"
                                                  class="min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full px-1">
                                            </span>
                                        @endif
                                    </a>
                                @endif
                            @endforeach

                            <div class="border-t border-gray-100 mt-1"></div>
                            <button @click="handleLogout(); showProfileMenu = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 transition-colors text-red-600">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="font-medium text-sm">Keluar</span>
                            </button>
                        </div>
                    </div>
                    {{-- END DESKTOP PROFILE DROPDOWN --}}

                </div>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════
         BOTTOM NAVIGATION — Mobile only
    ══════════════════════════════════════════════════════════ --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
        <div class="flex items-end justify-between px-4 py-1" style="padding-bottom: env(safe-area-inset-bottom, 4px)">

            {{-- Home --}}
            <a href="{{ route('app.home') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform">
                <svg class="w-6 h-6 {{ request()->routeIs('app.home') ? 'text-[#E76F51]' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-semibold {{ request()->routeIs('app.home') ? 'text-[#E76F51]' : 'text-gray-400' }}">Home</span>
            </a>

            {{-- Search --}}
            <a href="{{ route('app.search') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform">
                <svg class="w-6 h-6 {{ request()->routeIs('app.search') ? 'text-[#E76F51]' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-[10px] font-semibold {{ request()->routeIs('app.search') ? 'text-[#E76F51]' : 'text-gray-400' }}">{{ $isEnglish ? 'Search' : 'Cari' }}</span>
            </a>

            {{-- Create (center FAB) --}}
            <div class="flex flex-col items-center flex-1 pb-1">
                <a href="{{ route('app.recipe.create') }}" class="relative -mt-5 active:scale-90 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-r from-[#E76F51] to-[#F4A261] rounded-full shadow-xl flex items-center justify-center text-white ring-4 ring-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                </a>
            </div>

            {{-- Favorites --}}
            <a href="{{ route('app.favorites') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform">
                <svg class="w-6 h-6 {{ request()->routeIs('app.favorites*') ? 'text-[#E76F51]' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <span class="text-[10px] font-semibold {{ request()->routeIs('app.favorites*') ? 'text-[#E76F51]' : 'text-gray-400' }}">{{ $isEnglish ? 'Saved' : 'Simpan' }}</span>
            </a>

            {{-- Profile — opens mobile dropdown --}}
            <div class="relative flex-1">
                <button @click.stop="showMobileMenu = !showMobileMenu"
                        class="flex flex-col items-center gap-0.5 w-full py-1.5 active:scale-95 transition-transform">
                    <div class="w-7 h-7 rounded-full overflow-hidden border-2 shrink-0
                                {{ request()->routeIs('app.profile*') ? 'border-[#E76F51]' : 'border-gray-300' }}">
                        <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                            <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                    <span class="text-[10px] font-semibold {{ request()->routeIs('app.profile*') ? 'text-[#E76F51]' : 'text-gray-400' }}">{{ $isEnglish ? 'Profile' : 'Profil' }}</span>
                </button>

                {{-- MOBILE DROPDOWN — anchored above bottom nav --}}
                <div x-show="showMobileMenu"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop
                     class="absolute bottom-full right-0 mb-2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 py-1 z-50 overflow-hidden text-left">

                    {{-- User info header --}}
                    <a :href="profileUrl()" @click="showMobileMenu = false"
                       class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="w-11 h-11 rounded-full overflow-hidden bg-gray-200 shrink-0">
                            <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 truncate text-sm" x-text="username"></p>
                            <p class="text-xs text-gray-500">Lihat profil</p>
                        </div>
                    </a>

                    {{-- Menu items --}}
                    @foreach($menuItems as $item)
                        @if(!empty($item['dynamic']))
                            <a :href="profileUrl()" @click="showMobileMenu = false"
                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-gray-700">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="font-medium text-sm">{{ $item['label'] }}</span>
                            </a>
                        @elseif(!empty($item['route']))
                            <a href="{{ route($item['route']) }}" @click="showMobileMenu = false"
                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors
                                      {{ request()->routeIs($item['route']) ? 'text-[#E76F51] bg-orange-50' : 'text-gray-700' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="font-medium text-sm flex-1">{{ $item['label'] }}</span>
                                @if(!empty($item['badge']))
                                    <span x-show="unreadCount > 0"
                                          x-text="unreadCount > 99 ? '99+' : unreadCount"
                                          class="min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full px-1">
                                    </span>
                                @endif
                            </a>
                        @endif
                    @endforeach

                    <div class="border-t border-gray-100 mt-1"></div>

                    {{-- Logout — di dalam dropdown menu --}}
                    <button @click="handleLogout(); showMobileMenu = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 transition-colors text-red-600">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="font-medium text-sm">Keluar</span>
                    </button>
                </div>
            </div>
            {{-- END PROFILE BOTTOM NAV --}}

        </div>
    </nav>

    </div>
</div>
