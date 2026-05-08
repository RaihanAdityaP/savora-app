<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $recipe['title'] ?? 'Detail Resep' }} — Savora</title>
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
        $diffLabels = ['mudah'=>'Mudah','sedang'=>'Sedang','sulit'=>'Sulit','easy'=>'Mudah','medium'=>'Sedang','hard'=>'Sulit'];
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
               class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            @if($category)
                <span class="absolute top-4 right-4 px-3 py-1 text-white text-xs font-bold rounded-full shadow"
                      style="background: var(--gradient-accent);">{{ $category }}</span>
            @endif
            @if($avgRating > 0)
                <div class="absolute bottom-4 left-4 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-1 shadow">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    {{ number_format($avgRating, 1) }} ({{ $totalRatings }})
                </div>
            @endif
        </div>

        {{-- Title & Actions --}}
        <div class="card-savora p-5 mb-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <h1 class="text-2xl font-bold leading-tight flex-1" style="color: var(--color-text-primary);">{{ $recipe['title'] }}</h1>
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
                              onsubmit="return confirm('Hapus resep ini?')">
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

            {{-- Meta chips --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @if(!empty($recipe['cooking_time']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $recipe['cooking_time'] }} menit
                    </span>
                @endif
                @if(!empty($recipe['servings']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $recipe['servings'] }} porsi
                    </span>
                @endif
                @if(!empty($recipe['calories']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-sm font-medium rounded-full" style="color: var(--color-text-secondary);">
                        <svg class="w-4 h-4" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        {{ $recipe['calories'] }} kal
                    </span>
                @endif
                @if($diff)
                    <span class="px-3 py-1.5 text-sm font-medium rounded-full {{ $diffColors[$diff] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $diffLabels[$diff] ?? ucfirst($diff) }}
                    </span>
                @endif
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

        <div class="card-savora p-5 mb-4 border border-orange-200 bg-orange-50 text-orange-900">
            <div class="text-sm leading-relaxed">
                <strong>Catatan:</strong> Untuk translate resep ke Inggris: bisa banget. Tapi itu beda dari language UI. UI language cuma menerjemahkan label seperti “Search”, “Settings”, “Ingredients”. Isi resep seperti judul, deskripsi, bahan, langkah itu data user, jadi perlu fitur translate konten. Cara paling rapi: tambah tombol “Translate to English” di detail resep, kirim title/description/ingredients/steps ke service AI/translation, lalu cache hasilnya supaya tidak translate ulang terus.
            </div>
        </div>

        {{-- Description --}}
        @if(!empty($recipe['description']))
            <div class="card-savora p-5 mb-4">
                <div class="mb-3">
                    <x-app-theme.section-header title="Deskripsi" :icon="$svgDesc" />
                </div>
                <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">{{ $recipe['description'] }}</p>
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
                    <x-app-theme.section-header title="Bahan-bahan" :icon="$svgIngr" />
                    <span class="badge-savora">{{ count($ingredients) }}</span>
                </div>
                <ul class="space-y-2">
                    @foreach($ingredients as $i => $ingredient)
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 mt-0.5"
                                 style="background: var(--gradient-accent);">{{ $i + 1 }}</div>
                            <span class="text-sm leading-relaxed" style="color: var(--color-text-primary);">{{ is_array($ingredient) ? ($ingredient['name'] ?? json_encode($ingredient)) : $ingredient }}</span>
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
                    <x-app-theme.section-header title="Langkah-langkah" :icon="$svgSteps" />
                    <span class="badge-savora">{{ count($steps) }}</span>
                </div>
                <ol class="space-y-4">
                    @foreach($steps as $i => $step)
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-2xl flex items-center justify-center text-white text-sm font-bold shrink-0"
                                 style="background: var(--gradient-accent);">{{ $i + 1 }}</div>
                            <div class="flex-1 pt-1">
                                <p class="text-sm leading-relaxed" style="color: var(--color-text-primary);">{{ is_array($step) ? ($step['description'] ?? $step['step'] ?? json_encode($step)) : $step }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Save to collection --}}
        <div class="card-savora p-5 mb-4">
            <div class="mb-3">
                <x-app-theme.section-header title="Simpan Resep" :icon="$svgBookmark" />
            </div>
            <form action="{{ route('app.favorites.save') }}" method="POST">
                @csrf
                <input type="hidden" name="recipe_id" value="{{ $recipe['id'] }}">
                <button type="submit" class="btn-primary-savora w-full py-3 rounded-2xl">
                    <svg class="w-5 h-5" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    {{ $isFavorite ? 'Tersimpan di Koleksi' : 'Simpan ke Koleksi' }}
                </button>
            </form>
        </div>

        {{-- Rating --}}
        <div class="card-savora p-5 mb-4">
            <div class="mb-4 flex items-center justify-between">
                <x-app-theme.section-header title="Rating" :icon="$svgStar" />
                @if($avgRating > 0)
                    <span class="flex items-center gap-1 font-bold" style="color: var(--color-primary-yellow);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ number_format($avgRating, 1) }} / 5
                        <span class="font-normal text-xs" style="color: var(--color-text-secondary);">({{ $totalRatings }})</span>
                    </span>
                @endif
            </div>
            <form action="{{ route('app.recipe.rate', $recipe['id']) }}" method="POST">
                @csrf
                <div class="flex items-center gap-2 mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="submit" name="rating" value="{{ $i }}"
                                class="text-3xl transition-transform hover:scale-110 {{ $i <= ($userRating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}">★</button>
                    @endfor
                    @if($userRating)
                        <span class="text-sm ml-2" style="color: var(--color-text-secondary);">Rating kamu: {{ $userRating }}/5</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Comments --}}
        <div class="card-savora p-5 mb-4">
            <div class="mb-4 flex items-center justify-between">
                <x-app-theme.section-header title="Komentar" :icon="$svgChat" />
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
                        <textarea name="content" rows="2" placeholder="Tulis komentar..."
                                  class="input-savora resize-none mb-2"></textarea>
                        <button type="submit" class="btn-primary-savora px-5 py-2 text-sm">Kirim</button>
                    </div>
                </div>
            </form>

            @forelse($comments as $comment)
                @php $commenter = $comment['profiles'] ?? []; @endphp
                <div class="flex gap-3 mb-4 last:mb-0">
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
                            <p class="text-sm leading-relaxed" style="color: var(--color-text-primary);">{{ $comment['content'] }}</p>
                        </div>
                        <div class="flex items-center gap-3 mt-1 px-2">
                            <span class="text-xs" style="color: var(--color-text-secondary);">
                                @php try { echo \Carbon\Carbon::parse($comment['created_at'])->diffForHumans(); } catch(\Exception $e) { echo $comment['created_at'] ?? ''; } @endphp
                            </span>
                            @if($userId && ($comment['user_id'] === $userId || $currentUserRole === 'admin'))
                                <form action="{{ route('app.comment.delete', $comment['id']) }}" method="POST"
                                      onsubmit="return confirm('Hapus komentar?')">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-app-theme.empty-state
                    icon="bi bi-chat-square"
                    title="Belum ada komentar"
                    subtitle="Jadilah yang pertama berkomentar!" />
            @endforelse
        </div>

    </div>
</body>
</html>