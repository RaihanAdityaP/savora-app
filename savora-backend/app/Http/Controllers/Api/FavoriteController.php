<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class FavoriteController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get user's favorite recipes
     * GET /api/v1/favorites/user/{userId}
     */
    public function getUserFavorites($userId)
    {
        try {
            $favorites = $this->supabase->select('favorites',
                ['*, recipes(*, profiles(*), categories(*))'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc']
            );

            return response()->json([
                'success' => true,
                'data' => $favorites,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add recipe to favorites
     * POST /api/v1/favorites
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'recipe_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if already favorited
            $existing = $this->supabase->select('favorites', ['id'], [
                'user_id' => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe already in favorites',
                ], 400);
            }

            $favorite = $this->supabase->insert('favorites', [
                'user_id' => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipe added to favorites',
                'data' => $favorite[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove recipe from favorites
     * DELETE /api/v1/favorites
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'recipe_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->supabase->delete('favorites', [
                'user_id' => $request->input('user_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipe removed from favorites',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's recipe boards
     * GET /api/v1/favorites/boards/{userId}
     */
    public function getBoards($userId)
    {
        try {
            $boards = $this->supabase->select('recipe_boards',
                ['*'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc']
            );

            // Add recipe count for each board
            foreach ($boards as &$board) {
                $recipes = $this->supabase->select('board_recipes',
                    ['id'],
                    ['board_id' => $board['id']]
                );
                $board['recipe_count'] = count($recipes);
            }

            return response()->json([
                'success' => true,
                'data' => $boards,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new recipe board
     * POST /api/v1/favorites/boards
     */
    public function createBoard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $board = $this->supabase->insert('recipe_boards', [
                'user_id' => $request->input('user_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Board created successfully',
                'data' => $board[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update recipe board
     * PUT /api/v1/favorites/boards/{boardId}
     */
    public function updateBoard(Request $request, $boardId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['name', 'description']);
            $board = $this->supabase->update('recipe_boards', $data, ['id' => $boardId]);

            return response()->json([
                'success' => true,
                'message' => 'Board updated successfully',
                'data' => $board,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete recipe board
     * DELETE /api/v1/favorites/boards/{boardId}
     */
    public function deleteBoard($boardId)
    {
        try {
            // Delete all board_recipes first
            $this->supabase->delete('board_recipes', ['board_id' => $boardId]);
            
            // Delete the board
            $this->supabase->delete('recipe_boards', ['id' => $boardId]);

            return response()->json([
                'success' => true,
                'message' => 'Board deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recipes in a board
     * GET /api/v1/favorites/boards/{boardId}/recipes
     */
    public function getBoardRecipes($boardId)
    {
        try {
            $boardRecipes = $this->supabase->select('board_recipes',
                ['*, recipes(*, profiles(*), categories(*))'],
                ['board_id' => $boardId],
                ['order' => 'created_at.desc']
            );

            return response()->json([
                'success' => true,
                'data' => $boardRecipes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add recipe to board
     * POST /api/v1/favorites/boards/{boardId}/recipes
     */
    public function addRecipeToBoard(Request $request, $boardId)
    {
        $validator = Validator::make($request->all(), [
            'recipe_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if already in board
            $existing = $this->supabase->select('board_recipes', ['id'], [
                'board_id' => $boardId,
                'recipe_id' => $request->input('recipe_id'),
            ]);

            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe already in this board',
                ], 400);
            }

            $boardRecipe = $this->supabase->insert('board_recipes', [
                'board_id' => $boardId,
                'recipe_id' => $request->input('recipe_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipe added to board',
                'data' => $boardRecipe[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove recipe from board
     * DELETE /api/v1/favorites/boards/{boardId}/recipes/{recipeId}
     */
    public function removeRecipeFromBoard($boardId, $recipeId)
    {
        try {
            $this->supabase->delete('board_recipes', [
                'board_id' => $boardId,
                'recipe_id' => $recipeId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipe removed from board',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}