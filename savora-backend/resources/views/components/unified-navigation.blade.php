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
        async handleLogout() {
            if (!confirm('Keluar dari akun Savora?')) return;
            this.clearSupabaseBrowserSession();
            document.getElementById('logout-form').submit();
        },
        clearSupabaseBrowserSession() {
            try {
                for (let i = localStorage.length - 1; i >= 0; i--) {
                    const key = localStorage.key(i);
                    if (key && key.startsWith('sb-') && key.includes('auth-token')) {
                        localStorage.removeItem(key);
                    }
                }
            } catch (error) {}

            try {
                for (let i = sessionStorage.length - 1; i >= 0; i--) {
                    const key = sessionStorage.key(i);
                    if (key && key.startsWith('sb-') && key.includes('auth-token')) {
                        sessionStorage.removeItem(key);
                    }
                }
            } catch (error) {}
        },
        profileUrl() {
            return this.userId ? '/profile/' + this.userId : '#';
        }
    }" @click.outside="showProfileMenu = false; showMobileMenu = false">

    {{-- ═══════════════════════════════════════
         TOP NAVIGATION
    ════════════════════════════════════════ --}}
    <header class="sticky top-0 z-50 shadow-sm"
            style="background: var(--color-card-bg); border-bottom: 1px solid var(--color-separator);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- LOGO --}}
                <a href="{{ route('app.home') }}" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                    <div class="relative w-10 h-10 rounded-full p-1 shadow-lg"
                         style="background: linear-gradient(135deg, #2B6CB0, #FF6B35);">
                        <div class="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('storage/images/logo.png') }}" alt="Savora" class="object-cover w-8 h-8"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <span style="display:none; color: #E76F51;" class="font-black text-sm">S</span>
                        </div>
                    </div>
                    <span class="text-xl md:text-2xl font-bold tracking-tight" style="color: var(--color-text-primary);">Savora</span>
                </a>

                {{-- RIGHT ACTIONS --}}
                <div class="flex items-center gap-1">

                    {{-- NOTIFICATION BUTTON --}}
                    <a href="{{ route('app.notifications') }}"
                       class="relative p-2 rounded-full transition-colors"
                       style="color: {{ request()->routeIs('app.notifications*') ? 'var(--color-primary-coral)' : 'var(--color-text-secondary)' }};"
                       onmouseover="this.style.background='var(--color-chip-bg)'"
                       onmouseout="this.style.background='transparent'">
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
                                class="flex items-center gap-1.5 p-1 rounded-full transition-colors focus:outline-none"
                                onmouseover="this.style.background='var(--color-chip-bg)'"
                                onmouseout="this.style.background='transparent'">
                            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0"
                                 style="border: 2px solid var(--color-separator); background: var(--color-chip-bg);">
                                <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                    <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                 style="color: var(--color-text-secondary);"
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
                             class="absolute right-0 top-full mt-2 w-64 rounded-2xl shadow-2xl py-1 z-50 overflow-hidden"
                             style="background: var(--color-card-bg); border: 1.5px solid var(--color-card-border);">

                            {{-- User info header --}}
                            <a :href="profileUrl()" @click="showProfileMenu = false"
                               class="flex items-center gap-3 px-4 py-3 transition-colors"
                               style="border-bottom: 1px solid var(--color-separator);"
                               onmouseover="this.style.background='var(--color-chip-bg)'"
                               onmouseout="this.style.background='transparent'">
                                <div class="w-11 h-11 rounded-full overflow-hidden shrink-0"
                                     style="background: var(--color-chip-bg);">
                                    <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                        <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold truncate text-sm" style="color: var(--color-text-primary);" x-text="username"></p>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'View profile' : 'Lihat profil' }}</p>
                                </div>
                            </a>

                            {{-- Menu items --}}
                            @php
                                $menuItems = [
                                    ['label' => 'Home',       'route' => 'app.home',           'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                                    ['label' => $isEnglish ? 'Search' : 'Cari',        'route' => 'app.search',         'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                                    ['label' => $isEnglish ? 'Saved' : 'Koleksi',      'route' => 'app.favorites',      'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'],
                                    ['label' => $isEnglish ? 'Create Recipe' : 'Buat Resep', 'route' => 'app.recipe.create', 'icon' => 'M12 4v16m8-8H4'],
                                    ['label' => $isEnglish ? 'Notifications' : 'Notifikasi', 'route' => 'app.notifications', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'badge' => true],
                                    ['label' => 'Chef AI',    'route' => 'app.ai',             'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                                    ['label' => $isEnglish ? 'Profile' : 'Profil',     'route' => null,                 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'dynamic' => true],
                                    ['label' => $isEnglish ? 'Settings' : 'Pengaturan','route' => 'app.settings',       'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                                ];
                            @endphp

                            @foreach($menuItems as $item)
                                @php $isActive = !empty($item['route']) && request()->routeIs($item['route']); @endphp
                                @if(!empty($item['dynamic']))
                                    <a :href="profileUrl()" @click="showProfileMenu = false"
                                       class="flex items-center gap-3 px-4 py-2.5 transition-colors"
                                       style="color: var(--color-text-primary);"
                                       onmouseover="this.style.background='var(--color-chip-bg)'"
                                       onmouseout="this.style.background='transparent'">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="font-medium text-sm">{{ $item['label'] }}</span>
                                    </a>
                                @elseif(!empty($item['route']))
                                    <a href="{{ route($item['route']) }}" @click="showProfileMenu = false"
                                       class="flex items-center gap-3 px-4 py-2.5 transition-colors"
                                       style="color: {{ $isActive ? 'var(--color-primary-coral)' : 'var(--color-text-primary)' }}; background: {{ $isActive ? 'rgba(231,111,81,0.08)' : 'transparent' }};"
                                       onmouseover="this.style.background='var(--color-chip-bg)'"
                                       onmouseout="this.style.background='{{ $isActive ? 'rgba(231,111,81,0.08)' : 'transparent' }}'">
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

                            <div style="border-top: 1px solid var(--color-separator); margin-top: 4px;"></div>
                            <button @click="handleLogout(); showProfileMenu = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 transition-colors text-red-500"
                                    onmouseover="this.style.background='rgba(239,68,68,0.08)'"
                                    onmouseout="this.style.background='transparent'">
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

    {{-- ═══════════════════════════════════════
         BOTTOM NAVIGATION — Mobile only
    ════════════════════════════════════════ --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 shadow-lg"
         style="background: var(--color-card-bg); border-top: 1px solid var(--color-separator);">
        <div class="flex items-end justify-between px-4 py-1" style="padding-bottom: env(safe-area-inset-bottom, 4px)">

            {{-- Home --}}
            <a href="{{ route('app.home') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform"
               style="color: {{ request()->routeIs('app.home') ? 'var(--color-primary-coral)' : 'var(--color-text-muted)' }};">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-semibold">Home</span>
            </a>

            {{-- Search --}}
            <a href="{{ route('app.search') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform"
               style="color: {{ request()->routeIs('app.search') ? 'var(--color-primary-coral)' : 'var(--color-text-muted)' }};">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-[10px] font-semibold">{{ $isEnglish ? 'Search' : 'Cari' }}</span>
            </a>

            {{-- Create FAB --}}
            <div class="flex flex-col items-center flex-1 pb-1">
                <a href="{{ route('app.recipe.create') }}" class="relative -mt-5 active:scale-90 transition-transform">
                    <div class="w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white ring-4"
                         style="background: var(--gradient-accent); --tw-ring-color: var(--color-bg-light); color: var(--color-on-accent);">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                </a>
            </div>

            {{-- Favorites --}}
            <a href="{{ route('app.favorites') }}"
               class="flex flex-col items-center gap-0.5 flex-1 py-1.5 active:scale-95 transition-transform"
               style="color: {{ request()->routeIs('app.favorites*') ? 'var(--color-primary-coral)' : 'var(--color-text-muted)' }};">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <span class="text-[10px] font-semibold">{{ $isEnglish ? 'Saved' : 'Simpan' }}</span>
            </a>

            {{-- Profile --}}
            <div class="relative flex-1">
                <button @click.stop="showMobileMenu = !showMobileMenu"
                        class="flex flex-col items-center gap-0.5 w-full py-1.5 active:scale-95 transition-transform"
                        style="color: {{ request()->routeIs('app.profile*') ? 'var(--color-primary-coral)' : 'var(--color-text-muted)' }};">
                    <div class="w-7 h-7 rounded-full overflow-hidden shrink-0"
                         style="border: 2px solid {{ request()->routeIs('app.profile*') ? 'var(--color-primary-coral)' : 'var(--color-separator)' }}; background: var(--color-chip-bg);">
                        <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                            <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-4 h-4" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                    <span class="text-[10px] font-semibold">{{ $isEnglish ? 'Profile' : 'Profil' }}</span>
                </button>

                {{-- MOBILE DROPDOWN --}}
                <div x-show="showMobileMenu"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop
                     class="absolute bottom-full right-0 mb-2 w-64 rounded-2xl shadow-2xl py-1 z-50 overflow-hidden text-left"
                     style="background: var(--color-card-bg); border: 1.5px solid var(--color-card-border);">

                    <a :href="profileUrl()" @click="showMobileMenu = false"
                       class="flex items-center gap-3 px-4 py-3 transition-colors"
                       style="border-bottom: 1px solid var(--color-separator);"
                       onmouseover="this.style.background='var(--color-chip-bg)'"
                       onmouseout="this.style.background='transparent'">
                        <div class="w-11 h-11 rounded-full overflow-hidden shrink-0"
                             style="background: var(--color-chip-bg);">
                            <template x-if="avatarUrl && avatarUrl !== 'null' && avatarUrl !== ''">
                                <img :src="avatarUrl" alt="Profile" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarUrl || avatarUrl === 'null' || avatarUrl === ''">
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-6 h-6" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold truncate text-sm" style="color: var(--color-text-primary);" x-text="username"></p>
                            <p class="text-xs" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'View profile' : 'Lihat profil' }}</p>
                        </div>
                    </a>

                    @foreach($menuItems as $item)
                        @php $isActive = !empty($item['route']) && request()->routeIs($item['route']); @endphp
                        @if(!empty($item['dynamic']))
                            <a :href="profileUrl()" @click="showMobileMenu = false"
                               class="flex items-center gap-3 px-4 py-2.5 transition-colors"
                               style="color: var(--color-text-primary);"
                               onmouseover="this.style.background='var(--color-chip-bg)'"
                               onmouseout="this.style.background='transparent'">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="font-medium text-sm">{{ $item['label'] }}</span>
                            </a>
                        @elseif(!empty($item['route']))
                            <a href="{{ route($item['route']) }}" @click="showMobileMenu = false"
                               class="flex items-center gap-3 px-4 py-2.5 transition-colors"
                               style="color: {{ $isActive ? 'var(--color-primary-coral)' : 'var(--color-text-primary)' }}; background: {{ $isActive ? 'rgba(231,111,81,0.08)' : 'transparent' }};"
                               onmouseover="this.style.background='var(--color-chip-bg)'"
                               onmouseout="this.style.background='{{ $isActive ? 'rgba(231,111,81,0.08)' : 'transparent' }}'">
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

                    <div style="border-top: 1px solid var(--color-separator); margin-top: 4px;"></div>
                    <button @click="handleLogout(); showMobileMenu = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 transition-colors text-red-500"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)'"
                            onmouseout="this.style.background='transparent'">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="font-medium text-sm">Keluar</span>
                    </button>
                </div>
            </div>

        </div>
    </nav>

    </div>
</div>
