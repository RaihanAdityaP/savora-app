<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Resep — Savora</title>
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
        :avatar-url="$profile['avatar_url'] ?? session('user_avatar')"
        :unread-count="$unreadCount ?? 0"
        :username="$profile['username'] ?? session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-28 md:pb-10" x-data="{ showCreate: false }">

        {{-- Page Header --}}
        <div class="mb-6">
            <x-app-theme.section-header
                title="Koleksi Resep"
                icon="<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'/></svg>"
            />
            <p class="mt-2 text-sm" style="color: var(--color-text-secondary); padding-left: 56px;">
                Kumpulan resep favorit Anda
            </p>
        </div>

        {{-- Flash messages --}}
        @if(session('status'))
            <x-app-theme.info-banner
                message="{{ session('status') }}"
                icon="bi bi-check-circle"
            />
            <div class="mb-4"></div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Stats pill --}}
        <div class="flex items-center justify-between mb-5">
            <span class="badge-savora" style="font-size: 13px; padding: 6px 14px; border-radius: var(--radius-full);">
                {{ count($boards ?? []) }} Koleksi
            </span>
        </div>

        {{-- Create collection toggle --}}
        <div class="mb-6">
            <button @click="showCreate = !showCreate"
                    class="w-full flex items-center justify-between px-5 py-4 transition-all"
                    style="background: var(--color-card-bg); border-radius: var(--radius-xl); border: 2px solid rgba(231,111,81,0.15); box-shadow: var(--shadow-card);">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 flex items-center justify-center"
                         style="background: var(--gradient-accent); border-radius: var(--radius-sm);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="font-bold" style="color: var(--color-text-primary);">Buat Koleksi Baru</span>
                </div>
                <svg class="w-5 h-5 transition-transform" :class="showCreate ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color: var(--color-text-secondary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Create form --}}
            <div x-show="showCreate" x-transition class="mt-2 p-5 card-savora">
                <form action="{{ route('app.favorites.board.create') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2"
                               style="color: var(--color-text-secondary);">Nama Koleksi</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Contoh: Resep Sarapan"
                               class="input-savora">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-2"
                               style="color: var(--color-text-secondary);">Deskripsi (opsional)</label>
                        <textarea name="description" rows="2"
                                  placeholder="Deskripsi singkat koleksi ini..."
                                  class="input-savora resize-none">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn-primary-savora w-full">
                        Buat Koleksi
                    </button>
                </form>
            </div>
        </div>

        {{-- Collections list --}}
        @forelse($boards ?? [] as $board)
            @php
                $boardPreviews = $previews[$board['id']] ?? [];
                $recipeCount  = $board['recipe_count'] ?? 0;
            @endphp
            <div class="card-savora overflow-hidden mb-4 transition-all hover:shadow-lg"
                 x-data="{ showOptions: false }">
                <a href="{{ route('app.favorites.board', $board['id']) }}" class="flex gap-4 p-4">

                    {{-- Photo grid --}}
                    <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
                        @php $imgs = array_slice($boardPreviews, 0, 4); $cnt = count($imgs); @endphp
                        @if($cnt === 0)
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: var(--gradient-accent);">
                                <svg class="w-10 h-10 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @elseif($cnt === 1)
                            <img src="{{ $imgs[0]['image_url'] ?? '' }}" class="w-full h-full object-cover" alt="">
                        @elseif($cnt === 2)
                            <div class="grid grid-cols-2 h-full gap-px">
                                @foreach($imgs as $img)
                                    <img src="{{ $img['image_url'] ?? '' }}" class="w-full h-full object-cover" alt="">
                                @endforeach
                            </div>
                        @elseif($cnt === 3)
                            <div class="grid grid-cols-2 h-full gap-px">
                                <img src="{{ $imgs[0]['image_url'] ?? '' }}" class="w-full h-full object-cover row-span-2" alt="">
                                <img src="{{ $imgs[1]['image_url'] ?? '' }}" class="w-full h-full object-cover" alt="">
                                <img src="{{ $imgs[2]['image_url'] ?? '' }}" class="w-full h-full object-cover" alt="">
                            </div>
                        @else
                            <div class="grid grid-cols-2 h-full gap-px">
                                @foreach($imgs as $img)
                                    <img src="{{ $img['image_url'] ?? '' }}" class="w-full h-full object-cover" alt="">
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-base mb-1 truncate" style="color: var(--color-text-primary);">
                            {{ $board['name'] }}
                        </h3>
                        @if(!empty($board['description']))
                            <p class="text-sm line-clamp-2 mb-2" style="color: var(--color-text-secondary);">
                                {{ $board['description'] }}
                            </p>
                        @else
                            <p class="text-sm italic mb-2" style="color: #9CA3AF;">Koleksi resep spesial</p>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                              style="background: rgba(231,111,81,0.10); color: var(--color-primary-coral);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            {{ $recipeCount }} Resep
                        </span>
                    </div>

                    {{-- Options button --}}
                    <button @click.prevent="showOptions = !showOptions"
                            class="p-2 self-start flex-shrink-0 transition-colors"
                            style="color: #9CA3AF;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </a>

                {{-- Options panel --}}
                <div x-show="showOptions" x-transition
                     class="flex border-t" style="border-color: rgba(231,111,81,0.10);">
                    <a href="{{ route('app.favorites.board', $board['id']) }}"
                       class="flex-1 py-3 text-center text-sm font-semibold transition-colors hover:bg-orange-50"
                       style="color: var(--color-primary-coral);">
                        Lihat Resep
                    </a>
                    <div class="w-px" style="background: rgba(231,111,81,0.10);"></div>
                    <form action="{{ route('app.favorites.board.delete', $board['id']) }}" method="POST"
                          class="flex-1"
                          onsubmit="return confirm('Hapus koleksi {{ addslashes($board['name']) }}?')">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-50 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <x-app-theme.empty-state
                icon="bi bi-collection"
                title="Belum ada koleksi"
                subtitle="Mulai buat koleksi resep favorit agar mudah diakses kembali">
                <button @click="showCreate = true; $nextTick(() => document.querySelector('[name=name]')?.focus())"
                        class="btn-primary-savora mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Koleksi Pertama
                </button>
            </x-app-theme.empty-state>
        @endforelse

    </div>
</body>
</html>