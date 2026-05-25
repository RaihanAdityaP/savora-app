@props([
    'recipe',
    'rating' => null,
    'currentUserId' => null,
    'favoriteBoards' => [],
    'savedBoardIds' => [],
    'detailHref' => null,
    'likesCount' => null,
    'isLiked' => null,
])

@php
    $profiles       = $recipe['profiles'] ?? [];
    $isEnglish      = session('user_language', 'en') === 'en';
    $authorBlock    = $recipe['author'] ?? [];
    $categoryBlock  = $recipe['categories'] ?? null;
    $categoryName   = $recipe['category'] ?? (is_array($categoryBlock) ? ($categoryBlock['name'] ?? null) : null);
    $title          = $recipe['title'] ?? $recipe['name'] ?? ($isEnglish ? 'Recipe' : 'Resep');
    $imageUrl       = $recipe['image_url'] ?? $recipe['image'] ?? null;
    $authorName     = $authorBlock['name'] ?? ($profiles['username'] ?? null);
    $authorAvatar   = $authorBlock['avatar'] ?? ($profiles['avatar_url'] ?? null);
    $tagList        = $recipe['tags'] ?? collect($recipe['recipe_tags'] ?? [])->pluck('tags.name')->filter()->take(3)->values()->all();
    $prepTime       = $recipe['prep_time'] ?? $recipe['cooking_time'] ?? $recipe['cook_time'] ?? null;
    $ratingInfo     = $recipe['rating_info'] ?? [];
    $ratingValue    = $rating ?? ($recipe['rating_avg'] ?? ($recipe['average_rating'] ?? ($ratingInfo['average'] ?? null)));
    $ratingCount    = (int) ($recipe['rating_count'] ?? ($ratingInfo['total'] ?? 0));
    $diffLabels     = $isEnglish
        ? ['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','mudah'=>'Easy','sedang'=>'Medium','sulit'=>'Hard']
        : ['easy'=>'Mudah','medium'=>'Sedang','hard'=>'Sulit','mudah'=>'Mudah','sedang'=>'Sedang','sulit'=>'Sulit'];
    $diffKey        = strtolower((string) ($recipe['difficulty'] ?? ''));
    $difficultyLabel = $diffKey !== '' ? ($diffLabels[$diffKey] ?? ucfirst((string) $recipe['difficulty'])) : null;
    $resolvedLikesCount = (int) ($likesCount ?? ($recipe['likes_count'] ?? 0));
    $resolvedIsLiked = (bool) ($isLiked ?? ($recipe['is_liked'] ?? false));
    $linkHref       = $detailHref ?? (isset($recipe['id']) ? route('app.recipe.show', $recipe['id']) : '#');
    $loggedIn       = ! empty($currentUserId);
@endphp

<div
    x-data="{ openBoardSelector: false }"
    class="recipe-card-surface rounded-3xl overflow-hidden mb-5 transition-all duration-300 hover:shadow-2xl active:scale-[0.99]"
    style="background: var(--color-card-bg); border: 1.5px solid var(--color-card-border); box-shadow: var(--shadow-card);"
