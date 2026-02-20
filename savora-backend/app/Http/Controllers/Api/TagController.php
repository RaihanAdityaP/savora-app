<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class TagController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get all tags
     * GET /api/tags
     */
    public function index(Request $request)
    {
        try {
            $filters = [];
            $options = [];

            // Filter by approval status
            if ($request->has('is_approved')) {
                $filters['is_approved'] = $request->input('is_approved') === 'true';
            }

            // Filter by featured
            if ($request->has('is_featured')) {
                $filters['is_featured'] = $request->input('is_featured') === 'true';
            }

            // Pagination
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);
            $options['limit'] = $limit;
            $options['offset'] = $offset;

            // Ordering
            $orderBy = $request->input('order_by', 'usage_count');
            $orderDirection = $request->input('order_direction', 'desc');
            $options['order'] = "{$orderBy}.{$orderDirection}";

            $tags = $this->supabase->select('tags', ['*'], $filters, $options);

            return response()->json([
                'success' => true,
                'data' => $tags,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single tag
     * GET /api/tags/{id}
     */
    public function show($id)
    {
        try {
            $tags = $this->supabase->select('tags', ['*'], ['id' => $id]);

            if (empty($tags)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag not found',
                ], 404);
            }

            $tag = $tags[0];

            // Get recipes with this tag
            $recipeCount = count($this->supabase->select('recipe_tags', ['id'], ['tag_id' => $id]));
            $tag['recipe_count'] = $recipeCount;

            return response()->json([
                'success' => true,
                'data' => $tag,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new tag
     * POST /api/tags
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'created_by' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $name = $request->input('name');
            $slug = Str::slug($name);

            // Check if tag already exists
            $existing = $this->supabase->select('tags', ['id'], ['slug' => $slug]);
            
            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag already exists',
                ], 400);
            }

            $tag = $this->supabase->insert('tags', [
                'name' => $name,
                'slug' => $slug,
                'created_by' => $request->input('created_by'),
                'is_approved' => false, // Needs admin approval
                'usage_count' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'data' => $tag[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update tag
     * PUT /api/tags/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:50',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [];
            
            if ($request->has('name')) {
                $data['name'] = $request->input('name');
                $data['slug'] = Str::slug($request->input('name'));
            }
            
            if ($request->has('is_featured')) {
                $data['is_featured'] = $request->input('is_featured');
            }

            $tag = $this->supabase->update('tags', $data, ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'data' => $tag,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete tag
     * DELETE /api/tags/{id}
     */
    public function destroy($id)
    {
        try {
            // Check if tag is in use
            $recipeCount = count($this->supabase->select('recipe_tags', ['id'], ['tag_id' => $id]));
            
            if ($recipeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete tag that is used by {$recipeCount} recipes",
                ], 400);
            }

            $this->supabase->delete('tags', ['id' => $id], true);

            return response()->json([
                'success' => true,
                'message' => 'Tag deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve tag (admin only)
     * POST /api/tags/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approved_by' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tag = $this->supabase->update('tags', [
                'is_approved' => true,
                'approved_by' => $request->input('approved_by'),
                'approved_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id], true);

            return response()->json([
                'success' => true,
                'message' => 'Tag approved successfully',
                'data' => $tag,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search tags
     * GET /api/tags/search
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $limit = $request->input('limit', 20);

            $tags = $this->supabase->select('tags', 
                ['*'], 
                ['is_approved' => true],
                ['order' => 'usage_count.desc', 'limit' => $limit]
            );

            // Filter by search query
            if (!empty($query)) {
                $tags = array_filter($tags, function($tag) use ($query) {
                    return stripos($tag['name'], $query) !== false;
                });
            }

            return response()->json([
                'success' => true,
                'data' => array_values($tags),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get popular tags
     * GET /api/tags/popular
     */
    public function popular(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $tags = $this->supabase->select('tags', 
                ['*'], 
                ['is_approved' => true],
                ['order' => 'usage_count.desc', 'limit' => $limit]
            );

            return response()->json([
                'success' => true,
                'data' => $tags,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recipes by tag
     * GET /api/tags/{id}/recipes
     */
    public function recipes(Request $request, $id)
    {
        try {
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            // Get recipe IDs with this tag
            $recipeTags = $this->supabase->select('recipe_tags', 
                ['recipe_id'], 
                ['tag_id' => $id]
            );

            $recipeIds = array_column($recipeTags, 'recipe_id');

            if (empty($recipeIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            // Get recipes (note: Supabase doesn't support IN operator directly in this implementation)
            // So we need to fetch and filter
            $allRecipes = $this->supabase->select('recipes',
                ['*', 'profiles(*)', 'categories(*)'],
                ['status' => 'approved'],
                ['order' => 'created_at.desc']
            );

            $recipes = array_filter($allRecipes, function($recipe) use ($recipeIds) {
                return in_array($recipe['id'], $recipeIds);
            });

            // Apply pagination
            $recipes = array_slice(array_values($recipes), $offset, $limit);

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
}