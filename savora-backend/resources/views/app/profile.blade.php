@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $profile['username'] ?? ($isEnglish ? 'Profile' : 'Profil') }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .modal-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            color: var(--color-text-secondary);
        }

        .modal-close-btn {
            padding: 8px;
            border-radius: 9999px;
            border: none;
            cursor: pointer;
            background: transparent;
            color: var(--color-text-muted);
            transition: background 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-close-btn:hover { background: var(--color-chip-bg); }

        .modal-drag-bar {
            width: 40px; height: 4px;
            border-radius: 9999px;
            background: var(--color-separator);
            margin: 12px auto 4px;
        }

        .stat-card {
            border-radius: 16px;
            padding: 12px;
            border-width: 2px;
            border-style: solid;
            text-align: center;
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.40);
            cursor: pointer;
            transition: background 0.2s;
        }
        .stat-card:hover { background: rgba(255,255,255,0.35); }
    </style>
</head>

<body>

    <x-unified-navigation
        :avatar-url="session('user_avatar') ?? null"
        :unread-count="$appUnreadCount ?? 0"
        :username="session('user_username') ?? null"
    />

    @php
        $isAdmin     = ($profile['role'] ?? 'user') === 'admin';
        $useGradient = $isOwnProfile && $isAdmin;
        $headerBg    = $useGradient ? 'var(--gradient-admin)' : 'var(--gradient-accent)';
        $canViewProfile = $canViewProfile ?? true;
        $followRequestStatus = $followRequestStatus ?? null;
        $isFollowPending = $followRequestStatus === 'pending';
        $shareUrl = !empty($profile['id']) ? route('web.profile.share', $profile['id']) : url()->current();
        $shareUsername = trim((string) ($profile['username'] ?? '')) !== '' ? '@' . $profile['username'] : ($isEnglish ? 'this profile' : 'profil ini');
        $shareText = $isEnglish
            ? "View {$shareUsername} on Savora: {$shareUrl}"
            : "Lihat {$shareUsername} di Savora: {$shareUrl}";
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- ===================== Profile Header Card ===================== --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 shadow-xl p-6 text-white"
             style="background: {{ $headerBg }}">

            {{-- Decorative blobs --}}
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white opacity-[0.08] rounded-full pointer-events-none"></div>

            <button type="button"
                    class="absolute top-4 right-4 z-10 w-11 h-11 rounded-2xl flex items-center justify-center text-white border transition-all hover:bg-white/25 active:scale-95"
                    style="background: rgba(255,255,255,0.16); border-color: rgba(255,255,255,0.35);"
                    data-share-title="{{ $isEnglish ? 'Savora Profile' : 'Profil Savora' }}"
                    data-share-text="{{ e($shareText) }}"
                    data-share-url="{{ e($shareUrl) }}"
                    onclick="shareProfileFromWeb(this)"
                    aria-label="{{ $isEnglish ? 'Share profile' : 'Bagikan profil' }}">
                <svg class="savora-svg-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12a3 3 0 100-6 3 3 0 000 6zm10-3a3 3 0 100-6 3 3 0 000 6zM17 21a3 3 0 100-6 3 3 0 000 6zM9.59 10.51l4.83-2.02M9.59 13.49l4.83 2.02"/>
                </svg>
            </button>

            <div class="relative flex flex-col items-center text-center">

                {{-- Avatar --}}
                <div class="relative mb-4">
                    <div class="w-24 h-24 rounded-3xl overflow-hidden border-4 shadow-xl" style="border-color: rgba(255,255,255,0.50)">
                        @if(!empty($profile['avatar_url']))
                            <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['username'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="background: rgba(255,255,255,0.25)">
                                <svg class="w-12 h-12" style="color: rgba(255,255,255,0.80)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <h1 class="text-2xl font-bold text-white mb-1">{{ $profile['username'] ?? 'Unknown' }}</h1>
                @if(!empty($profile['full_name']))
                    <p class="text-sm mb-2" style="color: rgba(255,255,255,0.90)">{{ $profile['full_name'] }}</p>
                @endif

                {{-- Role badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border mb-4"
                     style="background: rgba(255,255,255,0.20); border-color: rgba(255,255,255,0.40)">
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
                @if($canViewProfile && !empty($profile['bio']))
                    <div class="w-full rounded-2xl p-4 border mb-4 text-left"
                         style="background: rgba(255,255,255,0.20); border-color: rgba(255,255,255,0.30)">
                        <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.95)">{{ $profile['bio'] }}</p>
                    </div>
                @endif

                {{-- Stats --}}
                <div class="w-full grid grid-cols-4 gap-3">
                    <div class="stat-card">
                        <p class="text-xl font-bold leading-none text-white">{{ $recipesCount }}</p>
                        <p class="text-xs mt-1 font-semibold" style="color: rgba(255,255,255,0.90)">{{ $isEnglish ? 'Recipes' : 'Resep' }}</p>
                    </div>
                    <a href="{{ $canViewProfile ? route('app.profile.followers', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none text-white">{{ $followersCount }}</p>
                        <p class="text-xs mt-1 font-semibold" style="color: rgba(255,255,255,0.90)">{{ $isEnglish ? 'Followers' : 'Pengikut' }}</p>
                    </a>
                    <a href="{{ $canViewProfile ? route('app.profile.following', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none text-white">{{ $followingCount }}</p>
                        <p class="text-xs mt-1 font-semibold" style="color: rgba(255,255,255,0.90)">{{ $isEnglish ? 'Following' : 'Mengikuti' }}</p>
                    </a>
                    <a href="{{ $canViewProfile ? route('app.profile.likes', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none text-white">{{ $likedCount ?? 0 }}</p>
                        <p class="text-xs mt-1 font-semibold" style="color: rgba(255,255,255,0.90)">Like</p>
                    </a>
                </div>

                {{-- Follow / Edit buttons --}}
                <div class="flex gap-3 mt-4 w-full">
                    @if($isOwnProfile)
                        <a href="{{ route('app.profile.edit') }}" class="btn-outlined-savora flex-1 py-3 text-sm text-center">
                            {{ $isEnglish ? 'Edit Profile' : 'Edit Profil' }}
                        </a>
                        <a href="{{ route('app.favorites') }}" class="btn-outlined-savora flex-1 py-3 text-sm text-center">
                            {{ $isEnglish ? 'Collections' : 'Koleksi' }}
                        </a>
                        <a href="{{ route('app.profile.likes', $profile['id']) }}" class="btn-outlined-savora flex-1 py-3 text-sm text-center">
                            {{ $isEnglish ? 'Liked' : 'Disukai' }}
                        </a>
                    @else
                        <form action="{{ route($isFollowing ? 'app.profile.unfollow' : 'app.profile.follow', $profile['id']) }}"
                              method="POST" class="flex-1">
                            @csrf
                            @if($isFollowing)
                                <button type="submit" class="btn-outlined-savora w-full py-3 text-sm">
                                    {{ $isEnglish ? 'Unfollow' : 'Berhenti Mengikuti' }}
                                </button>
                            @elseif($isFollowPending)
                                <button type="button" disabled
                                        class="w-full py-3 font-bold rounded-2xl text-sm opacity-80 cursor-not-allowed"
                                        style="background: rgba(255,255,255,0.85); color: var(--color-text-secondary);">
                                    {{ $isEnglish ? 'Requested' : 'Diminta' }}
                                </button>
                            @else
                                <button type="submit"
                                        class="w-full py-3 font-bold rounded-2xl text-sm transition-all"
                                        style="background: #ffffff; color: var(--color-primary-coral);"
                                        onmouseover="this.style.opacity='0.90'"
                                        onmouseout="this.style.opacity='1'">
                                    {{ $canViewProfile ? ($isEnglish ? 'Follow' : 'Ikuti') : ($isEnglish ? 'Request Follow' : 'Minta Follow') }}
                                </button>
                            @endif
                        </form>
                    @endif
                </div>

            </div>
        </div>

        {{-- Flash messages --}}
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

        @if(!$canViewProfile && !$isOwnProfile)
            <div class="card-savora p-8 text-center">
                <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
                     style="background: var(--color-chip-bg); color: var(--color-primary-coral);">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v4H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V7a3 3 0 116 0v4H9z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold mb-2" style="color: var(--color-text-primary)">
                    {{ $isEnglish ? 'Private Profile' : 'Profil Private' }}
                </h2>
                <p class="text-sm" style="color: var(--color-text-secondary)">
                    {{ $isFollowPending
                        ? ($isEnglish ? 'Your follow request is waiting for approval.' : 'Permintaan follow Anda menunggu persetujuan.')
                        : ($isEnglish ? 'Send a follow request to view this profile.' : 'Kirim permintaan follow untuk melihat profil ini.') }}
                </p>
            </div>
        @else
        {{-- ===================== Recipes Section ===================== --}}
        <div class="card-savora p-6">
            <div class="flex items-center gap-3 mb-5">
                <x-app-theme.section-header
                    title="{{ $isEnglish ? 'Latest Recipes' : 'Resep Terbaru' }}"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
                />
                <span class="ml-auto badge-savora">{{ $recipesCount }}</span>
            </div>

            @forelse($recipes ?? [] as $recipe)
                @php
                    $rating = $recipe['average_rating'] ?? $recipe['rating_avg'] ?? null;
                @endphp
                <x-recipe-card
                    :recipe="$recipe"
                    :rating="$rating"
                    :current-user-id="session('user_id')"
                    :favorite-boards="$favoriteBoards ?? []"
                    :saved-board-ids="$recipeSavedBoards[$recipe['id']] ?? []"
                    :detail-href="route('app.recipe.show', $recipe['id'])"
                />

            @empty
                <x-app-theme.empty-state
                    icon="bi bi-journal-richtext"
                    title="{{ $isEnglish ? 'No published recipes yet' : 'Belum ada resep yang dipublikasikan' }}"
                >
                    @if($isOwnProfile)
                        <a href="{{ route('app.recipe.create') }}" class="btn-primary-savora mt-2" style="padding: 10px 20px">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $isEnglish ? 'Create Recipe' : 'Buat Resep' }}
                        </a>
                    @endif
                </x-app-theme.empty-state>
            @endforelse
        </div>
        @endif

    </div>
    <script>
        async function shareProfileFromWeb(button) {
            const title = button.dataset.shareTitle || document.title;
            const text = button.dataset.shareText || title;
            const url = button.dataset.shareUrl || window.location.href;

            try {
                if (navigator.share) {
                    await navigator.share({ title, text, url });
                    return;
                }

                await navigator.clipboard?.writeText(url);
                const original = button.innerHTML;
                button.innerHTML = '<span class="text-xs font-bold">{{ $isEnglish ? 'Copied' : 'Disalin' }}</span>';
                setTimeout(() => { button.innerHTML = original; }, 1600);
            } catch (error) {
                console.warn('Profile share failed', error);
            }
        }
    </script>
</body>
</html>
