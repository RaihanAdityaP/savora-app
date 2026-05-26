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
            $canViewProfile = $this->canViewProfile($targetId, $currentUserId, $targetSettings);
            $followRequestStatus = $currentUserId
                ? $this->resolveFollowRequestStatus($currentUserId, $targetId)
                : null;

            // Stats
            $recipesCount  = 0;
            $followersCount = 0;
            $followingCount = 0;
            $likedCount = 0;

            if ($canViewProfile) {
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
            }

            // Resep user
            $recipes = [];
            if ($canViewProfile) try {
                $recipes = $this->supabase->select(
                    'recipes',
                    ['*', 'categories(name)', 'recipe_tags(tags(id, name))'],
                    ['user_id' => $targetId, 'status' => 'approved'],
                    ['order' => 'created_at.desc', 'limit' => 20]
                );
                $recipes = $this->enrichRecipeCards($recipes);
            } catch (Exception) {}
            $likedRecipes = $canViewProfile ? $this->likedRecipesForUser($targetId, 20) : [];

            // Follow status
            $isFollowing = $followRequestStatus === 'following';
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
                'isFollowing', 'currentUserId', 'canViewProfile', 'followRequestStatus'
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
            if ($currentUserId === $userId) {
                return back()->with('error', $this->tr('You cannot follow yourself.', 'Tidak bisa follow diri sendiri.'));
            }

            $existing = $this->supabase->select('follows', ['id'], [
                'follower_id'  => $currentUserId,
                'following_id' => $userId,
            ]);

            if (empty($existing)) {
                if (! $this->settingsService->enabled($userId, 'profile_public')) {
                    $pending = $this->supabase->select('follow_requests', ['id'], [
                        'requester_id' => $currentUserId,
                        'target_id' => $userId,
                        'status' => 'pending',
                    ]);

                    if (empty($pending)) {
                        $requestRows = $this->supabase->insert('follow_requests', [
                            'requester_id' => $currentUserId,
                            'target_id' => $userId,
                            'status' => 'pending',
                        ]);
                        $requestId = $requestRows[0]['id'] ?? '';

                        if ($this->settingsService->enabled($userId, 'notify_follows')) {
                            $actorName = session('user_username', 'Someone');
                            $this->supabase->insert('notifications', [
                                'user_id'             => $userId,
                                'type'                => 'follow_request',
                                'title'               => 'Follow Request',
                                'message'             => "{$actorName} wants to follow your private account.",
                                'related_entity_type' => 'follow_request',
                                'related_entity_id'   => $requestId,
                            ]);

                            $this->sendPushToUser($userId, 'Follow Request', "{$actorName} wants to follow your private account.", 'follow_request', $requestId);
                        }
                    }

                    return back()->with('status', $this->tr('Follow request sent.', 'Permintaan follow dikirim.'));
                }

                $this->supabase->insert('follows', [
                    'follower_id'  => $currentUserId,
                    'following_id' => $userId,
                ]);

                if ($this->settingsService->enabled($userId, 'notify_follows')) {
                    $actorName = session('user_username', 'Someone');
                    $this->supabase->insert('notifications', [
                        'user_id'             => $userId,
                        'type'                => 'new_follower',
                        'title'               => 'New Follower',
                        'message'             => "{$actorName} started following you!",
                        'related_entity_type' => 'profile',
                        'related_entity_id'   => $currentUserId,
                    ]);

                    $this->sendPushToUser($userId, 'New Follower', "{$actorName} started following you!", 'new_follower', $currentUserId);
                }
            }

            return back()->with('status', $this->tr('Followed successfully.', 'Berhasil mengikuti.'));
        } catch (Exception $e) {
            return back()->with('error', $this->tr('Failed: ', 'Gagal: ') . $e->getMessage());
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
            $this->supabase->update('follow_requests', [
                'status' => 'rejected',
                'responded_at' => now()->toDateTimeString(),
            ], [
                'requester_id' => $currentUserId,
                'target_id' => $userId,
                'status' => 'pending',
            ]);

            return back()->with('status', $this->tr('Unfollowed.', 'Berhenti mengikuti.'));
        } catch (Exception $e) {
            return back()->with('error', $this->tr('Failed: ', 'Gagal: ') . $e->getMessage());
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

            $recipes = $this->enrichRecipeCards($recipes);

            return $recipes;
        } catch (Exception) {
            return [];
        }
    }

    private function enrichRecipeCards(array $recipes): array
    {
        $recipeIds = array_values(array_filter(array_column($recipes, 'id')));
        if (empty($recipeIds)) {
            return $recipes;
        }

        $ratingSums = [];
        $ratingCounts = [];
        try {
            $ratings = $this->supabase->select(
                'recipe_ratings',
                ['recipe_id', 'rating'],
                ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
            );
            foreach ($ratings as $rating) {
                $recipeId = $rating['recipe_id'] ?? null;
                if (! $recipeId) continue;
                $ratingSums[$recipeId] = ($ratingSums[$recipeId] ?? 0) + (float) ($rating['rating'] ?? 0);
                $ratingCounts[$recipeId] = ($ratingCounts[$recipeId] ?? 0) + 1;
            }
        } catch (Exception) {}

        $likeCounts = [];
        $likedRecipeIds = [];
        try {
            $likes = $this->supabase->select(
                'recipe_likes',
                ['recipe_id', 'user_id'],
                ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
            );
            $viewerId = session('user_id');
            foreach ($likes as $like) {
                $recipeId = $like['recipe_id'] ?? null;
                if (! $recipeId) continue;
                $likeCounts[$recipeId] = ($likeCounts[$recipeId] ?? 0) + 1;
                if ($viewerId && ($like['user_id'] ?? null) === $viewerId) {
                    $likedRecipeIds[$recipeId] = true;
                }
            }
        } catch (Exception) {}

        foreach ($recipes as $index => $recipe) {
            $recipeId = $recipe['id'] ?? null;
            $ratingCount = $recipeId ? (int) ($ratingCounts[$recipeId] ?? 0) : 0;
            $recipes[$index]['rating_count'] = $ratingCount;
            $recipes[$index]['rating_avg'] = $ratingCount > 0
                ? round(($ratingSums[$recipeId] ?? 0) / $ratingCount, 1)
                : 0;
            $recipes[$index]['likes_count'] = $recipeId ? (int) ($likeCounts[$recipeId] ?? 0) : 0;
            $recipes[$index]['is_liked'] = $recipeId ? ! empty($likedRecipeIds[$recipeId]) : false;
        }

        return $recipes;
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

    private function resolveFollowRequestStatus(string $requesterId, string $targetId): ?string
    {
        if ($requesterId === $targetId) return null;
        if ($this->isFollower($requesterId, $targetId)) return 'following';

        try {
            $requests = $this->supabase->select('follow_requests', ['status'], [
                'requester_id' => $requesterId,
                'target_id' => $targetId,
            ], [
                'order' => 'created_at.desc',
                'limit' => 1,
            ]);

            return $requests[0]['status'] ?? null;
        } catch (Exception) {
            return null;
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

    private function tr(string $english, string $indonesian): string
    {
        return session('user_language', 'en') === 'en' ? $english : $indonesian;
    }
}
