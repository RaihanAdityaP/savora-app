<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class UserController extends Controller
{
    private SupabaseService $supabase;
    private NotificationService $notification;
    private UserSettingsService $settingsService;

    public function __construct(SupabaseService $supabase, NotificationService $notification, UserSettingsService $settingsService)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
        $this->settingsService = $settingsService;
    }

    /**
     * Get all users
     * GET /api/v1/users
     */
    public function index(Request $request)
    {
        try {
            $filters = [];
            $options = [];

            if ($request->has('role')) {
                $filters['role'] = $request->input('role');
            }

            $limit = $request->input('limit', 20);
            $offset = $request->input('offset', 0);
            $options['limit'] = $limit;
            $options['offset'] = $offset;

            $users = $this->supabase->select('profiles', ['*'], $filters, $options);

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single user profile
     * GET /api/v1/users/{id}
     */
    public function show(Request $request, string $id)
    {
        try {
            $users = $this->supabase->select('profiles', ['*'], ['id' => $id]);

            if (empty($users)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $user = $users[0];
            $viewerId = $this->resolveViewerId($request);
            if (! $this->canViewProfile($id, $viewerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private',
                ], 403);
            }

            // Keep profile endpoint resilient even if supporting tables/views are unavailable.
            $user['followers_count'] = 0;
            $user['total_followers'] = 0;
            $user['following_count'] = 0;
            $user['total_following'] = 0;
            $user['recipes_count'] = 0;
            $user['liked_recipes_count'] = 0;

            try {
                // Schema table name is 'follows', not 'followers'
                $followers = $this->supabase->select('follows', ['follower_id'], ['following_id' => $id]);
                $followersCount = collect($followers)
                    ->pluck('follower_id')
                    ->filter(fn ($followerId) => ! empty($followerId) && $followerId !== $id)
                    ->unique()
                    ->count();
                $user['followers_count'] = $followersCount;
                $user['total_followers'] = $followersCount;
            } catch (Exception $e) {
                // keep default value
            }

            try {
                $following = $this->supabase->select('follows', ['following_id'], ['follower_id' => $id]);
                $followingCount = collect($following)
                    ->pluck('following_id')
                    ->filter(fn ($followingId) => ! empty($followingId) && $followingId !== $id)
                    ->unique()
                    ->count();
                $user['following_count'] = $followingCount;
                $user['total_following'] = $followingCount;
            } catch (Exception $e) {
                // keep default value
            }

            try {
                $recipes = $this->supabase->select('recipes', ['id'], ['user_id' => $id, 'status' => 'approved']);
                $user['recipes_count'] = count($recipes);
            } catch (Exception $e) {
                // keep default value
            }

            try {
                $user['liked_recipes_count'] = $this->supabase->count('recipe_likes', ['user_id' => $id]);
            } catch (Exception $e) {
                // keep default value
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get single user profile (alias)
     * GET /api/v1/users/{id}/profile
     */
    public function profile(Request $request, string $id)
    {
        return $this->show($request, $id);
    }

    public function likedRecipes(Request $request, string $id)
    {
        try {
            $limit = (int) $request->input('limit', 20);
            $limit = max(1, min(50, $limit));
            $viewerId = $this->resolveViewerId($request);
            if (! $this->canViewProfile($id, $viewerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private',
                ], 403);
            }

            $likes = $this->supabase->select(
                'recipe_likes',
                ['recipe_id', 'created_at'],
                ['user_id' => $id],
                ['order' => 'created_at.desc', 'limit' => $limit]
            );

            $recipeIds = collect($likes)->pluck('recipe_id')->filter()->unique()->values()->all();
            if (empty($recipeIds)) {
                return response()->json(['success' => true, 'data' => []]);
            }

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

                try {
                    $recipes[$index]['likes_count'] = $this->supabase->count('recipe_likes', ['recipe_id' => $recipeId]);
                } catch (Exception $e) {
                    $recipes[$index]['likes_count'] = 0;
                }

                try {
                    $recipes[$index]['is_liked'] = $viewerId ? ! empty($this->supabase->select('recipe_likes', ['id'], [
                        'recipe_id' => $recipeId,
                        'user_id' => $viewerId,
                    ])) : false;
                } catch (Exception $e) {
                    $recipes[$index]['is_liked'] = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $recipes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user profile
     * PUT /api/v1/users/{id}
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|max:50',
            'full_name' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['username', 'full_name', 'bio']);

            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $avatarName = Str::uuid() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = "avatars/{$avatarName}";

                $this->supabase->uploadFile('avatars', $avatarPath,
                    file_get_contents($avatar->getRealPath()),
                    $avatar->getMimeType()
                );

                $data['avatar_url'] = $this->supabase->getPublicUrl('avatars', $avatarPath);
            }

            $user = $this->supabase->update('profiles', $data, ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Follow user
     * POST /api/v1/users/{id}/follow
     */
    public function follow(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'follower_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $followerId = $request->input('follower_id');
            if ($followerId === $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot follow yourself',
                ], 400);
            }

            // Schema table name is 'follows'
            $existing = $this->supabase->select('follows', ['id'], [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already following this user',
                ], 400);
            }

            $this->supabase->insert('follows', [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            $existingNotification = $this->supabase->select('notifications', ['id'], [
                'user_id' => $id,
                'type' => 'new_follower',
                'related_entity_type' => 'profile',
                'related_entity_id' => $followerId,
                'is_read' => false,
            ]);

            if (empty($existingNotification) && $this->settingsService->enabled($id, 'notify_follows')) {
                $profiles = $this->supabase->select('profiles', ['username', 'full_name'], ['id' => $followerId]);
                $actorName = $profiles[0]['username'] ?? $profiles[0]['full_name'] ?? 'Seseorang';

                $this->supabase->insert('notifications', [
                    'user_id' => $id,
                    'type' => 'new_follower',
                    'title' => 'Follower Baru',
                    'message' => "{$actorName} mulai mengikuti Anda!",
                    'related_entity_type' => 'profile',
                    'related_entity_id' => $followerId,
                ]);

                $this->sendPushToUser(
                    $id,
                    'Follower Baru',
                    "{$actorName} mulai mengikuti Anda!",
                    'new_follower',
                    $followerId
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully followed user',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unfollow user
     * POST /api/v1/users/{id}/unfollow
     */
    public function unfollow(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'follower_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $followerId = $request->input('follower_id');

            // Schema table name is 'follows'
            $this->supabase->delete('follows', [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unfollowed user',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
            Log::warning('Failed to send push notification: ' . $e->getMessage());
        }
    }

    private function resolveViewerId(Request $request): ?string
    {
        return $request->input('viewer_id') ?? $request->input('user_id') ?? auth()->id();
    }

    private function canViewProfile(string $targetId, ?string $viewerId): bool
    {
        if ($viewerId === $targetId) {
            return true;
        }

        if ($viewerId && $this->isAdminUser($viewerId)) {
            return true;
        }

        if ($this->settingsService->enabled($targetId, 'profile_public')) {
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
        } catch (Exception $e) {
            return false;
        }
    }

    private function isAdminUser(string $userId): bool
    {
        try {
            $users = $this->supabase->select('profiles', ['role'], ['id' => $userId]);
            return ($users[0]['role'] ?? null) === 'admin';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a user is following another user
     * GET /api/v1/users/{id}/is-following?follower_id={followerId}
     */
    public function isFollowing(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'follower_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $followerId = $request->input('follower_id');

            // Schema table name is 'follows'
            $existing = $this->supabase->select('follows', ['id'], [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_following' => !empty($existing),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user followers
     * GET /api/v1/users/{id}/followers
     */
    public function followers(Request $request, string $id)
    {
        try {
            if (! $this->canViewProfile($id, $this->resolveViewerId($request))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private',
                ], 403);
            }

            // Schema table name is 'follows'; FK hint updated accordingly
            $followers = $this->supabase->select('follows',
                ['*, profiles!follows_follower_id_fkey(*)'],
                ['following_id' => $id]
            );

            $uniqueFollowers = [];
            foreach ($followers as $row) {
                $followerId = $row['follower_id'] ?? null;
                if ($followerId && $followerId !== $id && !isset($uniqueFollowers[$followerId])) {
                    $uniqueFollowers[$followerId] = $row;
                }
            }

            return response()->json([
                'success' => true,
                'data' => array_values($uniqueFollowers),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users that this user is following
     * GET /api/v1/users/{id}/following
     */
    public function following(Request $request, string $id)
    {
        try {
            if (! $this->canViewProfile($id, $this->resolveViewerId($request))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private',
                ], 403);
            }

            // Schema table name is 'follows'; FK hint updated accordingly
            $following = $this->supabase->select('follows',
                ['*, profiles!follows_following_id_fkey(*)'],
                ['follower_id' => $id]
            );

            $uniqueFollowing = [];
            foreach ($following as $row) {
                $followingId = $row['following_id'] ?? null;
                if ($followingId && $followingId !== $id && !isset($uniqueFollowing[$followingId])) {
                    $uniqueFollowing[$followingId] = $row;
                }
            }

            return response()->json([
                'success' => true,
                'data' => array_values($uniqueFollowing),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user recipes
     * GET /api/v1/users/{id}/recipes
     */
    public function recipes(Request $request, string $id)
    {
        try {
            $status = $request->input('status', 'approved');
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);
            if (! $this->canViewProfile($id, $this->resolveViewerId($request))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is private',
                ], 403);
            }

            $recipes = $this->supabase->select('recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['user_id' => $id, 'status' => $status],
                ['order' => 'created_at.desc', 'limit' => $limit, 'offset' => $offset]
            );

            return response()->json([
                'success' => true,
                'data' => $recipes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ban user (admin only)
     * POST /api/v1/users/{id}/ban
     */
    public function ban(Request $request, string $id)
    {
        try {
            $this->supabase->update('profiles',
                [
                    'is_banned'     => true,
                    'banned_reason' => $request->input('reason'),
                    'banned_at'     => date('Y-m-d H:i:s'),
                    'banned_by'     => $request->input('banned_by'),
                ],
                ['id' => $id],
                true
            );

            return response()->json([
                'success' => true,
                'message' => 'User banned successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unban user (admin only)
     * POST /api/v1/users/{id}/unban
     */
    public function unban(string $id)
    {
        try {
            $this->supabase->update('profiles',
                [
                    'is_banned'     => false,
                    'banned_reason' => null,
                    'banned_at'     => null,
                    'banned_by'     => null,
                ],
                ['id' => $id],
                true
            );

            return response()->json([
                'success' => true,
                'message' => 'User unbanned successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle premium status (admin only)
     * POST /api/v1/users/{id}/toggle-premium
     */
    public function togglePremium(string $id)
    {
        try {
            $users = $this->supabase->select('profiles', ['id', 'is_premium', 'username'], ['id' => $id]);
            if (empty($users)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            $user = $users[0];
            $newStatus = !($user['is_premium'] ?? false);
            $this->supabase->update('profiles',
                ['is_premium' => $newStatus],
                ['id' => $id],
                true
            );
            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'Premium granted successfully' : 'Premium removed successfully',
                'data' => [
                    'user_id' => $id,
                    'is_premium' => $newStatus,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
