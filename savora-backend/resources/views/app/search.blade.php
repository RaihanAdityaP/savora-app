<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Resep — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="session('user_avatar') ?? null"
        :unread-count="0"
        :username="session('user_username') ?? null"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6">
            <div class="p-3 bg-[#E76F51]/10 rounded-2xl border border-[#E76F51]/25">
                <svg class="w-7 h-7 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cari Resep</h1>
                <p class="text-gray-500 text-sm">Temukan resep yang kamu inginkan</p>
            </div>
        </div>

        {{-- Search form --}}
        <form method="GET" action="{{ route('app.search') }}" x-data="{ showFilters: false }">
            {{-- Search input --}}
            <div class="relative mb-4">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="q" value="{{ $query }}"
                       placeholder="Cari resep, bahan, atau chef..."
                       class="w-full pl-12 pr-4 py-4 bg-white rounded-2xl border border-gray-200 shadow-sm focus:outline-none focus:border-[#E76F51] focus:ring-2 focus:ring-[#E76F51]/20 text-gray-900 placeholder-gray-400 font-medium transition-all">
                @if($query)
                    <a href="{{ route('app.search') }}" class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>

            {{-- Filter toggle --}}
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="showFilters = !showFilters"
                        class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm text-sm font-semibold text-gray-700 hover:border-[#E76F51] hover:text-[#E76F51] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                    @php
                        $activeFilters = collect([$categoryId, $tagId, $difficulty, $minCalories, $maxCalories])->filter()->count();
                    @endphp
                    @if($activeFilters > 0)
                        <span class="px-1.5 py-0.5 gradient-accent text-white text-xs font-bold rounded-full">{{ $activeFilters }}</span>
                    @endif
                </button>

                {{-- Sort --}}
                <select name="sort" onchange="this.form.submit()"
                        class="px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#E76F51] cursor-pointer">
                    <option value="popular" {{ $sortBy === 'popular' ? 'selected' : '' }}>Terpopuler</option>
                    <option value="newest"  {{ $sortBy === 'newest'  ? 'selected' : '' }}>Terbaru</option>
                </select>
            </div>

            {{-- Filters panel --}}
            <div x-show="showFilters" x-transition class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Category --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Kategori</label>
                        <select name="category_id" class="w-full px-3 py-2.5 bg-gray-50 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#E76F51]">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat['id'] }}" {{ $categoryId == $cat['id'] ? 'selected' : '' }}>
                                    {{ $cat['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Difficulty --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Tingkat Kesulitan</label>
                        <select name="difficulty" class="w-full px-3 py-2.5 bg-gray-50 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#E76F51]">
                            <option value="">Semua Tingkat</option>
                            <option value="easy"   {{ $difficulty === 'easy'   ? 'selected' : '' }}>Mudah</option>
                            <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>Sedang</option>
                            <option value="hard"   {{ $difficulty === 'hard'   ? 'selected' : '' }}>Sulit</option>
                        </select>
                    </div>

                    {{-- Calories --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Min Kalori</label>
                        <input type="number" name="min_calories" value="{{ $minCalories }}" placeholder="0"
                               class="w-full px-3 py-2.5 bg-gray-50 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#E76F51]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Max Kalori</label>
                        <input type="number" name="max_calories" value="{{ $maxCalories }}" placeholder="9999"
                               class="w-full px-3 py-2.5 bg-gray-50 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#E76F51]">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 py-2.5 gradient-accent text-white font-bold rounded-xl text-sm hover:shadow-lg transition-all">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('app.search', ['q' => $query]) }}"
                       class="px-4 py-2.5 bg-gray-100 text-gray-600 font-semibold rounded-xl text-sm hover:bg-gray-200 transition-all">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- Popular tags --}}
        @if(count($popularTags) > 0 && !$query)
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Tag Populer</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($popularTags as $tag)
                        <a href="{{ route('app.search', ['tag_id' => $tag['id']]) }}"
                           class="px-3 py-1.5 rounded-full text-sm font-semibold transition-all
                                  {{ $tagId == $tag['id'] ? 'gradient-accent text-white shadow' : 'bg-white border border-gray-200 text-gray-700 hover:border-[#E76F51] hover:text-[#E76F51]' }}">
                            #{{ $tag['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Results --}}
        @if($query || $categoryId || $tagId || $difficulty || $minCalories || $maxCalories)
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-600">
                    @if(count($results) > 0)
                        <span class="text-[#E76F51] font-bold">{{ count($results) }}</span> resep ditemukan
                        @if($query) untuk "<span class="font-bold">{{ $query }}</span>" @endif
                    @else
                        Tidak ada hasil
                        @if($query) untuk "<span class="font-bold">{{ $query }}</span>" @endif
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
                <a href="{{ route('app.recipe.show', $recipe['id']) }}"
                   class="flex gap-4 bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-all mb-3 active:scale-[0.98]">
                    {{-- Thumbnail --}}
                    <div class="w-24 h-24 rounded-xl overflow-hidden shrink-0 bg-gray-200">
                        @if(!empty($recipe['image_url']))
                            <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full gradient-accent flex items-center justify-center">
                                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 line-clamp-2 text-sm leading-snug mb-1">{{ $recipe['title'] }}</h3>

                        @if($category)
                            <span class="inline-block px-2 py-0.5 gradient-accent text-white text-xs font-bold rounded-full mb-1.5">{{ $category }}</span>
                        @endif

                        <div class="flex items-center gap-3 text-xs text-gray-500 mb-1.5">
                            @if(!empty($recipe['cook_time']))
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $recipe['cook_time'] }} mnt
                                </span>
                            @endif
                            @if($rating && $rating > 0)
                                <span class="flex items-center gap-1 text-yellow-600 font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ number_format($rating, 1) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if(!empty($author['avatar_url']))
                                <img src="{{ $author['avatar_url'] }}" class="w-5 h-5 rounded-full object-cover">
                            @else
                                <div class="w-5 h-5 rounded-full gradient-accent flex items-center justify-center text-white text-[9px] font-bold">
                                    {{ strtoupper(substr($author['username'] ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-xs text-gray-500 font-medium truncate">{{ $author['username'] ?? 'Unknown' }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">Resep tidak ditemukan</h3>
                    <p class="text-gray-400 text-sm">Coba kata kunci atau filter yang berbeda</p>
                </div>
            @endforelse
        @else
            {{-- Empty state / suggestions --}}
            <div class="bg-white rounded-3xl p-10 text-center shadow-sm">
                <div class="w-20 h-20 gradient-accent rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Cari Resep Favoritmu</h3>
                <p class="text-gray-500 text-sm">Ketik nama resep, bahan, atau gunakan filter di atas</p>
            </div>
        @endif

    </div>
</body>
</html>
