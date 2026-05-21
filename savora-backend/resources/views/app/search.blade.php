@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Search Recipes' : 'Cari Resep' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Search result card hover */
        .search-result-card:hover {
            border-color: rgba(231,111,81,0.35) !important;
            transform: translateY(-1px);
        }

        /* Filter button theme-aware */
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            background: var(--color-card-bg);
            border: 1.5px solid var(--color-card-border);
            color: var(--color-text-primary);
            cursor: pointer;
            transition: border-color .2s, color .2s;
        }
        .filter-btn:hover {
            border-color: rgba(231,111,81,0.50);
            color: var(--color-primary-coral);
        }

        /* Sort select theme-aware */
        .sort-select {
            padding: 8px 36px 8px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            background: var(--color-card-bg);
            border: 1.5px solid var(--color-card-border);
            color: var(--color-text-primary);
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%23E76F51' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            outline: none;
            transition: border-color .2s;
        }
        .sort-select:focus { border-color: var(--color-primary-coral); }

        /* Filter label */
        .filter-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: var(--color-text-secondary);
        }

        /* Thumbnail placeholder */
        .thumb-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            background: var(--gradient-accent);
        }

        /* Empty state for search page */
        .search-empty {
            padding: 48px 24px;
            background: var(--gradient-card);
            border: 1.5px solid var(--color-card-border);
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
        }
        .search-empty-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: var(--color-chip-bg);
        }
    </style>
