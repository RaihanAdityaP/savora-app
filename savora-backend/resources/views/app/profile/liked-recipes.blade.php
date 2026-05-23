@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ session('user_language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Liked Recipes' : 'Resep Disukai' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">
    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="$appUnreadCount ?? 0"
        :username="session('user_username')"
    />

    <main class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('app.profile.user', $profile['id']) }}" class="btn-icon-savora w-11 h-11 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold" style="color: var(--color-text-primary);">{{ $isEnglish ? 'Liked Recipes' : 'Resep Disukai' }}</h1>
                <p class="text-sm" style="color: var(--color-text-secondary);">{{ $profile['username'] ?? 'User' }}</p>
            </div>
        </div>

        @forelse($recipes as $recipe)
            <x-recipe-card
                :recipe="$recipe"
                :current-user-id="$currentUserId"
                :likes-count="$recipe['likes_count'] ?? 0"
                :is-liked="$recipe['is_liked'] ?? false"
            />
        @empty
            <x-app-theme.empty-state
                icon="bi bi-heart"
                title="{{ $isEnglish ? 'No liked recipes yet' : 'Belum ada resep disukai' }}"
                subtitle="{{ $isEnglish ? 'Liked recipes will appear here.' : 'Resep yang disukai akan muncul di sini.' }}" />
        @endforelse
    </main>
</body>
</html>
