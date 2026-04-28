<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Resep — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="$profile['avatar_url'] ?? session('user_avatar')"
        :unread-count="$unreadCount ?? 0"
        :username="$profile['username'] ?? session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-28 md:pb-10" x-data="{ showCreate: false }">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-[#E76F51]/10 rounded-2xl border border-[#E76F51]/25">
                    <svg class="w-7 h-7 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Koleksi Resep</h1>
                    <p class="text-gray-500 text-sm">Kumpulan resep favorit Anda</p>
                </div>
            </div>
            <span class="px-3 py-1.5 bg-[#E76F51]/10 border border-[#E76F51]/20 text-[#E76F51] text-sm font-bold rounded-full flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                {{ count($boards ?? []) }} Koleksi
            </span>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- Create collection form (collapsible) --}}
        <div class="mb-6">
            <button @click="showCreate = !showCreate"
                    class="w-full flex items-center justify-between px-5 py-4 bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 gradient-accent rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="font-bold text-gray-800">Koleksi Baru</span>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="showCreate ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showCreate" x-transition class="mt-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <form action="{{ route('app.favorites.board.create') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Nama Koleksi</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Contoh: Resep Sarapan"
                               class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Deskripsi (opsional)</label>
                        <textarea name="description" rows="2"
                                  placeholder="Deskripsi singkat koleksi ini..."
                                  class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium resize-none">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit"
                            class="w-full py-3.5 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all text-sm">
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
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all mb-4 border border-gray-100"
                 x-data="{ showOptions: false }">
                <a href="{{ route('app.favorites.board', $board['id']) }}" class="flex gap-4 p-4">
                    {{-- Photo grid --}}
                    <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
                        @if(count($boardPreviews) >= 4)
                            <div class="grid grid-cols-2 gap-0.5 w-full h-full">
                                @foreach(array_slice($boardPreviews, 0, 4) as $preview)
                                    <div class="overflow-hidden">
                                        <img src="{{ $preview['image_url'] ?? '' }}" alt=""
                                             class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        @elseif(count($boardPreviews) > 0)
                            <img src="{{ $boardPreviews[0]['image_url'] ?? '' }}" alt=""
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full gradient-accent flex items-center justify-center">
                                <svg class="w-10 h-10 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 text-base mb-1 truncate">{{ $board['name'] }}</h3>
                        @if(!empty($board['description']))
                            <p class="text-gray-500 text-sm line-clamp-2 mb-2">{{ $board['description'] }}</p>
                        @else
                            <p class="text-gray-400 text-sm italic mb-2">Koleksi resep spesial</p>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#E76F51]/10 text-[#E76F51] text-xs font-bold rounded-full">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            {{ $recipeCount }} Resep
                        </span>
                    </div>

                    {{-- Options button --}}
                    <button @click.prevent="showOptions = !showOptions"
                            class="p-2 text-gray-400 hover:text-gray-600 self-start flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </a>

                {{-- Options panel --}}
                <div x-show="showOptions" x-transition class="border-t border-gray-100 flex">
                    <a href="{{ route('app.favorites.board', $board['id']) }}"
                       class="flex-1 py-3 text-center text-sm font-semibold text-[#E76F51] hover:bg-orange-50 transition-colors">
                        Lihat Resep
                    </a>
                    <div class="w-px bg-gray-100"></div>
                    <form action="{{ route('app.favorites.board.delete', $board['id']) }}" method="POST" class="flex-1"
                          onsubmit="return confirm('Hapus koleksi {{ addslashes($board['name']) }}?')">
                        @csrf
                        <button type="submit" class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-50 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum ada koleksi</h3>
                <p class="text-gray-400 text-sm mb-6">Mulai buat koleksi resep favorit agar mudah diakses kembali</p>
                <button @click="showCreate = true; $nextTick(() => document.querySelector('[name=name]')?.focus())"
                        class="inline-flex items-center gap-2 px-6 py-3 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Koleksi Pertama
                </button>
            </div>
        @endforelse

    </div>
</body>
</html>
