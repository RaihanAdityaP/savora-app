@php
    $pageLanguage = session('user_language', 'en');
    $isEnglish = $pageLanguage === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $pageLanguage }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $recipe['title'] ?? ($isEnglish ? 'Recipe Detail' : 'Detail Resep') }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2 { font-family: 'Poppins', sans-serif; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    @php
        $author     = $recipe['profiles'] ?? [];
        $category   = $recipe['categories']['name'] ?? null;
        $isOwner    = $userId && $recipe['user_id'] === $userId;
        $isAdmin    = $currentUserRole === 'admin';
        $canEdit    = $isOwner || $isAdmin;
        $showTranslate = $pageLanguage === 'en';
        $translateLabel = 'Translate';
        $undoTranslateLabel = 'Undo';
        $diffLabels = $isEnglish
            ? ['mudah'=>'Easy','sedang'=>'Medium','sulit'=>'Hard','easy'=>'Easy','medium'=>'Medium','hard'=>'Hard']
            : ['mudah'=>'Mudah','sedang'=>'Sedang','sulit'=>'Sulit','easy'=>'Mudah','medium'=>'Sedang','hard'=>'Sulit'];
        $diffColors = ['mudah'=>'bg-green-100 text-green-700','sedang'=>'bg-yellow-100 text-yellow-700','sulit'=>'bg-red-100 text-red-700','easy'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','hard'=>'bg-red-100 text-red-700'];
        $diff = strtolower($recipe['difficulty'] ?? '');

        $svgDesc     = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7"/></svg>';
        $svgTags     = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>';
        $svgIngr     = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
        $svgSteps    = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
        $svgBookmark = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>';
        $svgStar     = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        $svgChat     = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>';
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        @if(session('status'))
            <div class="mb-4">
                <x-app-theme.info-banner message="{{ session('status') }}" icon="bi bi-check-circle" />
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4">
                <x-app-theme.info-banner message="{{ session('error') }}" icon="bi bi-exclamation-circle" />
            </div>
        @endif

        {{-- Hero Image --}}
        <div class="relative rounded-3xl overflow-hidden mb-5 shadow-xl bg-gray-200" style="height: 260px">
            @if(!empty($recipe['image_url']))
                <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center" style="background: var(--gradient-accent);">
                    <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
            <a href="javascript:history.back()"
               class="btn-icon-savora absolute top-4 left-4 w-10 h-10 backdrop-blur rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            @if($category)
                <span class="absolute bottom-4 right-4 px-3 py-1 text-white text-xs font-bold rounded-full shadow"
                      style="background: var(--gradient-accent);">{{ $category }}</span>
            @endif
        </div>

        {{-- Title & Actions --}}
        <div class="card-savora p-5 mb-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold leading-tight" style="color: var(--color-text-primary);">
                        <span class="savora-recipe-copy">{{ $recipe['title'] }}</span>
                    </h1>
                    @if($showTranslate)
                        <button type="button"
                                class="btn-translate-savora mt-2"
                                data-savora-translate=".savora-recipe-copy"
                                data-savora-label="{{ $translateLabel }}"
                                data-savora-undo-label="{{ $undoTranslateLabel }}">{{ $translateLabel }}</button>
                    @endif
                </div>
                @if($canEdit)
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('app.recipe.edit', $recipe['id']) }}"
                           class="p-2 rounded-xl transition-all"
                           style="background: rgba(231,111,81,0.10); color: var(--color-primary-coral);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form action="{{ route('app.recipe.destroy', $recipe['id']) }}" method="POST"
                              onsubmit="return confirm('{{ $isEnglish ? 'Delete this recipe?' : 'Hapus resep ini?' }}')">
                            @csrf
                            <button type="submit" class="p-2 bg-red-50 text-red-500 rounded-xl hover:bg-red-100 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="mb-4 rounded-3xl p-4"
                 style="background: linear-gradient(135deg, rgba(231,111,81,0.10), rgba(244,162,97,0.08)); border: 1.5px solid var(--color-card-border);">
                @if($userId)
                    <form action="{{ route('app.recipe.like', $recipe['id']) }}" method="POST">
                        @csrf
                        <button type="submit" class="group w-full flex items-center gap-4 text-left transition-all">
                            <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-lg"
                                  style="background: {{ $isLiked ? 'var(--gradient-accent)' : 'var(--color-card-bg)' }}; color: {{ $isLiked ? '#ffffff' : 'var(--color-primary-coral)' }}; border: 1px solid var(--color-separator);">
                                <svg class="w-7 h-7 transition-transform group-hover:scale-110" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                                </svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-base font-extrabold" style="color: var(--color-text-primary);">{{ $isLiked ? ($isEnglish ? 'Liked' : 'Disukai') : ($isEnglish ? 'Like this recipe' : 'Suka resep ini') }}</span>
                                <span class="block text-sm" style="color: var(--color-text-secondary);">{{ $likesCount }} {{ $isEnglish ? 'people like this recipe' : 'orang menyukai resep ini' }}</span>
                            </span>
                            <span class="px-4 py-2 rounded-full text-sm font-bold"
                                  style="background: {{ $isLiked ? 'var(--gradient-accent)' : 'var(--color-chip-bg)' }}; color: {{ $isLiked ? '#ffffff' : 'var(--color-primary-coral)' }};">
                                {{ $likesCount }}
                            </span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('app.login') }}" class="group w-full flex items-center gap-4 text-left">
                        <span class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                              style="background: var(--color-card-bg); color: var(--color-primary-coral); border: 1px solid var(--color-separator);">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/></svg>
                        </span>
                        <span class="flex-1">
                            <span class="block text-base font-extrabold" style="color: var(--color-text-primary);">{{ $isEnglish ? 'Like this recipe' : 'Suka resep ini' }}</span>
                            <span class="block text-sm" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Log in to like' : 'Login untuk memberi like' }}</span>
                        </span>
                        <span class="px-4 py-2 rounded-full text-sm font-bold" style="background: var(--color-chip-bg); color: var(--color-primary-coral);">{{ $likesCount }}</span>
                    </a>
                @endif
            </div>

            {{-- Meta chips --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @if($avgRating > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <span class="flex items-center gap-0.5" aria-label="{{ number_format($avgRating, 1) }} dari 5">
                            @for($i = 1; $i <= 5; $i++)
                                @php
                                    $ratingValue = (float) $avgRating;
                                    $isFull = $ratingValue >= $i;
                                    $isHalf = ! $isFull && $ratingValue >= ($i - 0.5);
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
                        <span class="font-bold">{{ number_format($avgRating, 1) }}</span>
                        @if($totalRatings > 0)
                            <span class="text-xs" style="color: var(--color-text-muted);">({{ $totalRatings }})</span>
                        @endif
                    </span>
                @endif
                @if(!empty($recipe['cooking_time']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $recipe['cooking_time'] }} {{ $isEnglish ? 'minutes' : 'menit' }}
                    </span>
                @endif
                @if(!empty($recipe['servings']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $recipe['servings'] }} {{ $isEnglish ? 'servings' : 'porsi' }}
                    </span>
                @endif
                @if(!empty($recipe['calories']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        {{ $recipe['calories'] }} {{ $isEnglish ? 'cal' : 'kal' }}
                    </span>
                @endif
                @if($diff)
                    <span class="px-3 py-1.5 text-sm font-medium rounded-full {{ $diffColors[$diff] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $diffLabels[$diff] ?? ucfirst($diff) }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <a href="#simpan-resep"
                   class="btn-primary-savora justify-center py-3 rounded-2xl text-sm">
                    <svg class="w-5 h-5" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    {{ $isFavorite ? ($isEnglish ? 'Saved' : 'Tersimpan') : ($isEnglish ? 'Save' : 'Simpan') }}
                </a>
                <button type="button"
                        class="flex items-center justify-center gap-2 py-3 rounded-2xl text-sm font-bold text-white shadow-lg transition-transform hover:scale-[1.01] active:scale-[0.99]"
                        style="background: linear-gradient(135deg, #6366F1, #8B5CF6);"
                        data-share-title="{{ e($recipe['title'] ?? 'Savora Recipe') }}"
                        data-share-url="{{ url()->current() }}"
                        onclick="shareRecipeFromDetail(this)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316"/>
                    </svg>
                    {{ $isEnglish ? 'Share' : 'Bagikan' }}
                </button>
            </div>

            {{-- Author --}}
            <a href="{{ route('web.profile.user', $author['id'] ?? 0) }}"
               class="flex items-center gap-3 p-3 rounded-2xl hover:bg-gray-100 transition-colors"
               style="background: var(--color-bg-light);">
                @if(!empty($author['avatar_url']))
                    <img src="{{ $author['avatar_url'] }}" class="w-10 h-10 rounded-full object-cover" style="border: 2px solid rgba(231,111,81,0.3);">
                @else
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                         style="background: var(--gradient-accent);">
                        {{ strtoupper(substr($author['username'] ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm" style="color: var(--color-text-primary);">{{ $author['username'] ?? 'Unknown' }}</p>
                    @if(!empty($author['full_name']))
                        <p class="text-xs" style="color: var(--color-text-secondary);">{{ $author['full_name'] }}</p>
                    @endif
                </div>
                @if(($author['role'] ?? '') === 'admin')
                    <span class="role-badge admin">Admin</span>
                @endif
                <svg class="w-4 h-4" style="color: #D1D5DB;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        {{-- Description --}}
        @if(!empty($recipe['description']))
            <div class="card-savora p-5 mb-4">
                <div class="mb-3">
                    <x-app-theme.section-header :title="$isEnglish ? 'Description' : 'Deskripsi'" :icon="$svgDesc" />
                </div>
                <p class="text-sm leading-relaxed savora-recipe-copy" style="color: var(--color-text-secondary);">{{ $recipe['description'] }}</p>
            </div>
        @endif

        {{-- Tags --}}
        @if(count($tags) > 0)
            <div class="card-savora p-5 mb-4">
                <div class="mb-3">
                    <x-app-theme.section-header title="Tag" :icon="$svgTags" />
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <a href="{{ route('app.search', ['tag_id' => $tag['id']]) }}" class="tag-chip selected">
                            #{{ $tag['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ingredients --}}
        @php $ingredients = is_array($recipe['ingredients'] ?? null) ? $recipe['ingredients'] : []; @endphp
        @if(count($ingredients) > 0)
            <div class="card-savora p-5 mb-4">
                <div class="mb-4 flex items-center justify-between">
                    <x-app-theme.section-header :title="$isEnglish ? 'Ingredients' : 'Bahan-bahan'" :icon="$svgIngr" />
                    <span class="badge-savora">{{ count($ingredients) }}</span>
                </div>
                <ul class="space-y-2">
                    @foreach($ingredients as $i => $ingredient)
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 mt-0.5"
                                 style="background: var(--gradient-accent);">{{ $i + 1 }}</div>
                            <span class="text-sm leading-relaxed savora-recipe-copy" style="color: var(--color-text-primary);">{{ is_array($ingredient) ? ($ingredient['name'] ?? json_encode($ingredient)) : $ingredient }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Steps --}}
        @php $steps = is_array($recipe['steps'] ?? null) ? $recipe['steps'] : []; @endphp
        @if(count($steps) > 0)
            <div class="card-savora p-5 mb-4">
                <div class="mb-4 flex items-center justify-between">
                    <x-app-theme.section-header :title="$isEnglish ? 'Steps' : 'Langkah-langkah'" :icon="$svgSteps" />
                    <span class="badge-savora">{{ count($steps) }}</span>
                </div>
                <ol class="space-y-4">
                    @foreach($steps as $i => $step)
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-2xl flex items-center justify-center text-white text-sm font-bold shrink-0"
                                 style="background: var(--gradient-accent);">{{ $i + 1 }}</div>
                            <div class="flex-1 pt-1">
                                <p class="text-sm leading-relaxed savora-recipe-copy" style="color: var(--color-text-primary);">{{ is_array($step) ? ($step['description'] ?? $step['step'] ?? json_encode($step)) : $step }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Save to collection --}}
        <div id="simpan-resep" class="card-savora p-5 mb-4" x-data="{ openBoardSelector: false }">
            <div class="mb-3">
                <x-app-theme.section-header :title="$isEnglish ? 'Save Recipe' : 'Simpan Resep'" :icon="$svgBookmark" />
            </div>
            <button type="button"
                    class="btn-primary-savora w-full py-3 rounded-2xl disabled:opacity-60 disabled:cursor-not-allowed"
                    @click="openBoardSelector = true"
                    {{ $userId ? '' : 'disabled' }}>
                <svg class="w-5 h-5" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                {{ $isFavorite ? ($isEnglish ? 'Saved in Collection' : 'Tersimpan di Koleksi') : ($isEnglish ? 'Save to Collection' : 'Simpan ke Koleksi') }}
            </button>

            @if(!$userId)
                <p class="text-sm mt-3" style="color: var(--color-text-secondary);">
                    <a href="{{ route('app.login') }}" class="font-semibold underline">{{ $isEnglish ? 'Log in' : 'Login' }}</a> {{ $isEnglish ? 'to save recipes to a collection.' : 'untuk menyimpan resep ke koleksi.' }}
                </p>
            @elseif(empty($favoriteBoards))
                <p class="text-sm mt-3" style="color: var(--color-text-secondary);">
                    {{ $isEnglish ? 'No collections yet' : 'Belum ada koleksi' }} — {{ $isEnglish ? 'open the selector, then' : 'buka pemilih lalu' }} <a href="{{ route('app.favorites') }}" class="font-semibold underline">{{ $isEnglish ? 'create a new collection' : 'buat koleksi baru' }}</a>.
                </p>
            @endif

            <x-save-collection-sheet
                :recipe-id="$recipe['id']"
                :boards="$favoriteBoards"
                :saved-board-ids="$savedBoardIds ?? []"
            />
        </div>

        {{-- Rating & Comments --}}
        <div class="card-savora p-5 mb-4">
            <div class="mb-4 flex items-center justify-between">
                <x-app-theme.section-header :title="$isEnglish ? 'Rating & Comments' : 'Rating & Komentar'" :icon="$svgStar" />
                <span class="badge-savora" style="background: var(--gradient-category);">{{ count($comments) }}</span>
            </div>

            <form action="{{ route('app.recipe.comment', $recipe['id']) }}" method="POST" class="mb-5">
                @csrf
                <div class="flex gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                         style="background: var(--gradient-accent);">
                        {{ strtoupper(substr(session('user_username', 'U'), 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="rounded-2xl px-4 py-3 mb-3"
                             style="background: rgba(233,196,106,0.08); border: 1px solid rgba(233,196,106,0.25);">
                            <p class="text-sm font-bold mb-2" style="color: var(--color-text-primary);">
                                {{ $isEnglish ? 'Rate your review' : 'Beri rating untuk ulasan Anda' }}
                            </p>
                            <div class="flex items-center gap-2" data-review-rating-group>
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $i }}" class="sr-only" {{ (int) old('rating', $userRating ?? 0) === $i ? 'checked' : '' }} required>
                                        <span data-review-rating-star="{{ $i }}" class="text-3xl transition-transform hover:scale-110 {{ $i <= (int) old('rating', $userRating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}">&#9733;</span>
                                    </label>
                                @endfor
                                @if($userRating)
                                    <span class="text-xs ml-1" style="color: var(--color-text-secondary);">
                                        {{ $isEnglish ? 'Current' : 'Saat ini' }}: {{ $userRating }}/5
                                    </span>
                                @endif
                            </div>
                        </div>
                        <textarea name="content" rows="2" placeholder="{{ $isEnglish ? 'Write your review...' : 'Tulis ulasan Anda...' }}"
                                  class="input-savora resize-none mb-2"></textarea>
                        <button type="submit" class="btn-primary-savora px-5 py-2 text-sm">{{ $isEnglish ? 'Send review' : 'Kirim ulasan' }}</button>
                    </div>
                </div>
            </form>

            @forelse($comments as $comment)
                @php $commenter = $comment['profiles'] ?? []; @endphp
                <div class="savora-comment-item flex gap-3 mb-4 last:mb-0">
                    @if(!empty($commenter['avatar_url']))
                        <img src="{{ $commenter['avatar_url'] }}" class="w-9 h-9 rounded-full object-cover shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                             style="background: var(--gradient-accent);">
                            {{ strtoupper(substr($commenter['username'] ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <div class="px-4 py-3 rounded-2xl" style="background: var(--color-bg-light);">
                            <p class="font-bold text-sm mb-1" style="color: var(--color-text-primary);">{{ $commenter['username'] ?? 'Unknown' }}</p>
                            @if(!empty($comment['rating']))
                                <div class="flex items-center gap-0.5 mb-2" aria-label="{{ $comment['rating'] }}/5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-sm {{ $i <= (int) $comment['rating'] ? 'text-yellow-400' : 'text-gray-300' }}">&#9733;</span>
                                    @endfor
                                </div>
                            @endif
                            <p class="text-sm leading-relaxed savora-comment-copy" style="color: var(--color-text-primary);">{{ $comment['content'] }}</p>
                        </div>
                        <div class="flex items-center gap-3 mt-1 px-2">
                            <span class="text-xs" style="color: var(--color-text-secondary);">
                                @php try { echo \Carbon\Carbon::parse($comment['created_at'])->diffForHumans(); } catch(\Exception $e) { echo $comment['created_at'] ?? ''; } @endphp
                            </span>
                            @if($userId && ($comment['user_id'] === $userId || $currentUserRole === 'admin'))
                                <form action="{{ route('app.comment.delete', $comment['id']) }}" method="POST"
                                      onsubmit="return confirm('{{ $isEnglish ? 'Delete comment?' : 'Hapus komentar?' }}')">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium">{{ $isEnglish ? 'Delete' : 'Hapus' }}</button>
                                </form>
                            @endif
                            @if($showTranslate)
                                <button type="button"
                                        class="btn-translate-savora !px-2 !py-1"
                                        data-savora-translate=".savora-comment-copy"
                                        data-savora-translate-scope=".savora-comment-item"
                                        data-savora-label="{{ $translateLabel }}"
                                        data-savora-undo-label="{{ $undoTranslateLabel }}">{{ $translateLabel }}</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-app-theme.empty-state
                    icon="bi bi-chat-square"
                    :title="$isEnglish ? 'No comments yet' : 'Belum ada komentar'"
                    :subtitle="$isEnglish ? 'Be the first to comment!' : 'Jadilah yang pertama berkomentar!'" />
            @endforelse
        </div>

    </div>
    <script>
        document.querySelectorAll('[data-review-rating-group]').forEach(group => {
            const inputs = group.querySelectorAll('input[name="rating"]');
            const stars = group.querySelectorAll('[data-review-rating-star]');

            function paint(value) {
                stars.forEach(star => {
                    const active = Number(star.dataset.reviewRatingStar) <= value;
                    star.classList.toggle('text-yellow-400', active);
                    star.classList.toggle('text-gray-300', !active);
                });
            }

            inputs.forEach(input => {
                input.addEventListener('change', () => paint(Number(input.value)));
                if (input.checked) paint(Number(input.value));
            });
        });

        async function shareRecipeFromDetail(button) {
            const title = button.dataset.shareTitle || document.title;
            const url = button.dataset.shareUrl || window.location.href;

            if (navigator.share) {
                await navigator.share({ title, url });
                return;
            }

            await navigator.clipboard?.writeText(url);
            const original = button.innerHTML;
            button.textContent = '{{ $isEnglish ? 'Link copied' : 'Link disalin' }}';
            setTimeout(() => { button.innerHTML = original; }, 1600);
        }

        if (location.hash === '#simpan-resep') {
            document.getElementById('simpan-resep')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</body>
</html>
