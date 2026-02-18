<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class RatingController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get recipe ratings
     * GET /api/v1/ratings/recipe/{recipeId}
     */
    public function getRecipeRatings($recipeId)
    {
        try {
            $ratings = $this->supabase->select('ratings',
                ['*, profiles(id, username, avatar_url)'],
                ['recipe_id' => $recipeId],
                ['order' => 'created_at.desc']
            );

            // Calculate average rating
            $totalRating = 0;
            $count = count($ratings);
            
            foreach ($ratings as $rating) {
                $totalRating += $rating['rating'];
            }
            
            $averageRating = $count > 0 ? round($totalRating / $count, 1) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'ratings' => $ratings,
                    'average_rating' => $averageRating,
                    'total_ratings' => $count,
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
     * Get recipe average rating
     * GET /api/v1/ratings/recipe/{recipeId}/average
     */
    public function getAverageRating($recipeId)
    {
        try {
            $ratings = $this->supabase->select('ratings',
                ['rating'],
                ['recipe_id' => $recipeId]
            );

            $totalRating = 0;
            $count = count($ratings);
            
            foreach ($ratings as $rating) {
                $totalRating += $rating['rating'];
            }
            
            $averageRating = $count > 0 ? round($totalRating / $count, 1) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'average_rating' => $averageRating,
                    'total_ratings' => $count,
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
     * Add or update rating
     * POST /api/v1/ratings
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'recipe_id' => 'required|uuid',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if user already rated this recipe
            $existing = $this->supabase->select('ratings', ['id'], [
                'user_id' => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            if (!empty($existing)) {
                // Update existing rating
                $rating = $this->supabase->update('ratings', [
                    'rating' => $request->input('rating'),
                    'comment' => $request->input('comment'),
                ], [
                    'user_id' => $request->input('user_id'),
                    'recipe_id' => $request->input('recipe_id'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Rating updated successfully',
                    'data' => $rating,
                ]);
            } else {
                // Create new rating
                $rating = $this->supabase->insert('ratings', [
                    'user_id' => $request->input('user_id'),
                    'recipe_id' => $request->input('recipe_id'),
                    'rating' => $request->input('rating'),
                    'comment' => $request->input('comment'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Rating added successfully',
                    'data' => $rating[0],
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update rating
     * PUT /api/v1/ratings/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['rating', 'comment']);
            $rating = $this->supabase->update('ratings', $data, ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Rating updated successfully',
                'data' => $rating,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete rating
     * DELETE /api/v1/ratings/{id}
     */
    public function destroy($id)
    {
        try {
            $this->supabase->delete('ratings', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Rating deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's rating for a recipe
     * GET /api/v1/ratings/user/{userId}/recipe/{recipeId}
     */
    public function getUserRecipeRating($userId, $recipeId)
    {
        try {
            $ratings = $this->supabase->select('ratings', ['*'], [
                'user_id' => $userId,
                'recipe_id' => $recipeId,
            ]);

            if (empty($ratings)) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $ratings[0],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's all ratings
     * GET /api/v1/ratings/user/{userId}
     */
    public function getUserRatings($userId)
    {
        try {
            $ratings = $this->supabase->select('ratings',
                ['*, recipes(id, title, image_url)'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc']
            );

            return response()->json([
                'success' => true,
                'data' => $ratings,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rating statistics for a recipe
     * GET /api/v1/ratings/recipe/{recipeId}/stats
     */
    public function getRecipeRatingStats($recipeId)
    {
        try {
            $ratings = $this->supabase->select('ratings',
                ['rating'],
                ['recipe_id' => $recipeId]
            );

            $stats = [
                'total' => count($ratings),
                'average' => 0,
                'distribution' => [
                    '5' => 0,
                    '4' => 0,
                    '3' => 0,
                    '2' => 0,
                    '1' => 0,
                ],
            ];

            if (count($ratings) > 0) {
                $totalRating = 0;
                foreach ($ratings as $rating) {
                    $totalRating += $rating['rating'];
                    $stats['distribution'][(string)$rating['rating']]++;
                }
                $stats['average'] = round($totalRating / count($ratings), 1);
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}