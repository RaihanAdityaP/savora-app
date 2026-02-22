<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CategoryController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get all categories
     * GET /api/v1/categories
     */
    public function index()
    {
        try {
            $categories = $this->supabase->select('categories',
                ['*'],
                [],
                ['order' => 'name.asc']
            );

            // Add recipe count for each category
            foreach ($categories as &$category) {
                $recipes = $this->supabase->select('recipes',
                    ['id'],
                    ['category_id' => $category['id'], 'status' => 'approved']
                );
                $category['recipe_count'] = count($recipes);
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single category
     * GET /api/v1/categories/{id}
     */
    public function show($id)
    {
        try {
            $categories = $this->supabase->select('categories', ['*'], ['id' => $id]);

            if (empty($categories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            $category = $categories[0];

            // Get recipes in this category
            $recipes = $this->supabase->select('recipes',
                ['*', 'profiles(*)'],
                ['category_id' => $id, 'status' => 'approved'],
                ['order' => 'created_at.desc', 'limit' => 10]
            );
            $category['recipes'] = $recipes;
            $category['recipe_count'] = count($recipes);

            return response()->json([
                'success' => true,
                'data' => $category,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new category (admin only)
     * POST /api/v1/categories
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            // 'description' removed — not a column in the categories schema
            'icon_url' => 'nullable|string|max:500', // schema column is icon_url
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = $this->supabase->insert('categories', [
                'name'     => $request->input('name'),
                'icon_url' => $request->input('icon_url'), // was 'icon' — fixed to match schema
            ], true);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update category (admin only)
     * PUT /api/v1/categories/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:100',
            // 'description' removed — not a column in the categories schema
            'icon_url' => 'nullable|string|max:500', // schema column is icon_url
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Only pass columns that actually exist in the schema
            $data = $request->only(['name', 'icon_url']); // was ['name', 'description', 'icon'] — fixed

            $category = $this->supabase->update('categories', $data, ['id' => $id], true);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete category (admin only)
     * DELETE /api/v1/categories/{id}
     */
    public function destroy($id)
    {
        try {
            // Check if category has recipes
            $recipes = $this->supabase->select('recipes', ['id'], ['category_id' => $id]);

            if (!empty($recipes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing recipes',
                ], 400);
            }

            $this->supabase->delete('categories', ['id' => $id], true);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recipes by category
     * GET /api/v1/categories/{id}/recipes
     */
    public function recipes(Request $request, $id)
    {
        try {
            $limit          = $request->input('limit', 10);
            $offset         = $request->input('offset', 0);

            // Guard order params against invalid values (prevents PostgREST 500)
            $allowedOrderBy = ['created_at', 'updated_at', 'views_count', 'title'];
            $orderBy        = $request->input('order_by', 'created_at');
            if (!in_array($orderBy, $allowedOrderBy, true)) {
                $orderBy = 'created_at';
            }

            $orderDirection = strtolower($request->input('order_direction', 'desc'));
            if (!in_array($orderDirection, ['asc', 'desc'], true)) {
                $orderDirection = 'desc';
            }

            $recipes = $this->supabase->select('recipes',
                ['*', 'profiles(*)', 'recipe_tags(tags(*))'],
                ['category_id' => $id, 'status' => 'approved'],
                [
                    'order'  => "{$orderBy}.{$orderDirection}",
                    'limit'  => $limit,
                    'offset' => $offset,
                ]
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
}