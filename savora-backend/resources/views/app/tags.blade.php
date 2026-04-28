<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tag — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header card --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl gradient-accent p-6 text-white">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>
            <div class="relative flex items-center gap-4">
                <div class="p-3 bg-white/25 rounded-2xl border-2 border-white/40">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Kelola Tag</h1>
                    <p class="text-white/80 text-sm">Buat dan cari tag komunitas</p>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        {{-- Create tag --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
            <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-1 h-5 gradient-accent rounded-full"></div>
                Tambah Tag Baru
            </h2>
            <form action="{{ route('app.tags.store') }}" method="POST">
                @csrf
                <div class="flex gap-3">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-[#E76F51]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <input type="text" name="name" value="{{ old('name') }}"
                               placeholder="Nama tag baru (cth: sarapan, vegan)"
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium">
                    </div>
                    <button type="submit"
                            class="px-5 py-3 gradient-accent text-white font-bold rounded-2xl shadow hover:shadow-lg transition-all flex items-center gap-2 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat
                    </button>
                </div>
            </form>
            <div class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-700 font-medium">
                Tag baru akan menunggu persetujuan admin sebelum bisa digunakan.
            </div>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('app.tags') }}" class="mb-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-[#2A9D8F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="q" value="{{ $query }}"
                       placeholder="Cari tag yang sudah ada..."
                       class="w-full pl-12 pr-12 py-3.5 bg-white rounded-2xl border border-gray-200 shadow-sm focus:outline-none focus:border-[#2A9D8F] text-sm font-medium transition-all">
                @if($query)
                    <a href="{{ route('app.tags') }}"
                       class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tag list --}}
        @if(count($tags) > 0)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="w-1 h-5 gradient-accent rounded-full"></div>
                    <h2 class="font-bold text-gray-900 flex-1">
                        {{ $query ? 'Hasil Pencarian' : 'Tag Populer' }}
                    </h2>
                    <span class="px-3 py-1 gradient-accent text-white text-xs font-bold rounded-full">
                        {{ count($tags) }}
                    </span>
                </div>

                @foreach($tags as $tag)
                    <div class="flex items-center gap-4 px-5 py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                        <div class="w-9 h-9 gradient-accent rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm">{{ $tag['name'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Dipakai {{ $tag['usage_count'] ?? 0 }} resep</p>
                        </div>

                        @if($tag['is_approved'] ?? false)
                            <span class="px-2.5 py-1 bg-green-50 border border-green-200 text-green-700 text-xs font-bold rounded-full">
                                Approved
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-orange-50 border border-orange-200 text-orange-700 text-xs font-bold rounded-full">
                                Pending
                            </span>
                        @endif

                        <a href="{{ route('app.search', ['tag_id' => $tag['id']]) }}"
                           class="p-1.5 text-gray-400 hover:text-[#E76F51] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">
                    {{ $query ? 'Tag tidak ditemukan' : 'Belum ada tag' }}
                </h3>
                <p class="text-gray-400 text-sm">
                    {{ $query ? 'Coba kata kunci lain atau buat tag baru di atas' : 'Buat tag pertama di atas' }}
                </p>
            </div>
        @endif

    </div>
</body>
</html>
