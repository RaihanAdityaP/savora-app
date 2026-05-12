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
            $ratings = $this->supabase->select('recipe_ratings',
                ['*, profiles(id, username, avatar_url)'],
                ['recipe_id' => $recipeId],
                ['order' => 'created_at.desc']
            );

            $totalRating = array_sum(array_column($ratings, 'rating'));
            $count       = count($ratings);
            $averageRating = $count > 0 ? round($totalRating / $count, 1) : 0;

            return response()->json([
                'success' => true,
                'data'    => [
                    'ratings'        => $ratings,
                    'average_rating' => $averageRating,
                    'total_ratings'  => $count,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recipe average rating
     * GET /api/v1/ratings/recipe/{recipeId}/average
     */
    public function getAverageRating($recipeId)
    {
        try {
            $ratings  = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $recipeId]);
            $count    = count($ratings);
            $average  = $count > 0 ? round(array_sum(array_column($ratings, 'rating')) / $count, 1) : 0;

            return response()->json([
                'success' => true,
                'data'    => ['average_rating' => $average, 'total_ratings' => $count],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add or update rating
     * POST /api/v1/ratings
     *
     * Schema: recipe_ratings(id, recipe_id, user_id, rating, created_at)
     * NOTE: No 'comment' column exists — removed from insert/update.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|uuid',
            'recipe_id' => 'required|uuid',
            'rating'    => 'required|integer|min:1|max:5',
            // 'comment' intentionally excluded — not in schema
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $existing = $this->supabase->select('recipe_ratings', ['id'], [
                'user_id'   => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            if (!empty($existing)) {
                $rating = $this->supabase->update('recipe_ratings',
                    ['rating' => $request->input('rating')],
                    [
                        'user_id'   => $request->input('user_id'),
                        'recipe_id' => $request->input('recipe_id'),
                    ]
                );

                return response()->json(['success' => true, 'message' => 'Rating updated successfully', 'data' => $rating]);
            }

            $rating = $this->supabase->insert('recipe_ratings', [
                'user_id'   => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
                'rating'    => $request->input('rating'),
            ]);

            return response()->json(['success' => true, 'message' => 'Rating added successfully', 'data' => $rating[0]], 201);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
            // 'comment' intentionally excluded — not in schema
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $data   = $request->only(['rating']);
            $rating = $this->supabase->update('recipe_ratings', $data, ['id' => $id]);

            return response()->json(['success' => true, 'message' => 'Rating updated successfully', 'data' => $rating]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete rating
     * DELETE /api/v1/ratings/{id}
     */
    public function destroy($id)
    {
        try {
            $this->supabase->delete('recipe_ratings', ['id' => $id]);
            return response()->json(['success' => true, 'message' => 'Rating deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's rating for a recipe
     * GET /api/v1/ratings/user/{userId}/recipe/{recipeId}
     */
    public function getUserRecipeRating($userId, $recipeId)
    {
        try {
            $ratings = $this->supabase->select('recipe_ratings', ['*'], [
                'user_id'   => $userId,
                'recipe_id' => $recipeId,
            ]);

            return response()->json(['success' => true, 'data' => $ratings[0] ?? null]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's all ratings
     * GET /api/v1/ratings/user/{userId}
     */
    public function getUserRatings($userId)
    {
        try {
            $ratings = $this->supabase->select('recipe_ratings',
                ['*, recipes(id, title, image_url)'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc']
            );

            return response()->json(['success' => true, 'data' => $ratings]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get rating statistics for a recipe
     * GET /api/v1/ratings/recipe/{recipeId}/stats
     */
    public function getRecipeRatingStats($recipeId)
    {
        try {
            $ratings = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $recipeId]);

            $stats = [
                'total'        => count($ratings),
                'average'      => 0,
                'distribution' => ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0],
            ];

            if ($stats['total'] > 0) {
                $sum = 0;
                foreach ($ratings as $r) {
                    $sum += $r['rating'];
                    $stats['distribution'][(string) $r['rating']]++;
                }
                $stats['average'] = round($sum / $stats['total'], 1);
            }

            return response()->json(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get authenticated user's rating for a recipe
     * GET /api/v1/ratings/recipe/{recipeId}/user
     */
    public function getMyRecipeRating(Request $request, $recipeId)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            return $this->getUserRecipeRating($userId, $recipeId);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}