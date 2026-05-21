<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class ProfileController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
        private NotificationService $notification,
    ) {}

    // GET /app/profile atau /app/profile/{userId}
    public function show(?string $userId = null)
    {
        $currentUserId = session('user_id');
        $targetId      = $userId ?? $currentUserId;
        $isOwnProfile  = $targetId === $currentUserId;

        try {
            $profiles = $this->supabase->select(
                'profiles',
                ['*'],
                ['id' => $targetId]
            );

            if (empty($profiles)) abort(404, 'User tidak ditemukan.');

            $profile = $profiles[0];
            $targetSettings = $this->settingsService->get($targetId);

            if (! $this->canViewProfile($targetId, $currentUserId, $targetSettings)) {
                abort(403, 'Profil ini tidak publik.');
            }

            // Stats
            $recipesCount  = 0;
            $followersCount = 0;
            $followingCount = 0;
            $likedCount = 0;

            try { $recipesCount  = count($this->supabase->select('recipes', ['id'], ['user_id' => $targetId, 'status' => 'approved'])); } catch (Exception) {}
            try { $likedCount = $this->supabase->count('recipe_likes', ['user_id' => $targetId]); } catch (Exception) {}
            try {
                $followers = $this->supabase->select('follows', ['follower_id'], ['following_id' => $targetId]);
                $followersCount = collect($followers)
                    ->pluck('follower_id')
                    ->filter(fn ($followerId) => ! empty($followerId) && $followerId !== $targetId)
                    ->unique()
                    ->count();
            } catch (Exception) {}
            try {
                $following = $this->supabase->select('follows', ['following_id'], ['follower_id' => $targetId]);
                $followingCount = collect($following)
                    ->pluck('following_id')
                    ->filter(fn ($followingId) => ! empty($followingId) && $followingId !== $targetId)
                    ->unique()
                    ->count();
            } catch (Exception) {}

            // Resep user
            $recipes = [];
            try {
                $recipes = $this->supabase->select(
                    'recipes',
                    ['*', 'categories(name)', 'recipe_tags(tags(id, name))'],
                    ['user_id' => $targetId, 'status' => 'approved'],
                    ['order' => 'created_at.desc', 'limit' => 20]
                );
            } catch (Exception) {}
            $likedRecipes = $this->likedRecipesForUser($targetId, 20);

            // Follow status
            $isFollowing = false;
            if (! $isOwnProfile && $currentUserId) {
                try {
                    $check = $this->supabase->select('follows', ['id'], [
                        'follower_id'  => $currentUserId,
                        'following_id' => $targetId,
                    ]);
                    $isFollowing = ! empty($check);
                } catch (Exception) {}
            }

            return view('app.profile', compact(
                'profile', 'recipes', 'isOwnProfile',
                'recipesCount', 'followersCount', 'followingCount', 'likedCount', 'likedRecipes',
                'isFollowing', 'currentUserId'
            ));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function liked(string $userId)
    {
        $currentUserId = session('user_id');

        try {
            $profiles = $this->supabase->select('profiles', ['*'], ['id' => $userId]);
            if (empty($profiles)) abort(404, 'User tidak ditemukan.');

            $profile = $profiles[0];
            $targetSettings = $this->settingsService->get($userId);
            if (! $this->canViewProfile($userId, $currentUserId, $targetSettings)) {
                abort(403, 'Profil ini tidak publik.');
            }

            $recipes = $this->likedRecipesForUser($userId, 50);

            return view('app.profile.liked-recipes', compact('profile', 'recipes', 'currentUserId'));
        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function edit()
    {
        $userId = session('user_id');

        try {
            $profiles = $this->supabase->select('profiles', ['*'], ['id' => $userId]);
            if (empty($profiles)) abort(404, 'User tidak ditemukan.');

            return view('app.profile.edit', ['profile' => $profiles[0]]);
        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function followers(string $userId)
    {
        return $this->followList($userId, 'followers');
    }

    public function following(string $userId)
    {
        return $this->followList($userId, 'following');
    }

    // PUT /app/profile
    public function update(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50',
            'full_name' => 'nullable|string|max:100',
            'bio'       => 'nullable|string|max:500',
            'avatar'    => 'nullable|image|max:2048',
        ]);

        try {
            $userId = session('user_id');
            $data   = [
                'username'  => trim($request->input('username')),
                'full_name' => trim($request->input('full_name', '')),
                'bio'       => trim($request->input('bio', '')),
            ];

            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $path   = 'avatars/' . Str::uuid() . '.' . $avatar->getClientOriginalExtension();
                $this->supabase->uploadFile('avatars', $path, file_get_contents($avatar->getRealPath()), $avatar->getMimeType());
                $data['avatar_url'] = $this->supabase->getPublicUrl('avatars', $path);

                // Update session avatar
                session(['user_avatar' => $data['avatar_url']]);
            }

            $this->supabase->update('profiles', $data, ['id' => $userId]);
            session(['user_username' => $data['username']]);

            return back()->with('status', 'Profil berhasil diperbarui.');

        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/profile/{userId}/follow
    public function follow(string $userId)
    {
        try {
            $currentUserId = session('user_id');
            if ($currentUserId === $userId) return back()->with('error', 'Tidak bisa follow diri sendiri.');

            $existing = $this->supabase->select('follows', ['id'], [
                'follower_id'  => $currentUserId,
                'following_id' => $userId,
            ]);

            if (empty($existing)) {
                $this->supabase->insert('follows', [
                    'follower_id'  => $currentUserId,
                    'following_id' => $userId,
                ]);

                if ($this->settingsService->enabled($userId, 'notify_follows')) {
                    $actorName = session('user_username', 'Seseorang');
                    $this->supabase->insert('notifications', [
                        'user_id'             => $userId,
                        'type'                => 'new_follower',
                        'title'               => 'Follower Baru',
                        'message'             => "{$actorName} mulai mengikuti Anda!",
                        'related_entity_type' => 'profile',
                        'related_entity_id'   => $currentUserId,
                    ]);

                    $this->sendPushToUser($userId, 'Follower Baru', "{$actorName} mulai mengikuti Anda!", 'new_follower', $currentUserId);
                }
            }

            return back()->with('status', 'Berhasil mengikuti.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/profile/{userId}/unfollow
    public function unfollow(string $userId)
    {
        try {
            $currentUserId = session('user_id');
            $this->supabase->delete('follows', [
                'follower_id'  => $currentUserId,
                'following_id' => $userId,
            ]);

            return back()->with('status', 'Berhenti mengikuti.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function likedRecipesForUser(string $userId, int $limit = 20): array
    {
        try {
            $likes = $this->supabase->select(
                'recipe_likes',
                ['recipe_id', 'created_at'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc', 'limit' => $limit]
            );

            $recipeIds = collect($likes)->pluck('recipe_id')->filter()->unique()->values()->all();
            if (empty($recipeIds)) return [];

            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(id, username, full_name, avatar_url, role, is_premium)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['id' => ['operator' => 'in', 'values' => $recipeIds], 'status' => 'approved']
            );

            $order = array_flip($recipeIds);
            usort($recipes, fn ($a, $b) => ($order[$a['id'] ?? ''] ?? 9999) <=> ($order[$b['id'] ?? ''] ?? 9999));

            foreach ($recipes as $index => $recipe) {
                $recipeId = $recipe['id'] ?? null;
                if (! $recipeId) continue;
                try { $recipes[$index]['likes_count'] = $this->supabase->count('recipe_likes', ['recipe_id' => $recipeId]); } catch (Exception) { $recipes[$index]['likes_count'] = 0; }
                try {
                    $recipes[$index]['is_liked'] = ! empty($this->supabase->select('recipe_likes', ['id'], [
                        'recipe_id' => $recipeId,
                        'user_id' => session('user_id'),
                    ]));
                } catch (Exception) {
                    $recipes[$index]['is_liked'] = false;
                }
            }

            return $recipes;
        } catch (Exception) {
            return [];
        }
    }

    private function followList(string $userId, string $type)
    {
        try {
            $profiles = $this->supabase->select('profiles', ['id', 'username', 'avatar_url'], ['id' => $userId]);
            if (empty($profiles)) abort(404, 'User tidak ditemukan.');
            $currentUserId = session('user_id');
            $targetSettings = $this->settingsService->get($userId);
            if (! $this->canViewProfile($userId, $currentUserId, $targetSettings)) {
                abort(403, 'Profil ini tidak publik.');
            }

            $isFollowers = $type === 'followers';
            $rows = $this->supabase->select(
                'follows',
                [$isFollowers ? '*, profiles!follows_follower_id_fkey(*)' : '*, profiles!follows_following_id_fkey(*)'],
                [$isFollowers ? 'following_id' : 'follower_id' => $userId],
                ['order' => 'created_at.desc']
            );

            $users = [];
            foreach ($rows as $row) {
                $id = $isFollowers ? ($row['follower_id'] ?? null) : ($row['following_id'] ?? null);
                if (! $id || $id === $userId || isset($users[$id])) continue;
                $users[$id] = $row['profiles'] ?? [];
            }

            return view('app.profile.follow-list', [
                'profile' => $profiles[0],
                'users' => array_values($users),
                'type' => $type,
                'title' => $isFollowers ? 'Pengikut' : 'Mengikuti',
            ]);
        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    private function canViewProfile(string $targetId, ?string $viewerId, array $targetSettings): bool
    {
        if ($viewerId === $targetId || session('user_role') === 'admin') {
            return true;
        }

        if ((bool) ($targetSettings['profile_public'] ?? true)) {
            return true;
        }

        return $viewerId ? $this->isFollower($viewerId, $targetId) : false;
    }

    private function isFollower(string $viewerId, string $targetId): bool
    {
        try {
            return ! empty($this->supabase->select('follows', ['id'], [
                'follower_id' => $viewerId,
                'following_id' => $targetId,
            ]));
        } catch (Exception) {
            return false;
        }
    }

    private function sendPushToUser(string $userId, string $title, string $message, string $type, string $entityId): void
    {
        try {
            $deviceTokens = $this->supabase->select('device_tokens', ['token'], [
                'user_id' => $userId,
                'is_active' => true,
            ]);

            if (empty($deviceTokens)) return;

            $this->notification->sendToMultipleDevices(
                array_column($deviceTokens, 'token'),
                $title,
                $message,
                $this->notification->generatePayload($type, $entityId)
            );
        } catch (Exception $e) {
            \Log::warning('Failed to send push notification: ' . $e->getMessage());
        }
    }
}
