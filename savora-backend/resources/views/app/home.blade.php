<!DOCTYPE html>
<html lang="id">
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
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="$profile['avatar_url'] ?? null"
        :unread-count="$unreadCount"
        :username="$profile['username'] ?? null"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Welcome Card --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl p-6 text-white"
             style="background: var(--gradient-primary)">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>

            <div class="relative">
                {{-- Greeting --}}
                <div class="flex items-center gap-4 mb-5">
                    <div class="p-3 rounded-2xl border-2" style="background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.40)">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold truncate">Halo, {{ $profile['username'] ?? 'Foodie' }}!</h1>
                        <p class="text-white/90 text-sm">Selamat datang kembali di Savora</p>
                    </div>
                </div>

                {{-- Stats chips --}}
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="rounded-2xl p-3 border-2 text-center"
                         style="background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.40)">
                        <svg class="w-6 h-6 mx-auto mb-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="text-xl font-bold leading-none text-white">{{ $myRecipesCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Resep Saya</p>
                    </div>
                    <div class="rounded-2xl p-3 border-2 text-center"
                         style="background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.40)">
                        <svg class="w-6 h-6 mx-auto mb-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <p class="text-xl font-bold leading-none text-white">{{ $bookmarksCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Tersimpan</p>
                    </div>
                    <div class="rounded-2xl p-3 border-2 text-center"
                         style="background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.40)">
                        <svg class="w-6 h-6 mx-auto mb-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-xl font-bold leading-none text-white">{{ $followersCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Pengikut</p>
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
                <div class="rounded-2xl p-4 border-2"
                     style="background: rgba(255,255,255,0.20); border-color: rgba(255,255,255,0.40)">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="p-2 rounded-xl" style="background: rgba(255,255,255,0.25)">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-white/95 tracking-wide">Inspirasi Hari Ini</span>
                    </div>
                    <p class="text-white italic text-sm font-medium leading-relaxed mb-2">"{{ $todayQuote['q'] }}"</p>
                    <div class="flex items-center gap-2">
                        <div class="w-0.5 h-4 rounded-full" style="background: rgba(255,255,255,0.60)"></div>
                        <p class="text-white/90 text-xs font-semibold">{{ $todayQuote['a'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section header: Untuk Kamu --}}
        <div class="flex items-center justify-between mb-4 px-1">
            <x-app-theme.section-header
                title="Untuk Kamu"
                icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>'
            />
            <div class="flex items-center gap-2">
                <span class="btn-primary-savora text-xs" style="padding: 4px 14px; border-radius: var(--radius-full); box-shadow: none">FYP</span>
                <span class="px-3 py-1 bg-white text-xs font-bold rounded-full flex items-center gap-1"
                      style="border: 1px solid rgba(231,111,81,0.30); color: var(--color-primary-coral)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ count($feed ?? []) }}
                </span>
            </div>
        </div>

        {{-- Feed --}}
        @forelse($feed ?? [] as $recipe)
            @php
                $author      = $recipe['profiles'] ?? [];
                $category    = $recipe['categories']['name'] ?? null;
                $tags        = collect($recipe['recipe_tags'] ?? [])->pluck('tags.name')->filter()->take(3)->toArray();
                $rating      = $recipe['rating_avg'] ?? null;
                $ratingCount = $recipe['rating_count'] ?? 0;
            @endphp

            <a href="{{ route('app.recipe.show', $recipe['id']) }}"
               class="card-savora block overflow-hidden hover:shadow-lg transition-all duration-300 mb-4 active:scale-[0.98]">

                {{-- Image --}}
                <div class="relative h-52 overflow-hidden" style="background: #e5e7eb">
                    @if(!empty($recipe['image_url']))
                        <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}"
                             class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center"
                             style="background: var(--gradient-primary)">
                            <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif

                    @if($category)
                        <span class="absolute top-3 left-3 px-3 py-1 text-white text-xs font-bold shadow"
                              style="background: var(--gradient-accent); border-radius: var(--radius-full)">
                            {{ $category }}
                        </span>
                    @endif

                    @if($rating && $rating > 0)
                        <div class="absolute bottom-3 left-3 bg-yellow-400 text-yellow-900 px-2.5 py-1 text-xs font-bold flex items-center gap-1 shadow"
                             style="border-radius: var(--radius-full)">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            {{ number_format($rating, 1) }}
                            @if($ratingCount > 0)<span class="opacity-75">({{ $ratingCount }})</span>@endif
                        </div>
                    @endif

                    @if(!empty($recipe['difficulty']))
                        @php
                            $diffColors = ['easy' => '#22c55e', 'medium' => '#eab308', 'hard' => '#ef4444'];
                            $diffLabels = ['easy' => 'Mudah', 'medium' => 'Sedang', 'hard' => 'Sulit'];
                            $diff = strtolower($recipe['difficulty']);
                        @endphp
                        <span class="absolute bottom-3 right-3 px-2.5 py-1 text-white text-xs font-bold shadow"
                              style="background: {{ $diffColors[$diff] ?? '#6b7280' }}; border-radius: var(--radius-full)">
                            {{ $diffLabels[$diff] ?? ucfirst($diff) }}
                        </span>
                    @endif
                </div>

                {{-- Content --}}
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-1 line-clamp-2" style="color: var(--color-text-primary)">{{ $recipe['title'] ?? 'Resep' }}</h3>

                    @if(!empty($recipe['description']))
                        <p class="text-sm mb-3 line-clamp-2" style="color: var(--color-text-secondary)">{{ $recipe['description'] }}</p>
                    @endif

                    {{-- Meta --}}
                    <div class="flex items-center gap-4 text-sm mb-3" style="color: var(--color-text-secondary)">
                        @if(!empty($recipe['cook_time']))
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" style="color: var(--color-primary-coral)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $recipe['cook_time'] }} mnt</span>
                            </div>
                        @endif
                        @if(!empty($recipe['servings']))
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" style="color: var(--color-primary-coral)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ $recipe['servings'] }} porsi</span>
                            </div>
                        @endif
                        @if(!empty($recipe['calories']))
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" style="color: var(--color-primary-coral)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                                </svg>
                                <span>{{ $recipe['calories'] }} kal</span>
                            </div>
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if(count($tags) > 0)
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            @foreach($tags as $tag)
                                <span class="tag-chip" style="padding: 3px 10px; font-size: 11px; border-radius: var(--radius-full); border-color: rgba(231,111,81,0.20); background: rgba(231,111,81,0.08); color: var(--color-primary-coral)">
                                    #{{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Author --}}
                    <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                        @if(!empty($author['avatar_url']))
                            <img src="{{ $author['avatar_url'] }}" alt="{{ $author['username'] }}"
                                 class="w-8 h-8 rounded-full object-cover border-2"
                                 style="border-color: rgba(231,111,81,0.30)">
                        @else
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                 style="background: var(--gradient-accent)">
                                {{ strtoupper(substr($author['username'] ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate" style="color: var(--color-text-primary)">{{ $author['username'] ?? 'Unknown' }}</p>
                        </div>
                        @if(!empty($author['role']) && $author['role'] === 'admin')
                            <span class="role-badge admin">Admin</span>
                        @endif
                    </div>
                </div>
            </a>

        @empty
            <x-app-theme.empty-state
                icon="bi bi-journal-richtext"
                title="Belum Ada Resep"
                subtitle="Jadilah yang pertama membagikan resep lezat!"
            >
                <a href="{{ route('web.recipe.create') }}" class="btn-primary-savora mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Resep Pertama
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
                    Muat Lebih Banyak
                </a>
            </div>
        @elseif(count($feed ?? []) > 0)
            <div class="flex items-center justify-center gap-2 py-4 app-body-small">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Kamu sudah melihat semua resep untukmu
            </div>
        @endif

    </div>
</body>
</html>