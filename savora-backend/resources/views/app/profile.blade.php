@php
    $isEnglish = session('user_language', 'en') === 'en';
    $isDarkTheme = session('user_theme', 'light') === 'dark';
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

        .profile-web-page {
            background: var(--profile-page-bg);
            color: var(--profile-text-primary);
        }

        .profile-theme-light {
            --profile-page-bg: var(--color-bg-light);
            --profile-surface-bg: var(--color-card-bg);
            --profile-surface-border: var(--color-card-border);
            --profile-text-primary: var(--color-text-primary);
            --profile-text-secondary: var(--color-text-secondary);
            --profile-text-muted: var(--color-text-muted);
            --profile-accent: var(--color-primary-coral);
            --profile-stat-divider: rgba(231,111,81,0.18);
            --profile-avatar-shadow: 0 10px 26px rgba(231,111,81,0.16);
        }

        .profile-theme-dark {
            --color-bg-light: #0b1017;
            --color-card-bg: #172433;
            --color-card-border: #314456;
            --color-chip-bg: rgba(231,111,81,0.12);
            --color-separator: rgba(203,213,225,0.16);
            --color-text-primary: #f8fafc;
            --color-text-secondary: #a8b3c2;
            --color-text-muted: #7d8b9d;
            --shadow-card: 0 12px 34px rgba(0,0,0,0.24);
            --profile-page-bg: #0b1017;
            --profile-surface-bg: #172433;
            --profile-surface-border: #314456;
            --profile-text-primary: #f8fafc;
            --profile-text-secondary: #a8b3c2;
            --profile-text-muted: #7d8b9d;
            --profile-accent: #ff8067;
            --profile-stat-divider: rgba(203,213,225,0.55);
            --profile-avatar-shadow: 0 0 30px rgba(231,111,81,0.30);
        }

        .profile-hero-card {
            background: var(--profile-page-bg);
            color: var(--profile-text-primary);
            border: none;
            box-shadow: none;
        }

        .profile-avatar-ring {
            border-color: rgba(231,111,81,0.65);
            box-shadow: var(--profile-avatar-shadow);
        }

        .profile-muted { color: var(--profile-text-secondary); }

        .profile-role-badge {
            background: rgba(231,111,81,0.10);
            border-color: rgba(231,111,81,0.45);
            color: var(--profile-accent);
        }

        .profile-info-box {
            background: var(--profile-surface-bg);
            border: 1.5px solid var(--profile-surface-border);
            color: var(--profile-text-secondary);
        }

        .stat-card {
            padding: 14px 6px;
            text-align: center;
            border-right: 1px solid var(--profile-stat-divider);
            cursor: pointer;
            transition: background 0.2s;
        }
        .stat-card:last-child { border-right: 0; }
        .stat-card:hover { background: var(--color-chip-bg); }

        .profile-action-primary {
            background: var(--gradient-accent);
            color: #ffffff;
            box-shadow: 0 12px 30px rgba(231,111,81,0.25);
        }

        .profile-action-outline {
            background: transparent;
            border: 1.5px solid rgba(231,111,81,0.45);
            color: var(--profile-accent);
        }

        .profile-action-outline:hover {
            background: rgba(231,111,81,0.08);
            color: var(--profile-accent);
        }

        .profile-section-title .header-title { color: var(--profile-text-primary); }

        .profile-section-count {
            background: rgba(231,111,81,0.10);
            border: 1px solid rgba(231,111,81,0.30);
            color: var(--profile-accent);
            box-shadow: none;
        }
    </style>
</head>

