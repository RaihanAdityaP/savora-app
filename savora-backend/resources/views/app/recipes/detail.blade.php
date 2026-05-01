<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $recipe['title'] ?? 'Detail Resep' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

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
        $diffLabels = ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit', 'easy' => 'Mudah', 'medium' => 'Sedang', 'hard' => 'Sulit'];
        $diffColors = ['mudah' => 'bg-green-100 text-green-700', 'sedang' => 'bg-yellow-100 text-yellow-700', 'sulit' => 'bg-red-100 text-red-700', 'easy' => 'bg-green-100 text-green-700', 'medium' => 'bg-yellow-100 text-yellow-700', 'hard' => 'bg-red-100 text-red-700'];
        $diff = strtolower($recipe['difficulty'] ?? '');
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- Hero Image --}}
        <div class="relative rounded-3xl overflow-hidden mb-5 shadow-xl bg-gray-200" style="height: 260px">
            @if(!empty($recipe['image_url']))
                <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full gradient-accent flex items-center justify-center">
                    <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            {{-- Back button --}}
            <a href="javascript:history.back()"
               class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            {{-- Category badge --}}
            @if($category)
                <span class="absolute top-4 right-4 px-3 py-1 gradient-accent text-white text-xs font-bold rounded-full shadow">
                    {{ $category }}
                </span>
            @endif

            {{-- Rating overlay --}}
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
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <h1 class="text-2xl font-bold text-gray-900 leading-tight flex-1">{{ $recipe['title'] }}</h1>
                @if($canEdit)
                    <div class="flex gap-2 flex-shrink-0">
                        <a href="{{ route('app.recipe.edit', $recipe['id']) }}"
                           class="p-2 bg-[#E76F51]/10 text-[#E76F51] rounded-xl hover:bg-[#E76F51]/20 transition-all">
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
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-medium rounded-full">
                        <svg class="w-4 h-4 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $recipe['cooking_time'] }} menit
                    </span>
                @endif
                @if(!empty($recipe['servings']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-medium rounded-full">
                        <svg class="w-4 h-4 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $recipe['servings'] }} porsi
                    </span>
                @endif
                @if(!empty($recipe['calories']))
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-medium rounded-full">
                        <svg class="w-4 h-4 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <a href="{{ route('app.profile.user', $author['id'] ?? 0) }}"
               class="flex items-center gap-3 p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                @if(!empty($author['avatar_url']))
                    <img src="{{ $author['avatar_url'] }}" class="w-10 h-10 rounded-full object-cover border-2 border-[#E76F51]/30">
                @else
                    <div class="w-10 h-10 rounded-full gradient-accent flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($author['username'] ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 text-sm">{{ $author['username'] ?? 'Unknown' }}</p>
                    @if(!empty($author['full_name']))
                        <p class="text-xs text-gray-500">{{ $author['full_name'] }}</p>
                    @endif
                </div>
                @if(($author['role'] ?? '') === 'admin')
                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">Admin</span>
                @endif
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        {{-- Description --}}
        @if(!empty($recipe['description']))
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    Deskripsi
                </h2>
                <p class="text-gray-600 text-sm leading-relaxed">{{ $recipe['description'] }}</p>
            </div>
        @endif

        {{-- Tags --}}
        @if(count($tags) > 0)
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    Tag
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <a href="{{ route('app.search', ['tag_id' => $tag['id']]) }}"
                           class="px-3 py-1.5 bg-[#E76F51]/10 text-[#E76F51] text-sm font-semibold rounded-full border border-[#E76F51]/20 hover:bg-[#E76F51]/20 transition-colors">
                            #{{ $tag['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ingredients --}}
        @php $ingredients = is_array($recipe['ingredients'] ?? null) ? $recipe['ingredients'] : []; @endphp
        @if(count($ingredients) > 0)
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    Bahan-bahan
                    <span class="ml-auto px-2.5 py-0.5 gradient-accent text-white text-xs font-bold rounded-full">{{ count($ingredients) }}</span>
                </h2>
                <ul class="space-y-2">
                    @foreach($ingredients as $i => $ingredient)
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 gradient-accent rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5">
                                {{ $i + 1 }}
                            </div>
                            <span class="text-gray-700 text-sm leading-relaxed">{{ is_array($ingredient) ? ($ingredient['name'] ?? json_encode($ingredient)) : $ingredient }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Steps --}}
        @php $steps = is_array($recipe['steps'] ?? null) ? $recipe['steps'] : []; @endphp
        @if(count($steps) > 0)
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    Langkah-langkah
                    <span class="ml-auto px-2.5 py-0.5 gradient-accent text-white text-xs font-bold rounded-full">{{ count($steps) }}</span>
                </h2>
                <ol class="space-y-4">
                    @foreach($steps as $i => $step)
                        <li class="flex gap-4">
                            <div class="w-8 h-8 gradient-accent rounded-2xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1 pt-1">
                                <p class="text-gray-700 text-sm leading-relaxed">{{ is_array($step) ? ($step['description'] ?? $step['step'] ?? json_encode($step)) : $step }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Save to collection --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4" x-data="{ saved: {{ $isFavorite ? 'true' : 'false' }} }">
            <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <div class="w-1 h-5 gradient-accent rounded-full"></div>
                Simpan Resep
            </h2>
            <form action="{{ route('app.favorites.save') }}" method="POST">
                @csrf
                <input type="hidden" name="recipe_id" value="{{ $recipe['id'] }}">
                <button type="submit"
                        class="w-full py-3 gradient-accent text-white font-bold rounded-2xl shadow hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    {{ $isFavorite ? 'Tersimpan di Koleksi' : 'Simpan ke Koleksi' }}
                </button>
            </form>
        </div>

        {{-- Rating --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
            <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-1 h-5 gradient-accent rounded-full"></div>
                Rating
                @if($avgRating > 0)
                    <span class="ml-auto flex items-center gap-1 text-yellow-500 font-bold">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ number_format($avgRating, 1) }} / 5
                        <span class="text-gray-400 font-normal text-xs">({{ $totalRatings }})</span>
                    </span>
                @endif
            </h2>
            <form action="{{ route('app.recipe.rate', $recipe['id']) }}" method="POST">
                @csrf
                <div class="flex items-center gap-2 mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="submit" name="rating" value="{{ $i }}"
                                class="text-3xl transition-transform hover:scale-110 {{ $i <= ($userRating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}">
                            ★
                        </button>
                    @endfor
                    @if($userRating)
                        <span class="text-sm text-gray-500 ml-2">Rating kamu: {{ $userRating }}/5</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Comments --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
            <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-1 h-5 gradient-accent rounded-full"></div>
                Komentar
                <span class="ml-auto px-2.5 py-0.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">{{ count($comments) }}</span>
            </h2>

            {{-- Post comment --}}
            <form action="{{ route('app.recipe.comment', $recipe['id']) }}" method="POST" class="mb-5">
                @csrf
                <div class="flex gap-3">
                    <div class="w-9 h-9 rounded-full gradient-accent flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr(session('user_username', 'U'), 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <textarea name="content" rows="2" placeholder="Tulis komentar..."
                                  class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm resize-none"></textarea>
                        <button type="submit"
                                class="mt-2 px-5 py-2 gradient-accent text-white text-sm font-bold rounded-xl shadow hover:shadow-md transition-all">
                            Kirim
                        </button>
                    </div>
                </div>
            </form>

            {{-- Comment list --}}
            @forelse($comments as $comment)
                @php $commenter = $comment['profiles'] ?? []; @endphp
                <div class="flex gap-3 mb-4 last:mb-0">
                    @if(!empty($commenter['avatar_url']))
                        <img src="{{ $commenter['avatar_url'] }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-full gradient-accent flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr($commenter['username'] ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <div class="bg-gray-50 rounded-2xl px-4 py-3">
                            <p class="font-bold text-gray-900 text-sm mb-1">{{ $commenter['username'] ?? 'Unknown' }}</p>
                            <p class="text-gray-700 text-sm leading-relaxed">{{ $comment['content'] }}</p>
                        </div>
                        <div class="flex items-center gap-3 mt-1 px-2">
                            <span class="text-xs text-gray-400">
                                @php
                                    try { echo \Carbon\Carbon::parse($comment['created_at'])->diffForHumans(); } catch(\Exception $e) { echo $comment['created_at'] ?? ''; }
                                @endphp
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
                <div class="text-center py-6 text-gray-400 text-sm">Belum ada komentar. Jadilah yang pertama!</div>
            @endforelse
        </div>

    </div>
</body>
</html>
