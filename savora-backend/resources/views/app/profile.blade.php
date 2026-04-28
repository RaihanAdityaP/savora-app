<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $profile['username'] ?? 'Profil' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent  { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .gradient-admin   { background: linear-gradient(135deg, #FFD700, #FFA500, #FF8C00, #FFD700); }
        .line-clamp-2     { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="$profile['avatar_url'] ?? null"
        :unread-count="0"
        :username="$profile['username'] ?? null"
    />

    @php
        $isAdmin      = ($profile['role'] ?? 'user') === 'admin';
        $useGradient  = $isOwnProfile && $isAdmin;
        $headerClass  = $useGradient ? 'gradient-admin' : 'gradient-accent';
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Profile Header Card --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl {{ $headerClass }} p-6 text-white">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>

            <div class="relative flex flex-col items-center text-center">
                {{-- Avatar --}}
                <div class="relative mb-4">
                    <div class="w-24 h-24 rounded-3xl overflow-hidden border-4 border-white/50 shadow-xl">
                        @if(!empty($profile['avatar_url']))
                            <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['username'] }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-white/25 flex items-center justify-center">
                                <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    @if($isAdmin)
                        <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-yellow-400 rounded-full flex items-center justify-center border-2 border-white shadow">
                            <svg class="w-4 h-4 text-yellow-900" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Name --}}
                <h1 class="text-2xl font-bold text-white mb-1">
                    {{ $profile['username'] ?? 'Unknown' }}
                </h1>
                @if(!empty($profile['full_name']))
                    <p class="text-white/90 text-sm mb-2">{{ $profile['full_name'] }}</p>
                @endif

                {{-- Role badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/20 rounded-full border border-white/40 mb-4">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($isAdmin)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        @elseif($profile['is_premium'] ?? false)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        @endif
                    </svg>
                    <span class="text-white text-xs font-bold tracking-wide">
                        {{ $isAdmin ? 'Admin' : (($profile['is_premium'] ?? false) ? 'Premium' : 'Member') }}
                    </span>
                </div>

                {{-- Bio --}}
                @if(!empty($profile['bio']))
                    <div class="w-full bg-white/20 rounded-2xl p-4 border border-white/30 mb-4 text-left">
                        <p class="text-white/95 text-sm leading-relaxed">{{ $profile['bio'] }}</p>
                    </div>
                @endif

                {{-- Stats --}}
                <div class="w-full grid grid-cols-3 gap-3">
                    <div class="bg-white/25 rounded-2xl p-3 border-2 border-white/40 text-center">
                        <p class="text-xl font-bold leading-none">{{ $recipesCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Resep</p>
                    </div>
                    <div class="bg-white/25 rounded-2xl p-3 border-2 border-white/40 text-center cursor-pointer hover:bg-white/30 transition-colors">
                        <p class="text-xl font-bold leading-none">{{ $followersCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Pengikut</p>
                    </div>
                    <div class="bg-white/25 rounded-2xl p-3 border-2 border-white/40 text-center cursor-pointer hover:bg-white/30 transition-colors">
                        <p class="text-xl font-bold leading-none">{{ $followingCount }}</p>
                        <p class="text-xs text-white/90 mt-1 font-semibold">Mengikuti</p>
                    </div>
                </div>

                {{-- Follow / Edit buttons --}}
                <div class="flex gap-3 mt-4 w-full">
                    @if($isOwnProfile)
                        <a href="#edit-profile"
                           class="flex-1 py-3 bg-white/25 border-2 border-white/50 text-white font-bold rounded-2xl text-sm text-center hover:bg-white/35 transition-all">
                            Edit Profil
                        </a>
                        <a href="{{ route('app.favorites') }}"
                           class="flex-1 py-3 bg-white/25 border-2 border-white/50 text-white font-bold rounded-2xl text-sm text-center hover:bg-white/35 transition-all">
                            Koleksi
                        </a>
                    @else
                        <form action="{{ route($isFollowing ? 'app.profile.unfollow' : 'app.profile.follow', $profile['id']) }}"
                              method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full py-3 {{ $isFollowing ? 'bg-white/20 border-2 border-white/40' : 'bg-white text-[#E76F51]' }} font-bold rounded-2xl text-sm hover:opacity-90 transition-all">
                                {{ $isFollowing ? 'Berhenti Mengikuti' : 'Ikuti' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- Edit Profile Form (own profile only) --}}
        @if($isOwnProfile)
            <div id="edit-profile" class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="p-2.5 gradient-accent rounded-xl">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Edit Profil</h2>
                </div>

                <form action="{{ route('app.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Username</label>
                        <input type="text" name="username" value="{{ old('username', $profile['username']) }}" required
                               class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Nama Lengkap</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $profile['full_name'] ?? '') }}"
                               class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Bio</label>
                        <textarea name="bio" rows="3"
                                  class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white transition-all text-sm font-medium resize-none">{{ old('bio', $profile['bio'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Foto Profil</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:gradient-accent file:text-white">
                    </div>
                    <button type="submit"
                            class="w-full py-3.5 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all text-sm">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        @endif

        {{-- Recipes --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-5">
                <div class="p-2.5 gradient-accent rounded-xl">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Resep Terbaru</h2>
                <span class="ml-auto px-3 py-1 bg-[#E76F51]/10 text-[#E76F51] text-xs font-bold rounded-full">{{ $recipesCount }}</span>
            </div>

            @forelse($recipes ?? [] as $recipe)
                @php
                    $category = $recipe['categories']['name'] ?? null;
                    $rating   = $recipe['average_rating'] ?? $recipe['rating_avg'] ?? null;
                @endphp
                <a href="{{ route('app.recipe.show', $recipe['id']) }}"
                   class="flex gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-colors mb-2 last:mb-0 active:scale-[0.98]">
                    <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
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
            @empty
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm font-medium">Belum ada resep yang dipublikasikan</p>
                    @if($isOwnProfile)
                        <a href="{{ route('app.recipe.create') }}"
                           class="inline-flex items-center gap-2 mt-3 px-5 py-2.5 gradient-accent text-white font-bold rounded-2xl text-sm shadow hover:shadow-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat Resep
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

    </div>
</body>
</html>
