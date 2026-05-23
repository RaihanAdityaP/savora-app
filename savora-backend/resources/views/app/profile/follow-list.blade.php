@php
    $isEnglish = session('user_language', 'en') === 'en';
    $pageTitle = $type === 'followers'
        ? ($isEnglish ? 'Followers' : 'Pengikut')
        : ($isEnglish ? 'Following' : 'Mengikuti');
@endphp
<!DOCTYPE html>
<html lang="{{ session('user_language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('components.app-theme')
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">
    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="$appUnreadCount ?? 0"
        :username="session('user_username')"
    />

    <main class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('app.profile.user', $profile['id']) }}" class="btn-icon-savora w-11 h-11 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold" style="color: var(--color-text-primary);">{{ $pageTitle }}</h1>
                <p class="text-sm" style="color: var(--color-text-secondary);">{{ $profile['username'] ?? 'User' }}</p>
            </div>
        </div>

        <div class="card-savora p-4">
            @forelse($users as $user)
                <a href="{{ route('app.profile.user', $user['id']) }}" class="flex items-center gap-3 p-3 rounded-2xl transition-all hover:opacity-90" style="color: inherit;">
                    @if(!empty($user['avatar_url']))
                        <img src="{{ $user['avatar_url'] }}" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold" style="background: var(--gradient-accent);">
                            {{ strtoupper(substr($user['username'] ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-bold truncate" style="color: var(--color-text-primary);">{{ $user['username'] ?? 'Unknown' }}</p>
                        @if(!empty($user['full_name']))
                            <p class="text-sm truncate" style="color: var(--color-text-secondary);">{{ $user['full_name'] }}</p>
                        @endif
                    </div>
                    <svg class="w-4 h-4" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @empty
                <x-app-theme.empty-state
                    icon="bi bi-people"
                    title="{{ $type === 'followers' ? ($isEnglish ? 'No followers yet' : 'Belum ada pengikut') : ($isEnglish ? 'Not following anyone yet' : 'Belum mengikuti siapa pun') }}" />
            @endforelse
        </div>
    </main>
</body>
</html>
