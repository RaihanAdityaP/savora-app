@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ session('user_language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEnglish ? 'Edit Profile' : 'Edit Profil' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('components.app-theme')
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">
    <x-unified-navigation
        :avatar-url="$profile['avatar_url'] ?? session('user_avatar')"
        :unread-count="0"
        :username="$profile['username'] ?? session('user_username')"
    />

    <main class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-10">
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('app.profile') }}" class="btn-icon-savora w-11 h-11 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold" style="color: var(--color-text-primary);">{{ $isEnglish ? 'Edit Profile' : 'Edit Profil' }}</h1>
                <p class="text-sm" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Update your account identity.' : 'Perbarui identitas akun kamu.' }}</p>
            </div>
        </div>

        <div class="card-savora p-6">
            <form action="{{ route('app.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="modal-label">Username</label>
                    <input type="text" name="username" value="{{ old('username', $profile['username']) }}" required class="input-savora">
                </div>
                <div>
                    <label class="modal-label">{{ $isEnglish ? 'Full Name' : 'Nama Lengkap' }}</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $profile['full_name'] ?? '') }}" class="input-savora">
                </div>
                <div>
                    <label class="modal-label">Bio</label>
                    <textarea name="bio" rows="4" class="input-savora resize-none">{{ old('bio', $profile['bio'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="modal-label">{{ $isEnglish ? 'Profile Photo' : 'Foto Profil' }}</label>
                    <input type="file" name="avatar" accept="image/*" class="input-savora file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:text-white">
                </div>
                <button type="submit" class="btn-primary-savora w-full">{{ $isEnglish ? 'Save Changes' : 'Simpan Perubahan' }}</button>
            </form>
        </div>
    </main>
</body>
</html>
