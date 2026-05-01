<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board['name'] ?? 'Koleksi' }} — Savora</title>
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

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl gradient-accent p-6 text-white">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>
            <div class="relative">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-white/70 text-xs font-semibold uppercase tracking-wide mb-1">Koleksi</p>
                        <h1 class="text-2xl font-bold truncate">{{ $board['name'] }}</h1>
                        @if(!empty($board['description']))
                            <p class="text-white/80 text-sm mt-1">{{ $board['description'] }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <a href="{{ route('app.favorites') }}"
                           class="p-2.5 bg-white/20 rounded-xl border border-white/30 hover:bg-white/30 transition-all">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <form action="{{ route('app.favorites.board.delete', $board['id']) }}" method="POST"
                              onsubmit="return confirm('Hapus koleksi ini?')">
                            @csrf
                            <button type="submit"
                                    class="p-2.5 bg-red-500/80 rounded-xl border border-red-400/50 hover:bg-red-500 transition-all">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 rounded-full text-xs font-bold border border-white/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ count($recipes ?? []) }} Resep
                </span>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- Recipe list --}}
        @forelse($recipes ?? [] as $recipe)
            @php
                $author   = $recipe['profiles'] ?? [];
                $category = $recipe['categories']['name'] ?? null;
                $rating   = $recipe['average_rating'] ?? $recipe['rating_avg'] ?? null;
            @endphp
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all mb-4 border border-gray-100">
                <a href="{{ route('app.recipe.show', $recipe['id']) }}" class="flex gap-4 p-4">
                    <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
                        @if(!empty($recipe['image_url']))
                            <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full gradient-accent flex items-center justify-center">
                                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 line-clamp-2 text-sm leading-snug mb-1">{{ $recipe['title'] }}</h3>
                        @if($category)
                            <span class="inline-block px-2 py-0.5 gradient-accent text-white text-xs font-bold rounded-full mb-1">{{ $category }}</span>
                        @endif
                        @if($rating && $rating > 0)
                            <div class="flex items-center gap-1 text-yellow-500 text-xs font-semibold">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                {{ number_format($rating, 1) }}
                            </div>
                        @endif
                    </div>
                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0 self-center" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                {{-- Remove from collection --}}
                <div class="border-t border-gray-100">
                    <form action="{{ route('app.favorites.remove') }}" method="POST">
                        @csrf
                        <input type="hidden" name="board_id" value="{{ $board['id'] }}">
                        <input type="hidden" name="recipe_id" value="{{ $recipe['id'] }}">
                        <button type="submit"
                                class="w-full py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors"
                                onclick="return confirm('Hapus resep dari koleksi ini?')">
                            Hapus dari Koleksi
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">Koleksi masih kosong</h3>
                <p class="text-gray-400 text-sm mb-5">Tambahkan resep ke koleksi ini dari halaman detail resep</p>
                <a href="{{ route('app.home') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 gradient-accent text-white font-bold rounded-2xl text-sm shadow hover:shadow-lg transition-all">
                    Jelajahi Resep
                </a>
            </div>
        @endforelse

    </div>
</body>
</html>
