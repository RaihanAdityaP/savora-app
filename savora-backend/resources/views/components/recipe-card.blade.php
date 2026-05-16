@props([
    'recipe',
    'rating' => null,
    'currentUserId' => null,
    'favoriteBoards' => [],
    'savedBoardIds' => [],
    'detailHref' => null,
])

@php
    $profiles = $recipe['profiles'] ?? [];
    $authorBlock = $recipe['author'] ?? [];
    $categoryBlock = $recipe['categories'] ?? null;
    $categoryName = $recipe['category'] ?? (is_array($categoryBlock) ? ($categoryBlock['name'] ?? null) : null);
    $title = $recipe['title'] ?? $recipe['name'] ?? 'Resep';
    $imageUrl = $recipe['image_url'] ?? $recipe['image'] ?? null;
    $authorName = $authorBlock['name'] ?? ($profiles['username'] ?? null);
    $authorAvatar = $authorBlock['avatar'] ?? ($profiles['avatar_url'] ?? null);
    $tagList = $recipe['tags'] ?? collect($recipe['recipe_tags'] ?? [])->pluck('tags.name')->filter()->take(3)->values()->all();
    $prepTime = $recipe['prep_time'] ?? $recipe['cooking_time'] ?? $recipe['cook_time'] ?? null;
    $ratingValue = $rating ?? ($recipe['rating_avg'] ?? null);
    $ratingCount = (int) ($recipe['rating_count'] ?? 0);
    $linkHref = $detailHref ?? (isset($recipe['id']) ? route('app.recipe.show', $recipe['id']) : '#');
    $loggedIn = ! empty($currentUserId);
@endphp

<div
    x-data="{
        openBoardSelector: false
    }"
    class="rounded-3xl overflow-hidden shadow-xl bg-white mb-5 transition-all duration-300 hover:shadow-2xl active:scale-[0.99]"
>
    <div class="relative bg-gray-200 overflow-hidden" style="height: 260px">
        <a href="{{ $linkHref }}" class="block w-full h-full" aria-label="Buka resep {{ $title }}">
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $title }}"
                    class="w-full h-full object-cover"
                >
            @else
                <div class="w-full h-full flex items-center justify-center" style="background: var(--gradient-accent);">
                    <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
        </a>

        @if ($loggedIn)
            <button
                type="button"
                @click.stop.prevent="openBoardSelector = true"
                class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all z-10"
                aria-label="Simpan ke koleksi"
                title="Simpan ke koleksi"
            >
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </button>
        @else
            <a
                href="{{ route('app.login') }}"
                @click.stop
                class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all z-10"
                aria-label="Login untuk simpan"
                title="Login untuk simpan"
            >
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </a>
        @endif

        @if ($categoryName)
            <span class="absolute top-4 right-4 px-3 py-1 text-white text-xs font-bold rounded-full shadow pointer-events-none"
                  style="background: var(--gradient-accent);">
                {{ $categoryName }}
            </span>
        @endif

        <div class="absolute bottom-4 left-4 pointer-events-none max-w-[min(100%,14rem)]">
            @if ($ratingValue !== null && (float) $ratingValue > 0)
                <div class="bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1 shadow">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span>{{ number_format((float) $ratingValue, 1) }}</span>
                    @if($ratingCount > 0)
                        <span class="font-semibold opacity-80 text-xs">({{ $ratingCount }})</span>
                    @endif
                </div>
            @else
                <div class="bg-black/55 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-xs font-semibold shadow">
                    Belum ada rating
                </div>
            @endif
        </div>

        @if (!empty($recipe['difficulty']))
            @php
                $diffColors = ['easy' => '#22c55e', 'medium' => '#eab308', 'hard' => '#ef4444', 'mudah' => '#22c55e', 'sedang' => '#eab308', 'sulit' => '#ef4444'];
                $diffLabels = ['easy' => 'Mudah', 'medium' => 'Sedang', 'hard' => 'Sulit', 'mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'];
                $diff = strtolower($recipe['difficulty']);
            @endphp
            <span class="absolute bottom-4 right-4 px-2.5 py-1 text-white text-xs font-bold shadow rounded-full pointer-events-none"
                  style="background: {{ $diffColors[$diff] ?? '#6b7280' }};">
                {{ $diffLabels[$diff] ?? ucfirst($recipe['difficulty']) }}
            </span>
        @endif
    </div>

    <a href="{{ $linkHref }}" class="block p-5 cursor-pointer no-underline text-inherit" aria-label="Buka resep {{ $title }}">
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-xl font-bold leading-tight line-clamp-2 flex-1" style="color: var(--color-text-primary);">
                {{ $title }}
            </h3>
            @if ($ratingValue !== null && (float) $ratingValue > 0)
                <span class="shrink-0 text-sm font-bold flex items-center gap-0.5 px-2 py-1 rounded-lg bg-amber-50" style="color: var(--color-primary-coral);">
                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    {{ number_format((float) $ratingValue, 1) }}
                </span>
            @endif
        </div>

        @if (!empty($recipe['description']))
            <p class="text-sm mb-4 line-clamp-2 leading-relaxed" style="color: var(--color-text-secondary);">{{ $recipe['description'] }}</p>
        @endif

        <div class="flex flex-wrap gap-2 mb-4">
            @if ($prepTime)
                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $prepTime }} mnt
                </span>
            @endif
            @if (!empty($recipe['servings']))
                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $recipe['servings'] }} porsi
                </span>
            @endif
            @if (!empty($recipe['calories']))
                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                    <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                    {{ $recipe['calories'] }} kal
                </span>
            @endif
        </div>

        @if (count($tagList) > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($tagList as $tag)
                    <span class="text-xs font-semibold px-3 py-1 rounded-full"
                          style="border: 1px solid rgba(231,111,81,0.20); background: rgba(231,111,81,0.08); color: var(--color-primary-coral);">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($authorName)
            <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                @if ($authorAvatar)
                    <img
                        src="{{ $authorAvatar }}"
                        alt=""
                        class="w-9 h-9 rounded-full object-cover border-2 shrink-0"
                        style="border-color: rgba(231,111,81,0.30);"
                    >
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
