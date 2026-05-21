@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Notifications' : 'Notifikasi' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
</head>
<body style="background: var(--color-bg-light); color: var(--color-text-primary);">

    <x-unified-navigation
        :avatar-url="session('user_avatar') ?? null"
        :unread-count="$unreadCount ?? 0"
        :username="session('user_username') ?? null"
    />

    <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <x-app-theme.section-header
                    :title="$isEnglish ? 'Notifications' : 'Notifikasi'"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>'
                />
                @if(($unreadCount ?? 0) > 0)
                    <span class="badge-savora">{{ $unreadCount }} {{ $isEnglish ? 'new' : 'baru' }}</span>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2" x-data="{ open: false }">
                @if(($unreadCount ?? 0) > 0)
                    <form action="{{ route('app.notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="p-2.5 bg-white rounded-full shadow hover:shadow-md transition-all"
                                style="color: var(--color-primary-teal)"
                                title="{{ $isEnglish ? 'Mark all as read' : 'Tandai semua dibaca' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </button>
                    </form>
                @endif
                <div class="relative">
                    <button @click="open = !open" class="p-2.5 bg-white rounded-full shadow hover:shadow-md transition-all text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-1 z-10">
                        <form action="{{ route('app.notifications.delete-all') }}" method="POST"
                              onsubmit="return confirm('{{ $isEnglish ? 'Delete all notifications?' : 'Hapus semua notifikasi?' }}')">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 transition-colors text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                {{ $isEnglish ? 'Delete All' : 'Hapus Semua' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status flash --}}
        @if(session('status'))
            <div class="mb-4">
                <x-app-theme.info-banner message="{{ session('status') }}" icon="bi bi-check-circle" />
            </div>
        @endif

        {{-- Notifications list --}}
        @forelse($notifications as $notif)
            @php
                $isRead = $notif['is_read'] ?? false;
                $type   = $notif['type'] ?? 'system';

                // Inline CSS colors — hindari dynamic Tailwind class generation
                $colorMap = [
                    'recipe_approved'           => ['bg' => '#22c55e', 'light' => 'rgba(34,197,94,0.08)',   'border' => 'rgba(34,197,94,0.35)',   'text' => '#16a34a'],
                    'recipe_rejected'           => ['bg' => '#ef4444', 'light' => 'rgba(239,68,68,0.08)',   'border' => 'rgba(239,68,68,0.35)',   'text' => '#dc2626'],
                    'new_follower'              => ['bg' => '#2A9D8F', 'light' => 'rgba(42,157,143,0.08)',  'border' => 'rgba(42,157,143,0.35)',  'text' => '#2A9D8F'],
                    'new_recipe_from_following' => ['bg' => '#F4A261', 'light' => 'rgba(244,162,97,0.08)',  'border' => 'rgba(244,162,97,0.35)',  'text' => '#d97706'],
                    'admin'                     => ['bg' => '#E76F51', 'light' => 'rgba(231,111,81,0.08)',  'border' => 'rgba(231,111,81,0.35)',  'text' => '#E76F51'],
                ];
                $c = $colorMap[$type] ?? ['bg' => '#9ca3af', 'light' => 'rgba(156,163,175,0.08)', 'border' => 'rgba(156,163,175,0.30)', 'text' => '#6b7280'];

                $iconMap = [
                    'recipe_approved'           => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'recipe_rejected'           => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'new_follower'              => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    'new_recipe_from_following' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                    'admin'                     => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ];
                $iconPath = $iconMap[$type] ?? 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';

                try {
                    $diff = \Carbon\Carbon::parse($notif['created_at'])->diffForHumans();
                } catch (\Exception $e) {
                    $diff = $notif['created_at'] ?? '';
                }
            @endphp

            <div class="card-savora relative mb-3 overflow-hidden transition-all"
                 style="{{ !$isRead ? 'border-color:' . $c['border'] . '; box-shadow: 0 4px 16px ' . $c['light'] . ';' : '' }}">

                @if(!$isRead)
                    <div class="absolute inset-0 pointer-events-none" style="background: {{ $c['light'] }}"></div>
                @endif

                <div class="relative p-4 flex items-start gap-4">
                    {{-- Icon --}}
                    <div class="w-12 h-12 flex items-center justify-center flex-shrink-0"
                         style="background: {{ $c['light'] }}; border: 2px solid {{ $c['border'] }}; border-radius: var(--radius-md)">
                        <svg class="w-6 h-6" style="color: {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                        </svg>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm leading-snug" style="font-weight: {{ $isRead ? 600 : 700 }}; color: var(--color-text-primary)">
                                {{ $notif['title'] ?? ($isEnglish ? 'Notification' : 'Notifikasi') }}
                            </p>
                            @if(!$isRead)
                                <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-1"
                                     style="background: {{ $c['bg'] }}; box-shadow: 0 0 6px {{ $c['bg'] }}99"></div>
                            @endif
                        </div>
                        <p class="text-sm mt-1 leading-relaxed" style="color: var(--color-text-secondary)">{{ $notif['message'] ?? '' }}</p>
                        <div class="flex items-center gap-1 mt-2">
                            <svg class="w-3.5 h-3.5" style="color: var(--color-text-secondary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="app-body-small font-medium">{{ $diff }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex border-t border-gray-100">
                    @if(!$isRead)
                        <form action="{{ route('app.notifications.read', $notif['id']) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-2.5 text-xs font-semibold hover:bg-teal-50 transition-colors"
                                    style="color: var(--color-primary-teal)">
                                {{ $isEnglish ? 'Mark as Read' : 'Tandai Dibaca' }}
                            </button>
                        </form>
                        <div class="w-px bg-gray-100"></div>
                    @endif
                    <form action="{{ route('app.notifications.delete', $notif['id']) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors">
                            {{ $isEnglish ? 'Delete' : 'Hapus' }}
                        </button>
                    </form>
                </div>
            </div>

        @empty
            <x-app-theme.empty-state
                icon="bi bi-bell-slash"
                :title="$isEnglish ? 'No notifications' : 'Tidak ada notifikasi'"
                :subtitle="$isEnglish ? 'Notifications will appear here' : 'Notifikasi akan muncul di sini'"
            />
        @endforelse

    </div>
</body>
</html>