>
    {{-- Image area --}}
    <div class="relative overflow-hidden" style="height: 260px; background: var(--color-chip-bg);">
        <a href="{{ $linkHref }}" class="block w-full h-full" aria-label="{{ $isEnglish ? 'Open recipe' : 'Buka resep' }} {{ $title }}">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $title }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center" style="background: var(--gradient-accent);">
                    <svg class="w-20 h-20" style="color: rgba(255,255,255,0.45);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
        </a>

        {{--
            Bookmark button
            Background selalu putih transparan, icon SELALU gelap (#374151) supaya
            kontras terhadap bg putih di light maupun dark mode.
        --}}
        @if ($loggedIn)
            <button
                type="button"
                @click.stop.prevent="openBoardSelector = true"
                class="absolute top-4 left-4 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-all z-10"
                style="background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);"
                aria-label="{{ $isEnglish ? 'Save to collection' : 'Simpan ke koleksi' }}"
            >
                <svg class="w-5 h-5" style="color: #374151;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </button>
        @else
            <a
                href="{{ route('app.login') }}"
                @click.stop
                class="absolute top-4 left-4 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-all z-10"
                style="background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);"
                aria-label="{{ $isEnglish ? 'Log in to save' : 'Login untuk simpan' }}"
            >
                <svg class="w-5 h-5" style="color: #374151;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </a>
        @endif

        @if ($loggedIn && !empty($recipe['id']))
            <form action="{{ route('app.recipe.like', $recipe['id']) }}" method="POST" class="absolute top-4 right-4 z-10">
                @csrf
                <button
                    type="submit"
                    class="min-w-10 h-10 px-3 rounded-full flex items-center justify-center gap-1.5 shadow-lg transition-all"
                    style="background: {{ $resolvedIsLiked ? 'var(--gradient-accent)' : 'rgba(255,255,255,0.92)' }}; color: {{ $resolvedIsLiked ? '#ffffff' : '#374151' }}; backdrop-filter: blur(8px);"
                    aria-label="{{ $resolvedIsLiked ? ($isEnglish ? 'Unlike recipe' : 'Batalkan like') : ($isEnglish ? 'Like recipe' : 'Like resep') }}"
                >
                    <svg class="w-5 h-5" fill="{{ $resolvedIsLiked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                    </svg>
                    <span class="text-xs font-bold">{{ $resolvedLikesCount }}</span>
                </button>
            </form>
        @elseif(!empty($recipe['id']))
            <a
                href="{{ route('app.login') }}"
                @click.stop
                class="absolute top-4 right-4 min-w-10 h-10 px-3 rounded-full flex items-center justify-center gap-1.5 shadow-lg transition-all z-10"
                style="background: rgba(255,255,255,0.92); color: #374151; backdrop-filter: blur(8px);"
                aria-label="{{ $isEnglish ? 'Log in to like' : 'Login untuk like' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                </svg>
                <span class="text-xs font-bold">{{ $resolvedLikesCount }}</span>
            </a>
        @endif

        {{-- Category badge --}}
        @if ($categoryName)
            <span class="absolute bottom-4 right-4 px-3 py-1 text-white text-xs font-bold rounded-full shadow pointer-events-none"
                  style="background: var(--gradient-accent);">
                {{ $categoryName }}
            </span>
        @endif

    </div>

    {{-- Content area --}}
    <a href="{{ $linkHref }}" class="block p-5 cursor-pointer no-underline" style="color: inherit;" aria-label="{{ $isEnglish ? 'Open recipe' : 'Buka resep' }} {{ $title }}">

        {{-- Title row --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-xl font-bold leading-tight line-clamp-2 flex-1" style="color: var(--color-text-primary);">
                {{ $title }}
            </h3>
        </div>

        {{-- Description --}}
        @if (!empty($recipe['description']))
            <p class="text-sm mb-4 line-clamp-2 leading-relaxed" style="color: var(--color-text-secondary);">
                {{ $recipe['description'] }}
            </p>
        @endif

        {{-- Meta chips --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @if ($ratingValue !== null && (float) $ratingValue > 0)
                <span class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full"
                      style="background: var(--color-chip-bg); color: var(--color-text-secondary);">
                    <span class="flex items-center gap-0.5" aria-label="{{ number_format((float) $ratingValue, 1) }} dari 5">
                        @for($i = 1; $i <= 5; $i++)
                            @php
                                $ratingNumber = (float) $ratingValue;
                                $isFull = $ratingNumber >= $i;
                                $isHalf = ! $isFull && $ratingNumber >= ($i - 0.5);
                            @endphp
                            @if($isHalf)
                                <span class="relative inline-block text-sm text-gray-300 leading-none">
                                    &#9733;
                                    <span class="absolute inset-0 overflow-hidden text-yellow-400 leading-none" style="width: 50%;">&#9733;</span>
                                </span>
                            @else
                                <span class="text-sm {{ $isFull ? 'text-yellow-400' : 'text-gray-300' }}">&#9733;</span>
                            @endif
                        @endfor
                    </span>
                    <span class="font-bold">{{ number_format((float) $ratingValue, 1) }}</span>
                    @if($ratingCount > 0)
                        <span class="text-xs" style="color: var(--color-text-muted);">({{ $ratingCount }})</span>
                    @endif
                </span>
            @endif
            @if ($prepTime)
                <span class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full"
                      style="background: var(--color-chip-bg); color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $prepTime }} {{ $isEnglish ? 'min' : 'mnt' }}
                </span>
            @endif
            @if (!empty($recipe['servings']))
                <span class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full"
                      style="background: var(--color-chip-bg); color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $recipe['servings'] }} {{ $isEnglish ? 'servings' : 'porsi' }}
                </span>
            @endif
            @if (!empty($recipe['calories']))
                <span class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full"
                      style="background: var(--color-chip-bg); color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                    {{ $recipe['calories'] }} {{ $isEnglish ? 'cal' : 'kal' }}
                </span>
            @endif
            @if ($difficultyLabel)
                <span class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full"
                      style="background: var(--color-chip-bg); color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m0 14h16M8 17V9m4 8V7m4 10v-5"/>
                    </svg>
                    {{ $difficultyLabel }}
                </span>
            @endif
        </div>

        {{-- Tags --}}
        @if (count($tagList) > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($tagList as $tag)
                    <span class="text-xs font-semibold px-3 py-1 rounded-full"
                          style="border: 1px solid rgba(231,111,81,0.22); background: rgba(231,111,81,0.08); color: var(--color-primary-coral);">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Author --}}
        @if ($authorName)
            <div class="flex items-center gap-2 pt-3 border-t" style="border-color: var(--color-separator);">
                @if ($authorAvatar)
                    <img src="{{ $authorAvatar }}" alt="" class="w-9 h-9 rounded-full object-cover border-2 shrink-0"
                         style="border-color: rgba(231,111,81,0.30);">
                @else
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                         style="background: var(--gradient-accent);">
                        {{ strtoupper(substr($authorName, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate" style="color: var(--color-text-primary);">{{ $authorName }}</p>
                </div>
                @if (!empty($profiles['role']) && $profiles['role'] === 'admin')
                    <span class="role-badge admin text-xs shrink-0">Admin</span>
                @endif
            </div>
        @endif
    </a>

    @if(!empty($recipe['id']))
        <x-save-collection-sheet
            :recipe-id="$recipe['id']"
            :boards="$favoriteBoards"
            :saved-board-ids="$savedBoardIds"
        />
    @endif
</div>
