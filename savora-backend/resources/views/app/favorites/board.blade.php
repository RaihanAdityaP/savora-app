<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board['name'] ?? 'Koleksi' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; background: var(--color-bg-light); }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header card --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl p-6 text-white"
             style="background: var(--gradient-accent);">
            {{-- Decorative blobs --}}
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>

            <div class="relative">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-white/70 text-xs font-semibold uppercase tracking-widest mb-1">Koleksi</p>
                        <h1 class="text-2xl font-bold truncate">{{ $board['name'] }}</h1>
                        @if(!empty($board['description']))
                            <p class="text-white/80 text-sm mt-1">{{ $board['description'] }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        {{-- Back --}}
                        <a href="{{ route('app.favorites') }}"
                           class="p-2.5 rounded-xl border transition-all hover:bg-white/30"
                           style="background: rgba(255,255,255,0.20); border-color: rgba(255,255,255,0.30);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        {{-- Delete collection --}}
                        <form action="{{ route('app.favorites.board.delete', $board['id']) }}" method="POST"
                              onsubmit="return confirm('Hapus koleksi ini?')">
                            @csrf
                            <button type="submit"
                                    class="p-2.5 rounded-xl border transition-all"
                                    style="background: rgba(239,68,68,0.70); border-color: rgba(239,68,68,0.50);">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Recipe count pill --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border"
                      style="background: rgba(255,255,255,0.20); border-color: rgba(255,255,255,0.30);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ count($recipes ?? []) }} Resep
                </span>
            </div>
        </div>

        {{-- Flash messages --}}
        @if(session('status'))
            <div class="mb-4">
                <x-app-theme.info-banner message="{{ session('status') }}" icon="bi bi-check-circle" />
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Recipe list --}}
        @forelse($recipes ?? [] as $recipe)
            @php
                $category = $recipe['categories']['name'] ?? null;
                $rating   = $recipe['average_rating'] ?? $recipe['rating_avg'] ?? null;
            @endphp
            <div class="card-savora overflow-hidden mb-4 transition-all hover:shadow-lg">
                <a href="{{ route('app.recipe.show', $recipe['id']) }}" class="flex gap-4 p-4">

                    {{-- Thumbnail --}}
                    <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
                        @if(!empty($recipe['image_url']))
                            <img src="{{ $recipe['image_url'] }}" alt="{{ $recipe['title'] }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: var(--gradient-accent);">
                                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-sm line-clamp-2 leading-snug mb-1"
                            style="color: var(--color-text-primary);">
                            {{ $recipe['title'] }}
                        </h3>
                        @if($category)
                            <span class="inline-block px-2 py-0.5 text-white text-xs font-bold rounded-full mb-1"
                                  style="background: var(--gradient-accent);">
                                {{ $category }}
                            </span>
                        @endif
                        @if($rating && $rating > 0)
                            <div class="flex items-center gap-1 text-xs font-semibold"
                                 style="color: var(--color-primary-yellow);">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                {{ number_format($rating, 1) }}
                            </div>
                        @endif
                    </div>

                    <svg class="w-4 h-4 flex-shrink-0 self-center" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color: #D1D5DB;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Remove from collection --}}
                <div class="border-t" style="border-color: rgba(231,111,81,0.10);">
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
            <x-app-theme.empty-state
                icon="bi bi-book"
                title="Koleksi masih kosong"
                subtitle="Tambahkan resep ke koleksi ini dari halaman detail resep">
                <a href="{{ route('app.home') }}" class="btn-primary-savora mt-2">
                    Jelajahi Resep
                </a>
            </x-app-theme.empty-state>
        @endforelse

    </div>
</body>
</html>