</head>
<body style="background: var(--color-bg-light); color: var(--color-text-primary);">

    <x-unified-navigation
        :avatar-url="session('user_avatar') ?? null"
        :unread-count="0"
        :username="session('user_username') ?? null"
    />

    <div class="max-w-3xl mx-auto px-4 pt-4 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="mb-6 px-1">
            <x-app-theme.section-header
                :title="$isEnglish ? 'Search Recipes' : 'Cari Resep'"
                icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>'
            />
            <p class="text-sm mt-2 pl-[56px]" style="color: var(--color-text-secondary);">
                {{ $isEnglish ? 'Find the recipe you want' : 'Temukan resep yang kamu inginkan' }}
            </p>
        </div>

        {{-- Search form --}}
        <form method="GET" action="{{ route('app.search') }}" x-data="{ showFilters: false }" class="card-savora p-4 sm:p-5 mb-6">

            {{-- Search input --}}
            <div class="relative mb-3">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="q" value="{{ $query }}"
                       placeholder="{{ $isEnglish ? 'Search recipes, ingredients, or chefs...' : 'Cari resep, bahan, atau chef...' }}"
                       class="input-savora pl-12 pr-4 py-4">
                @if($query)
                    <a href="{{ route('app.search') }}" class="absolute inset-y-0 right-4 flex items-center transition-colors"
                       style="color: var(--color-text-muted);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>

            {{-- Filter toggle + Sort --}}
            <div class="flex items-center justify-between gap-2 mb-3">
                <button type="button" @click="showFilters = !showFilters" class="filter-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                    @php
                        $activeFilters = collect([$categoryId, $tagId, $difficulty, $minCalories, $maxCalories])->filter()->count();
                    @endphp
                    @if($activeFilters > 0)
                        <span class="badge-savora">{{ $activeFilters }}</span>
                    @endif
                </button>

                <select name="sort" onchange="this.form.submit()" class="sort-select">
                    <option value="popular" {{ $sortBy === 'popular' ? 'selected' : '' }}>{{ $isEnglish ? 'Most Popular' : 'Terpopuler' }}</option>
                    <option value="newest"  {{ $sortBy === 'newest'  ? 'selected' : '' }}>{{ $isEnglish ? 'Newest' : 'Terbaru' }}</option>
                </select>
            </div>

            {{-- Filters panel --}}
            <div x-show="showFilters" x-transition class="card-savora p-5 mb-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Category --}}
                    <div>
                        <label class="filter-label">{{ $isEnglish ? 'Category' : 'Kategori' }}</label>
                        <select name="category_id" class="input-savora">
                            <option value="">{{ $isEnglish ? 'All Categories' : 'Semua Kategori' }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat['id'] }}" {{ $categoryId == $cat['id'] ? 'selected' : '' }}>
                                    {{ $cat['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Difficulty --}}
                    <div>
                        <label class="filter-label">{{ $isEnglish ? 'Difficulty' : 'Tingkat Kesulitan' }}</label>
                        <select name="difficulty" class="input-savora">
                            <option value="">{{ $isEnglish ? 'All Levels' : 'Semua Tingkat' }}</option>
                            <option value="easy"   {{ $difficulty === 'easy'   ? 'selected' : '' }}>{{ $isEnglish ? 'Easy' : 'Mudah' }}</option>
                            <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>{{ $isEnglish ? 'Medium' : 'Sedang' }}</option>
                            <option value="hard"   {{ $difficulty === 'hard'   ? 'selected' : '' }}>{{ $isEnglish ? 'Hard' : 'Sulit' }}</option>
                        </select>
                    </div>

                    {{-- Calories --}}
                    <div>
                        <label class="filter-label">{{ $isEnglish ? 'Min Calories' : 'Min Kalori' }}</label>
                        <input type="number" name="min_calories" value="{{ $minCalories }}" placeholder="0" class="input-savora">
                    </div>
                    <div>
                        <label class="filter-label">{{ $isEnglish ? 'Max Calories' : 'Max Kalori' }}</label>
                        <input type="number" name="max_calories" value="{{ $maxCalories }}" placeholder="9999" class="input-savora">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary-savora flex-1 py-2.5 text-sm">
                        {{ $isEnglish ? 'Apply Filter' : 'Terapkan Filter' }}
                    </button>
                    <a href="{{ route('app.search', ['q' => $query]) }}"
                       class="inline-flex items-center justify-center px-5 py-2.5 rounded-2xl text-sm font-semibold transition-colors"
                       style="background: var(--color-chip-bg); color: var(--color-text-secondary); border: 1.5px solid var(--color-card-border);">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- Popular tags --}}
        @if(count($popularTags) > 0 && !$query)
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <x-app-theme.section-header
                        :title="$isEnglish ? 'Popular Tags' : 'Tag Populer'"
                        icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h14M5 15h14M10 3L8 21M16 3l-2 18"/></svg>'
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($popularTags as $tag)
                        <a href="{{ route('app.search', ['tag_id' => $tag['id']]) }}"
                           class="tag-chip {{ $tagId == $tag['id'] ? 'selected' : '' }}">
                            #{{ $tag['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Results --}}
        @if($query || $categoryId || $tagId || $difficulty || $minCalories || $maxCalories)
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold" style="color: var(--color-text-secondary);">
                    @if(count($results) > 0)
                        <span style="color: var(--color-primary-coral); font-weight: 700;">{{ count($results) }}</span>
                        {{ $isEnglish ? 'recipes found' : 'resep ditemukan' }}
                        @if($query) {{ $isEnglish ? 'for' : 'untuk' }} "<span style="color: var(--color-text-primary); font-weight: 700;">{{ $query }}</span>" @endif
                    @else
                        {{ $isEnglish ? 'No results' : 'Tidak ada hasil' }}
                        @if($query) {{ $isEnglish ? 'for' : 'untuk' }} "<span style="color: var(--color-text-primary); font-weight: 700;">{{ $query }}</span>" @endif
                    @endif
                </p>
            </div>

            @forelse($results as $recipe)
                @php
                    $author   = $recipe['profiles'] ?? [];
                    $category = $recipe['categories']['name'] ?? null;
                    $tags     = collect($recipe['recipe_tags'] ?? [])->pluck('tags.name')->filter()->take(3)->toArray();
                    $rating   = $recipe['rating_avg'] ?? null;
                @endphp
                <a href="{{ route('web.recipe.show', $recipe['id']) }}"
                   class="search-result-card flex gap-4 p-4 mb-3 rounded-2xl transition-all no-underline active:scale-[0.98]"
                   style="
                       background: var(--color-card-bg);
                       border: 1.5px solid var(--color-card-border);
                       box-shadow: var(--shadow-card);
                       color: inherit;
                   ">
                    {{-- Thumbnail --}}
                    <div class="w-24 h-24 rounded-xl overflow-hidden shrink-0"
                         style="background: var(--color-chip-bg);">
                        @if(!empty($recipe['image_url']))
                            <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="thumb-placeholder">
                                <svg class="w-8 h-8" style="color: rgba(255,255,255,0.60);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold line-clamp-2 text-sm leading-snug mb-1.5"
                            style="color: var(--color-text-primary);">{{ $recipe['title'] }}</h3>

                        @if($category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white mb-1.5"
                                  style="background: var(--gradient-accent);">{{ $category }}</span>
                        @endif

                        <div class="flex items-center gap-3 text-xs mb-1.5" style="color: var(--color-text-secondary);">
                            @if(!empty($recipe['cook_time']))
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $recipe['cook_time'] }} {{ $isEnglish ? 'min' : 'mnt' }}
                                </span>
                            @endif
                            @if($rating && $rating > 0)
                                <span class="flex items-center gap-1 font-semibold" style="color: #D97706;">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ number_format($rating, 1) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if(!empty($author['avatar_url']))
                                <img src="{{ $author['avatar_url'] }}" class="w-5 h-5 rounded-full object-cover shrink-0">
                            @else
                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-white shrink-0"
                                     style="background: var(--gradient-accent); font-size: 9px; font-weight: 700;">
                                    {{ strtoupper(substr($author['username'] ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-xs font-medium truncate" style="color: var(--color-text-secondary);">
                                {{ $author['username'] ?? 'Unknown' }}
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="search-empty">
                    <div class="search-empty-icon">
                        <svg class="w-9 h-9" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Recipe not found' : 'Resep tidak ditemukan' }}</h3>
                    <p class="text-sm" style="color: var(--color-text-muted);">{{ $isEnglish ? 'Try another keyword or filter' : 'Coba kata kunci atau filter yang berbeda' }}</p>
                </div>
            @endforelse

        @else
            <x-app-theme.empty-state
                icon="bi bi-search"
                :title="$isEnglish ? 'Search Your Favorite Recipes' : 'Cari Resep Favoritmu'"
                :subtitle="$isEnglish ? 'Type a recipe name, ingredient, or use the filters above' : 'Ketik nama resep, bahan, atau gunakan filter di atas'"
            />
        @endif

    </div>
</body>
</html>
