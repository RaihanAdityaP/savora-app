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
    $ratingValue    = $rating ?? ($recipe['rating_avg'] ?? null);
    $ratingCount    = (int) ($recipe['rating_count'] ?? 0);
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

        {{-- Rating badge (bottom-left) --}}
        <div class="absolute bottom-4 left-4 pointer-events-none">
            @if ($ratingValue !== null && (float) $ratingValue > 0)
                <div class="px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1 shadow"
                     style="background: #F59E0B; color: #1C1917;">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span>{{ number_format((float) $ratingValue, 1) }}</span>
                    @if($ratingCount > 0)
                        <span class="font-semibold text-xs" style="opacity: 0.80;">({{ $ratingCount }})</span>
                    @endif
                </div>
            @else
                <div class="px-3 py-1.5 rounded-full text-xs font-semibold shadow"
                     style="background: rgba(0,0,0,0.55); backdrop-filter: blur(6px); color: #ffffff;">
                    {{ $isEnglish ? 'No rating yet' : 'Belum ada rating' }}
                </div>
            @endif
        </div>

        {{-- Difficulty badge (bottom center, agar category tetap bottom-right) --}}
        @if (!empty($recipe['difficulty']))
            @php
                $diffColors = ['easy'=>'#22c55e','medium'=>'#eab308','hard'=>'#ef4444','mudah'=>'#22c55e','sedang'=>'#eab308','sulit'=>'#ef4444'];
                $diffLabels = $isEnglish
                    ? ['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','mudah'=>'Easy','sedang'=>'Medium','sulit'=>'Hard']
                    : ['easy'=>'Mudah','medium'=>'Sedang','hard'=>'Sulit','mudah'=>'Mudah','sedang'=>'Sedang','sulit'=>'Sulit'];
                $diff = strtolower($recipe['difficulty']);
            @endphp
            <span class="absolute bottom-4 left-1/2 -translate-x-1/2 px-2.5 py-1 text-white text-xs font-bold shadow rounded-full pointer-events-none"
                  style="background: {{ $diffColors[$diff] ?? '#6b7280' }};">
                {{ $diffLabels[$diff] ?? ucfirst($recipe['difficulty']) }}
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
            @if ($ratingValue !== null && (float) $ratingValue > 0)
                <span class="shrink-0 text-sm font-bold flex items-center gap-0.5 px-2 py-1 rounded-lg"
                      style="background: rgba(231,111,81,0.10); color: var(--color-primary-coral);">
                    <svg class="w-4 h-4" style="color: #F59E0B;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    {{ number_format((float) $ratingValue, 1) }}
                </span>
            @endif
        </div>

        {{-- Description --}}
        @if (!empty($recipe['description']))
            <p class="text-sm mb-4 line-clamp-2 leading-relaxed" style="color: var(--color-text-secondary);">
                {{ $recipe['description'] }}
            </p>
        @endif

        {{-- Meta chips --}}
        <div class="flex flex-wrap gap-2 mb-4">
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
