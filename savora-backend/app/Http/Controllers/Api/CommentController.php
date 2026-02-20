<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CommentController extends Controller
{
    private $supabase;
    private $notification;

    public function __construct(SupabaseService $supabase, NotificationService $notification)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
    }

    /**
     * Get comments for a recipe
     * GET /api/comments/recipe/{recipeId}
     */
    public function getRecipeComments(Request $request, $recipeId)
    {
        try {
            $limit = $request->input('limit', 20);
            $offset = $request->input('offset', 0);
            $orderDirection = $request->input('order', 'desc'); // desc = newest first

            $comments = $this->supabase->select('comments',
                ['*', 'profiles(id, username, full_name, avatar_url)'],
                ['recipe_id' => $recipeId],
                [
                    'order' => "created_at.{$orderDirection}",
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $comments,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
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
     * Get comments by user
     * GET /api/comments/user/{userId}
     */
    public function getUserComments(Request $request, $userId)
    {
        try {
            $limit = $request->input('limit', 20);
            $offset = $request->input('offset', 0);

            $comments = $this->supabase->select('comments',
                ['*', 'recipes(id, title, image_url)', 'profiles(id, username, avatar_url)'],
                ['user_id' => $userId],
                [
                    'order' => 'created_at.desc',
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $comments,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single comment
     * GET /api/comments/{id}
     */
    public function show($id)
    {
        try {
            $comments = $this->supabase->select('comments',
                ['*', 'profiles(id, username, full_name, avatar_url)', 'recipes(id, title)'],
                ['id' => $id]
            );

            if (empty($comments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $comments[0],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create comment
     * POST /api/comments
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipe_id' => 'required|uuid',
            'user_id' => 'required|uuid',
            'content' => 'required|string|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $comment = $this->supabase->insert('comments', [
                'recipe_id' => $request->input('recipe_id'),
                'user_id' => $request->input('user_id'),
                'content' => $request->input('content'),
            ]);

            // Get recipe owner to send notification
            $recipeData = $this->supabase->select('recipes', 
                ['user_id', 'title'], 
                ['id' => $request->input('recipe_id')]
            );

            if (!empty($recipeData)) {
                $recipeOwnerId = $recipeData[0]['user_id'];
                $recipeTitle = $recipeData[0]['title'];
                
                // Don't notify if user comments on their own recipe
                if ($recipeOwnerId !== $request->input('user_id')) {
                    // Get commenter name
                    $commenterData = $this->supabase->select('profiles',
                        ['username', 'full_name'],
                        ['id' => $request->input('user_id')]
                    );

                    $commenterName = !empty($commenterData) 
                        ? ($commenterData[0]['full_name'] ?? $commenterData[0]['username'])
                        : 'Seseorang';

                    // Insert notification
                    $this->supabase->insert('notifications', [
                        'user_id' => $recipeOwnerId,
                        'type' => 'new_comment',
                        'title' => 'Komentar Baru',
                        'message' => "{$commenterName} berkomentar di resep '{$recipeTitle}'",
                        'related_entity_type' => 'recipe',
                        'related_entity_id' => $request->input('recipe_id'),
                    ]);

                    // Send push notification if device tokens exist
                    $deviceTokens = $this->supabase->select('device_tokens',
                        ['token'],
                        ['user_id' => $recipeOwnerId, 'is_active' => true]
                    );

                    if (!empty($deviceTokens)) {
                        $tokens = array_column($deviceTokens, 'token');
                        $payload = [
                            'route' => 'recipe',
                            'id' => $request->input('recipe_id'),
                        ];

                        try {
                            $this->notification->sendToMultipleDevices(
                                $tokens,
                                'Komentar Baru',
                                "{$commenterName} berkomentar di resep Anda",
                                $payload
                            );
                        } catch (Exception $e) {
                            // Log but don't fail the comment creation
                            \Log::warning('Failed to send push notification: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Get full comment data with relations
            $fullComment = $this->supabase->select('comments',
                ['*', 'profiles(id, username, full_name, avatar_url)'],
                ['id' => $comment[0]['id']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Comment posted successfully',
                'data' => !empty($fullComment) ? $fullComment[0] : $comment[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update comment
     * PUT /api/comments/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:1|max:1000',
            'user_id' => 'required|uuid', // To verify ownership
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Verify ownership
            $existing = $this->supabase->select('comments', 
                ['user_id'], 
                ['id' => $id]
            );

            if (empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            if ($existing[0]['user_id'] !== $request->input('user_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this comment',
                ], 403);
            }

            $comment = $this->supabase->update('comments', [
                'content' => $request->input('content'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $comment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete comment
     * DELETE /api/comments/{id}
     */
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid', // To verify ownership
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Verify ownership or admin
            $existing = $this->supabase->select('comments', 
                ['user_id'], 
                ['id' => $id]
            );

            if (empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            // Check if user is owner or admin
            $userData = $this->supabase->select('profiles',
                ['role'],
                ['id' => $request->input('user_id')]
            );

            $isOwner = $existing[0]['user_id'] === $request->input('user_id');
            $isAdmin = !empty($userData) && $userData[0]['role'] === 'admin';

            if (!$isOwner && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this comment',
                ], 403);
            }

            $this->supabase->delete('comments', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get comment count for a recipe
     * GET /api/comments/recipe/{recipeId}/count
     */
    public function getCommentCount($recipeId)
    {
        try {
            $comments = $this->supabase->select('comments',
                ['id'],
                ['recipe_id' => $recipeId]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => count($comments),
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