<body class="profile-web-page {{ $isDarkTheme ? 'profile-theme-dark' : 'profile-theme-light' }}">

    <x-unified-navigation
        :avatar-url="session('user_avatar') ?? null"
        :unread-count="$appUnreadCount ?? 0"
        :username="session('user_username') ?? null"
    />

    @php
        $isAdmin     = ($profile['role'] ?? 'user') === 'admin';
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
        <div class="profile-hero-card relative mb-6 p-0 text-white">

            <button type="button"
                    class="absolute top-4 right-4 z-10 w-11 h-11 rounded-2xl flex items-center justify-center text-white border transition-all hover:bg-white/25 active:scale-95"
                    style="background: #172433; border-color: #314456;"
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
                    <div class="profile-avatar-ring w-28 h-28 rounded-full overflow-hidden border-4 shadow-xl">
                        @if(!empty($profile['avatar_url']))
                            <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['username'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="background: #b8b8b8">
                                <svg class="w-12 h-12" style="color: #ffffff" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <h1 class="text-3xl font-bold mb-1" style="color: var(--profile-text-primary);">{{ $profile['username'] ?? 'Unknown' }}</h1>
                @if(!empty($profile['full_name']))
                    <p class="profile-muted text-base mb-4">{{ $profile['full_name'] }}</p>
                @endif

                {{-- Role badge --}}
                <div class="profile-role-badge inline-flex items-center gap-2 px-5 py-2 rounded-full border mb-5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($isAdmin)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        @elseif($profile['is_premium'] ?? false)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        @endif
                    </svg>
                    <span class="text-xs font-bold tracking-wide">
                        {{ $isAdmin ? 'Admin' : (($profile['is_premium'] ?? false) ? 'Premium' : 'Member') }}
                    </span>
                </div>

                {{-- Bio --}}
                @if($canViewProfile && !empty($profile['bio']))
                    <div class="profile-info-box w-full rounded-2xl p-4 mb-6 text-center">
                        <p class="text-sm leading-relaxed">{{ $profile['bio'] }}</p>
                    </div>
                @endif

                {{-- Stats --}}
                <div class="profile-info-box w-full grid grid-cols-4 gap-0 rounded-2xl overflow-hidden mb-6">
                    <div class="stat-card">
                        <p class="text-xl font-bold leading-none" style="color: var(--profile-text-primary);">{{ $recipesCount }}</p>
                        <p class="profile-muted text-xs mt-1 font-semibold">{{ $isEnglish ? 'Recipes' : 'Resep' }}</p>
                    </div>
                    <a href="{{ $canViewProfile ? route('app.profile.followers', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none" style="color: var(--profile-text-primary);">{{ $followersCount }}</p>
                        <p class="profile-muted text-xs mt-1 font-semibold">{{ $isEnglish ? 'Followers' : 'Pengikut' }}</p>
                    </a>
                    <a href="{{ $canViewProfile ? route('app.profile.following', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none" style="color: var(--profile-text-primary);">{{ $followingCount }}</p>
                        <p class="profile-muted text-xs mt-1 font-semibold">{{ $isEnglish ? 'Following' : 'Mengikuti' }}</p>
                    </a>
                    <a href="{{ $canViewProfile ? route('app.profile.likes', $profile['id']) : '#' }}" class="stat-card">
                        <p class="text-xl font-bold leading-none" style="color: var(--profile-text-primary);">{{ $likedCount ?? 0 }}</p>
                        <p class="profile-muted text-xs mt-1 font-semibold">Likes</p>
                    </a>
                </div>

                {{-- Follow / Edit buttons --}}
                <div class="flex flex-col gap-3 w-full">
                    @if($isOwnProfile)
                        <a href="{{ route('app.profile.edit') }}" class="profile-action-outline w-full py-3.5 rounded-2xl text-sm font-bold text-center">
                            {{ $isEnglish ? 'Edit Profile' : 'Edit Profil' }}
                        </a>
                        <a href="{{ route('app.favorites') }}" class="profile-action-outline w-full py-3.5 rounded-2xl text-sm font-bold text-center">
                            {{ $isEnglish ? 'Collections' : 'Koleksi' }}
                        </a>
                        <a href="{{ route('app.profile.likes', $profile['id']) }}" class="profile-action-outline w-full py-3.5 rounded-2xl text-sm font-bold text-center">
                            {{ $isEnglish ? 'Liked' : 'Disukai' }}
                        </a>
                    @else
                        <form action="{{ route($isFollowing ? 'app.profile.unfollow' : 'app.profile.follow', $profile['id']) }}"
                              method="POST" class="flex-1">
                            @csrf
                            @if($isFollowing)
                                <button type="submit" class="profile-action-outline w-full py-3.5 rounded-2xl text-sm font-bold">
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
                                        class="profile-action-primary w-full py-3.5 font-bold rounded-2xl text-base transition-all"
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
        <div>
            <div class="profile-section-title flex items-center gap-3 mb-5">
                <x-app-theme.section-header
                    title="{{ $isEnglish ? 'Latest Recipes' : 'Resep Terbaru' }}"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
                />
                <span class="profile-section-count ml-auto badge-savora">{{ $recipesCount }}</span>
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
