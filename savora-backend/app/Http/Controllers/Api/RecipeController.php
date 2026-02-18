<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class RecipeController extends Controller
{
    private $supabase;
    private $notification;

    public function __construct(SupabaseService $supabase, NotificationService $notification)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
    }

    /**
     * Get all recipes with pagination and filters
     * GET /api/recipes
     */
    public function index(Request $request)
    {
        try {
            $filters = [];
            $options = [];

            // Status filter (approved, pending, rejected)
            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            } else {
                $filters['status'] = 'approved'; // Default only approved
            }

            // Category filter
            if ($request->has('category_id')) {
                $filters['category_id'] = $request->input('category_id');
            }

            // User filter
            if ($request->has('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }

            // Pagination
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);
            $options['limit'] = $limit;
            $options['offset'] = $offset;

            // Ordering
            $orderBy = $request->input('order_by', 'created_at');
            $orderDirection = $request->input('order_direction', 'desc');
            $options['order'] = "{$orderBy}.{$orderDirection}";

            // Select with relationships
            $columns = ['*', 'profiles(*)', 'categories(*)', 'recipe_tags(tags(*))'];

            $recipes = $this->supabase->select('recipes', $columns, $filters, $options);

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
     * Get single recipe by ID
     * GET /api/recipes/{id}
     */
    public function show($id)
    {
        try {
            $columns = ['*', 'profiles(*)', 'categories(*)', 'recipe_tags(tags(*))', 'recipe_steps(*)'];
            $recipes = $this->supabase->select('recipes', $columns, ['id' => $id]);

            if (empty($recipes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $recipes[0],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new recipe
     * POST /api/recipes
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'category_id' => 'required|integer',
            'cooking_time' => 'nullable|integer',
            'servings' => 'nullable|integer',
            'difficulty' => 'nullable|in:mudah,sedang,sulit',
            'ingredients' => 'required|array',
            'steps' => 'required|array',
            'tags' => 'nullable|array',
            'image' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [
                'user_id' => $request->input('user_id'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'cooking_time' => $request->input('cooking_time'),
                'servings' => $request->input('servings'),
                'difficulty' => $request->input('difficulty', 'sedang'),
                'ingredients' => $request->input('ingredients'),
                'status' => 'pending', // Default pending approval
            ];

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $imagePath = "recipes/{$imageName}";
                
                $this->supabase->uploadFile('recipe-images', $imagePath, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $imagePath);
            }

            // Insert recipe
            $recipe = $this->supabase->insert('recipes', $data);
            $recipeId = $recipe[0]['id'];

            // Insert steps
            $steps = $request->input('steps');
            foreach ($steps as $index => $step) {
                $this->supabase->insert('recipe_steps', [
                    'recipe_id' => $recipeId,
                    'step_number' => $index + 1,
                    'description' => $step,
                ]);
            }

            // Insert tags if provided
            if ($request->has('tags')) {
                $tags = $request->input('tags');
                foreach ($tags as $tagId) {
                    $this->supabase->insert('recipe_tags', [
                        'recipe_id' => $recipeId,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe created successfully',
                'data' => $recipe[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update recipe
     * PUT /api/recipes/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'cooking_time' => 'nullable|integer',
            'servings' => 'nullable|integer',
            'difficulty' => 'nullable|in:mudah,sedang,sulit',
            'ingredients' => 'nullable|array',
            'steps' => 'nullable|array',
            'tags' => 'nullable|array',
            'image' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['title', 'description', 'category_id', 'cooking_time', 'servings', 'difficulty', 'ingredients']);

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $imagePath = "recipes/{$imageName}";
                
                $this->supabase->uploadFile('recipe-images', $imagePath, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $imagePath);
            }

            // Update recipe
            $recipe = $this->supabase->update('recipes', $data, ['id' => $id]);

            // Update steps if provided
            if ($request->has('steps')) {
                // Delete old steps
                $this->supabase->delete('recipe_steps', ['recipe_id' => $id]);
                
                // Insert new steps
                $steps = $request->input('steps');
                foreach ($steps as $index => $step) {
                    $this->supabase->insert('recipe_steps', [
                        'recipe_id' => $id,
                        'step_number' => $index + 1,
                        'description' => $step,
                    ]);
                }
            }

            // Update tags if provided
            if ($request->has('tags')) {
                // Delete old tags
                $this->supabase->delete('recipe_tags', ['recipe_id' => $id]);
                
                // Insert new tags
                $tags = $request->input('tags');
                foreach ($tags as $tagId) {
                    $this->supabase->insert('recipe_tags', [
                        'recipe_id' => $id,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data' => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete recipe
     * DELETE /api/recipes/{id}
     */
    public function destroy($id)
    {
        try {
            // Delete related data first
            $this->supabase->delete('recipe_steps', ['recipe_id' => $id]);
            $this->supabase->delete('recipe_tags', ['recipe_id' => $id]);
            $this->supabase->delete('favorites', ['recipe_id' => $id]);
            $this->supabase->delete('ratings', ['recipe_id' => $id]);
            
            // Delete recipe
            $this->supabase->delete('recipes', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Recipe deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve recipe (admin only)
     * POST /api/recipes/{id}/approve
     */
    public function approve($id)
    {
        try {
            $recipe = $this->supabase->update('recipes', ['status' => 'approved'], ['id' => $id], true);

            // Send notification to recipe owner
            $recipeData = $this->supabase->select('recipes', ['user_id'], ['id' => $id]);
            if (!empty($recipeData)) {
                $userId = $recipeData[0]['user_id'];
                
                $this->supabase->insert('notifications', [
                    'user_id' => $userId,
                    'type' => 'recipe_approved',
                    'title' => 'Resep Disetujui',
                    'message' => 'Resep Anda telah disetujui dan dipublikasikan!',
                    'related_entity_id' => $id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe approved successfully',
                'data' => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject recipe (admin only)
     * POST /api/recipes/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        try {
            $reason = $request->input('reason', 'Tidak memenuhi standar');
            
            $recipe = $this->supabase->update('recipes', [
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ], ['id' => $id], true);

            // Send notification to recipe owner
            $recipeData = $this->supabase->select('recipes', ['user_id'], ['id' => $id]);
            if (!empty($recipeData)) {
                $userId = $recipeData[0]['user_id'];
                
                $this->supabase->insert('notifications', [
                    'user_id' => $userId,
                    'type' => 'recipe_rejected',
                    'title' => 'Resep Ditolak',
                    'message' => "Resep Anda ditolak. Alasan: $reason",
                    'related_entity_id' => $id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe rejected',
                'data' => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search recipes
     * GET /api/recipes/search
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            
            // For now, we'll use basic filtering
            // In production, you might want to use full-text search
            $recipes = $this->supabase->select('recipes', 
                ['*', 'profiles(*)', 'categories(*)'], 
                ['status' => 'approved']
            );

            // Filter by search query
            if (!empty($query)) {
                $recipes = array_filter($recipes, function($recipe) use ($query) {
                    return stripos($recipe['title'], $query) !== false || 
                           stripos($recipe['description'], $query) !== false;
                });
            }

            return response()->json([
                'success' => true,
                'data' => array_values($recipes),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}