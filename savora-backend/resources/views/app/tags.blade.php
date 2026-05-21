@php($isEnglish = session('user_language', 'en') === 'en')
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Manage Tags' : 'Kelola Tag' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
</head>
<body style="background: var(--color-bg-light); color: var(--color-text-primary);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">

        @php
            $tagHeaderIcon = "<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'/></svg>";

            $addTagIcon = "<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'/></svg>";

            $popularTagIcon = "<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z M13 3h5c.512 0 1.024.195 1.414.586l2 2'/></svg>";
        @endphp

        {{-- Header --}}
        <div class="mb-6">
            <x-app-theme.section-header
                :title="$isEnglish ? 'Manage Tags' : 'Kelola Tag'"
                :icon="$tagHeaderIcon"
            />
            <p class="app-body-small mt-2" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Create and find community tags' : 'Buat dan cari tag komunitas' }}</p>
        </div>

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

        {{-- Create tag --}}
        <div class="card-savora p-5 mb-4">
            <div class="mb-4">
                <x-app-theme.section-header :title="$isEnglish ? 'Add New Tag' : 'Tambah Tag Baru'" :icon="$addTagIcon" />
            </div>
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
                               placeholder="{{ $isEnglish ? 'New tag name (ex: breakfast, vegan)' : 'Nama tag baru (cth: sarapan, vegan)' }}"
                               class="input-savora pl-10 pr-4 py-3">
                    </div>
                    <button type="submit" class="btn-primary-savora flex items-center gap-2 shrink-0 px-5 py-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $isEnglish ? 'Create' : 'Buat' }}
                    </button>
                </div>
            </form>
            <div class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-700 font-medium">
                {{ $isEnglish ? 'New tags will wait for admin approval before they can be used.' : 'Tag baru akan menunggu persetujuan admin sebelum bisa digunakan.' }}
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
                       placeholder="{{ $isEnglish ? 'Search existing tags...' : 'Cari tag yang sudah ada...' }}"
                       class="input-savora pl-12 pr-12 py-3.5">
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
            <div class="card-savora overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex-1">
                        <x-app-theme.section-header
                            :title="$query ? ($isEnglish ? 'Search Results' : 'Hasil Pencarian') : ($isEnglish ? 'Popular Tags' : 'Tag Populer')"
                            :icon="$popularTagIcon"
                        />
                    </div>
                    <span class="badge-savora">{{ count($tags) }}</span>
                </div>

                @foreach($tags as $tag)
                    <div class="flex items-center gap-4 px-5 py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                        <div class="w-9 h-9 bg-gradient-accent rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm">{{ $tag['name'] }}</p>
                            <p class="text-xs mt-0.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Used in' : 'Dipakai' }} {{ $tag['usage_count'] ?? 0 }} {{ $isEnglish ? 'recipes' : 'resep' }}</p>
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
                    {{ $query ? ($isEnglish ? 'Tag not found' : 'Tag tidak ditemukan') : ($isEnglish ? 'No tags yet' : 'Belum ada tag') }}
                </h3>
                <p class="text-gray-400 text-sm">
                    {{ $query ? ($isEnglish ? 'Try another keyword or create a new tag above' : 'Coba kata kunci lain atau buat tag baru di atas') : ($isEnglish ? 'Create the first tag above' : 'Buat tag pertama di atas') }}
                </p>
            </div>
        @endif

    </div>
</body>
</html>
