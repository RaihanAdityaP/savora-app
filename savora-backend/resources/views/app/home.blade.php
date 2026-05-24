@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body style="background: var(--color-bg-light); color: var(--color-text-primary);">

    <x-unified-navigation
        :avatar-url="$profile['avatar_url'] ?? null"
        :unread-count="$unreadCount"
        :username="$profile['username'] ?? null"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Welcome Card --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl p-6"
             style="background: linear-gradient(135deg, var(--color-primary-coral), var(--color-primary-orange), var(--color-primary-yellow));">

            {{-- Overlay for text contrast on light mode --}}
            <div class="welcome-overlay-layer"></div>

            {{-- Decorative circles --}}
            <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full pointer-events-none"
                 style="background: rgba(255,255,255,0.10)"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 rounded-full pointer-events-none"
                 style="background: rgba(255,255,255,0.07)"></div>

            <div class="relative">
                {{-- Greeting --}}
                <div class="flex items-center gap-4 mb-5">
                    <div class="p-3 rounded-2xl border-2 welcome-chip shrink-0">
                        <svg class="w-7 h-7 text-white welcome-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold truncate welcome-text">{{ $isEnglish ? 'Hi' : 'Halo' }}, {{ $profile['username'] ?? 'Foodie' }}!</h1>
                        <p class="text-sm welcome-text" style="opacity: 0.92;">{{ $isEnglish ? 'Welcome back to Savora' : 'Selamat datang kembali di Savora' }}</p>
                    </div>
                </div>

                {{-- Stats chips --}}
                <div class="grid grid-cols-3 gap-3 mb-5">
                    {{-- Resep Saya --}}
                    <div class="rounded-2xl p-3 border-2 text-center welcome-chip">
                        <svg class="w-6 h-6 mx-auto mb-1 welcome-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="text-xl font-bold leading-none welcome-text">{{ $myRecipesCount }}</p>
                        <p class="text-xs font-semibold mt-1 welcome-text" style="opacity: 0.90;">{{ $isEnglish ? 'My Recipes' : 'Resep Saya' }}</p>
                    </div>

                    {{-- Tersimpan --}}
                    <div class="rounded-2xl p-3 border-2 text-center welcome-chip">
                        <svg class="w-6 h-6 mx-auto mb-1 welcome-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <p class="text-xl font-bold leading-none welcome-text">{{ $bookmarksCount }}</p>
                        <p class="text-xs font-semibold mt-1 welcome-text" style="opacity: 0.90;">{{ $isEnglish ? 'Saved' : 'Tersimpan' }}</p>
                    </div>

                    {{-- Pengikut --}}
                    <div class="rounded-2xl p-3 border-2 text-center welcome-chip">
                        <svg class="w-6 h-6 mx-auto mb-1 welcome-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-xl font-bold leading-none welcome-text">{{ $followersCount }}</p>
                        <p class="text-xs font-semibold mt-1 welcome-text" style="opacity: 0.90;">{{ $isEnglish ? 'Followers' : 'Pengikut' }}</p>
                    </div>
                </div>

                {{-- Daily quote --}}
                @php
                    $quotes = [
                        ['q' => 'People who love to eat are always the best people.', 'a' => 'Julia Child'],
                        ['q' => 'Cooking is like love. It should be entered into with abandon.', 'a' => 'Harriet Van Horne'],
                        ['q' => 'I think food is, actually, very beautiful in itself.', 'a' => 'Delia Smith'],
                        ['q' => 'Learn how to cook—try new recipes, be fearless and have fun!', 'a' => 'Julia Child'],
                        ['q' => 'Food is everything we are. It\'s an extension of personal history.', 'a' => 'Anthony Bourdain'],
                    ];
                    $dayIndex = (int) date('z') % count($quotes);
                    $todayQuote = $quotes[$dayIndex];
                @endphp
                <div class="rounded-2xl p-4 border-2 welcome-quote">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="p-2 rounded-xl welcome-chip">
                            <svg class="w-4 h-4 welcome-text" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold tracking-wide welcome-text" style="opacity: 0.95;">{{ $isEnglish ? 'Today Inspiration' : 'Inspirasi Hari Ini' }}</span>
                    </div>
                    <p class="italic text-sm font-medium leading-relaxed mb-2 welcome-text">"{{ $todayQuote['q'] }}"</p>
                    <div class="flex items-center gap-2">
                        <div class="w-0.5 h-4 rounded-full" style="background: rgba(255,255,255,0.55)"></div>
                        <p class="text-xs font-semibold welcome-text" style="opacity: 0.88;">{{ $todayQuote['a'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section header: Untuk Kamu --}}
        <div class="flex items-center justify-between mb-4 px-1">
            <x-app-theme.section-header
                title="{{ $isEnglish ? 'For You' : 'Untuk Kamu' }}"
                icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>'
            />
            <div class="flex items-center gap-2">
                <span class="btn-primary-savora text-xs" style="padding: 4px 14px; border-radius: var(--radius-full); box-shadow: none;">FYP</span>
                <span class="px-3 py-1 text-xs font-bold rounded-full flex items-center gap-1"
                      style="background: var(--color-card-bg); border: 1px solid rgba(231,111,81,0.30); color: var(--color-primary-coral);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ count($feed ?? []) }}
                </span>
            </div>
        </div>

        {{-- Feed --}}
        @forelse($feed ?? [] as $recipe)
            <x-recipe-card
                :recipe="$recipe"
                :rating="$recipe['rating_avg'] ?? null"
                :current-user-id="session('user_id')"
                :favorite-boards="$favoriteBoards ?? []"
                :saved-board-ids="$recipeSavedBoards[$recipe['id']] ?? []"
                :detail-href="route('app.recipe.show', $recipe['id'])"
            />
        @empty
            <x-app-theme.empty-state
                icon="bi bi-journal-richtext"
                title="{{ $isEnglish ? 'No Recipes Yet' : 'Belum Ada Resep' }}"
                subtitle="{{ $isEnglish ? 'Be the first to share a delicious recipe!' : 'Jadilah yang pertama membagikan resep lezat!' }}"
            >
                <a href="{{ route('app.recipe.create') }}" class="btn-primary-savora mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $isEnglish ? 'Create First Recipe' : 'Buat Resep Pertama' }}
                </a>
            </x-app-theme.empty-state>
        @endforelse

        {{-- Load More --}}
        @if($hasMore ?? false)
            <div class="text-center mt-4">
                <a href="{{ route('app.home', ['offset' => ($offset ?? 0) + count($feed ?? [])]) }}"
                   class="btn-primary-savora">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ $isEnglish ? 'Load More' : 'Muat Lebih Banyak' }}
                </a>
            </div>
        @elseif(count($feed ?? []) > 0)
            <div class="flex items-center justify-center gap-2 py-4 app-body-small">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color: var(--color-text-secondary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $isEnglish ? 'You have seen all recipes for you' : 'Kamu sudah melihat semua resep untukmu' }}
            </div>
        @endif

    </div>
</body>
</html>
