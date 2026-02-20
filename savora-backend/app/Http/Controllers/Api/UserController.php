<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class UserController extends Controller
{
    private $supabase;
    private $notification;

    public function __construct(SupabaseService $supabase, NotificationService $notification)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
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
    public function show($id)
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

            $followers = $this->supabase->select('followers', ['id'], ['following_id' => $id]);
            $user['followers_count'] = count($followers);

            $following = $this->supabase->select('followers', ['id'], ['follower_id' => $id]);
            $user['following_count'] = count($following);

            $recipes = $this->supabase->select('recipes', ['id'], ['user_id' => $id, 'status' => 'approved']);
            $user['recipes_count'] = count($recipes);

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
     * Update user profile
     * PUT /api/v1/users/{id}
     */
    public function update(Request $request, $id)
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
    public function follow(Request $request, $id)
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

            $existing = $this->supabase->select('followers', ['id'], [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already following this user',
                ], 400);
            }

            $this->supabase->insert('followers', [
                'follower_id' => $followerId,
                'following_id' => $id,
            ]);

            $this->supabase->insert('notifications', [
                'user_id' => $id,
                'type' => 'new_follower',
                'title' => 'Follower Baru',
                'message' => 'Seseorang mulai mengikuti Anda!',
                'related_entity_id' => $followerId,
            ]);

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
    public function unfollow(Request $request, $id)
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

            $this->supabase->delete('followers', [
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

    /**
     * Check if a user is following another user
     * GET /api/v1/users/{id}/is-following?follower_id={followerId}
     */
    public function isFollowing(Request $request, $id)
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

            $existing = $this->supabase->select('followers', ['id'], [
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
    public function followers($id)
    {
        try {
            $followers = $this->supabase->select('followers',
                ['*, profiles!followers_follower_id_fkey(*)'],
                ['following_id' => $id]
            );

            return response()->json([
                'success' => true,
                'data' => $followers,
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
    public function following($id)
    {
        try {
            $following = $this->supabase->select('followers',
                ['*, profiles!followers_following_id_fkey(*)'],
                ['follower_id' => $id]
            );

            return response()->json([
                'success' => true,
                'data' => $following,
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
    public function recipes(Request $request, $id)
    {
        try {
            $status = $request->input('status', 'approved');
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $recipes = $this->supabase->select('recipes',
                ['*', 'categories(*)', 'recipe_tags(tags(*))'],
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
    public function ban($id)
    {
        try {
            $this->supabase->update('profiles',
                ['is_banned' => true],
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
    public function unban($id)
    {
        try {
            $this->supabase->update('profiles',
                ['is_banned' => false],
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
    public function togglePremium($id)
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