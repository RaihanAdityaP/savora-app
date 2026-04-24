<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\RecipeStatus;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $searchQuery = trim((string) $request->input('search', ''));
            $ingredientKeywords = $this->parseIngredientKeywords($request->input('ingredients'));
            $minCalories = $request->input('min_calories');
            $maxCalories = $request->input('max_calories');
            $localMaxCalories = null;

            // Status filter (approved, pending, rejected, or all)
            $status = $request->input('status');
            if ($status === null || $status === '') {
                $filters['status'] = RecipeStatus::APPROVED->value;
            } elseif ($status !== 'all') {
                $filters['status'] = $status;
            }

            if ($request->filled('category_id')) {
                $filters['category_id'] = $request->input('category_id');
            }

            if ($request->filled('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }

            if ($request->filled('difficulty')) {
                $filters['difficulty'] = $request->input('difficulty');
            }

            if ($minCalories !== null && $minCalories !== '') {
                $filters['calories'] = [
                    'operator' => 'gte',
                    'value'    => (int) $minCalories,
                ];
            }

            if ($maxCalories !== null && $maxCalories !== '') {
                $existingCaloriesFilter = $filters['calories'] ?? null;
                if (is_array($existingCaloriesFilter)) {
                    $localMaxCalories = (int) $maxCalories;
                } else {
                    $filters['calories'] = [
                        'operator' => 'lte',
                        'value'    => (int) $maxCalories,
                    ];
                }
            }

            if ($request->filled('tag_id')) {
                $taggedRecipeRows = $this->supabase->select(
                    'recipe_tags',
                    ['recipe_id'],
                    ['tag_id' => (int) $request->input('tag_id')]
                );

                $taggedRecipeIds = array_values(array_unique(array_column($taggedRecipeRows, 'recipe_id')));

                if (empty($taggedRecipeIds)) {
                    return response()->json([
                        'success'    => true,
                        'data'       => [],
                        'pagination' => [
                            'limit'  => (int) $request->input('limit', 10),
                            'offset' => (int) $request->input('offset', 0),
                        ],
                    ]);
                }

                $filters['id'] = [
                    'operator' => 'in',
                    'values'   => $taggedRecipeIds,
                ];
            }

            $limit  = (int) $request->input('limit', 10);
            $offset = (int) $request->input('offset', 0);

            if ($searchQuery === '') {
                $options['limit']  = $limit;
                $options['offset'] = $offset;
            }

            $allowedOrderBy = ['created_at', 'updated_at', 'views_count', 'title', 'calories', 'cooking_time'];
            $orderBy = $request->input('order_by', 'created_at');
            if (!in_array($orderBy, $allowedOrderBy, true)) {
                $orderBy = 'created_at';
            }

            $orderDirection = strtolower($request->input('order_direction', 'desc'));
            if (!in_array($orderDirection, ['asc', 'desc'], true)) {
                $orderDirection = 'desc';
            }

            $options['order'] = "{$orderBy}.{$orderDirection}";

            $columns = ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'];
            try {
                $recipes = $this->supabase->select('recipes', $columns, $filters, $options);
            } catch (Exception $e) {
                $recipes = $this->supabase->select('recipes', ['*'], $filters, $options);
            }

            if ($searchQuery !== '') {
                $needle  = strtolower($searchQuery);
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($needle) {
                    $title       = strtolower((string) ($recipe['title']       ?? ''));
                    $description = strtolower((string) ($recipe['description'] ?? ''));
                    $ingredients = strtolower(json_encode($recipe['ingredients'] ?? ''));

                    return str_contains($title, $needle)
                        || str_contains($description, $needle)
                        || str_contains($ingredients, $needle);
                }));

                $recipes = array_slice($recipes, $offset, $limit);
            }

            if ($localMaxCalories !== null) {
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($localMaxCalories) {
                    $calories = $recipe['calories'] ?? null;
                    if ($calories === null || $calories === '') {
                        return false;
                    }
                    return (int) $calories <= $localMaxCalories;
                }));
            }

            if (!empty($ingredientKeywords)) {
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($ingredientKeywords) {
                    $haystack = strtolower($this->flattenIngredientsToText($recipe['ingredients'] ?? []));
                    foreach ($ingredientKeywords as $keyword) {
                        if (!str_contains($haystack, strtolower($keyword))) {
                            return false;
                        }
                    }
                    return true;
                }));
            }

            foreach ($recipes as &$recipe) {
                $totalRatings = 0;
                $avgRating    = 0;

                try {
                    $ratings      = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $recipe['id']]);
                    $totalRatings = count($ratings);

                    if ($totalRatings > 0) {
                        $sum       = array_sum(array_column($ratings, 'rating'));
                        $avgRating = round($sum / $totalRatings, 1);
                    }
                } catch (Exception $e) {
                    // keep default
                }

                $recipe['rating_info'] = [
                    'average' => $avgRating,
                    'total'   => $totalRatings,
                ];
            }

            return response()->json([
                'success'    => true,
                'data'       => $recipes,
                'pagination' => [
                    'limit'  => $limit,
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
     * Get single recipe by ID
     * GET /api/recipes/{id}
     */
    public function show($id)
    {
        try {
            $columns = ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'];
            $recipes = $this->supabase->select('recipes', $columns, ['id' => $id]);

            if (empty($recipes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe not found',
                ], 404);
            }

            $recipe = $recipes[0];

            $ratings      = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $id]);
            $totalRatings = count($ratings);
            $avgRating    = 0;

            if ($totalRatings > 0) {
                $sum       = array_sum(array_column($ratings, 'rating'));
                $avgRating = round($sum / $totalRatings, 1);
            }

            $recipe['rating_info'] = [
                'average' => $avgRating,
                'total'   => $totalRatings,
            ];

            $this->supabase->update('recipes', [
                'views_count' => ($recipe['views_count'] ?? 0) + 1,
            ], ['id' => $id]);

            return response()->json([
                'success' => true,
                'data'    => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new recipe — always stored as 'pending', no auto-moderation.
     * POST /api/recipes
     */
    public function store(Request $request)
    {
        $this->normalizeArrayInput($request, 'ingredients');
        $this->normalizeArrayInput($request, 'steps');
        $this->normalizeArrayInput($request, 'tags', true);

        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|uuid',
            'title'        => 'required|string|max:200',
            'description'  => 'required|string',
            'category_id'  => 'required|integer',
            'cooking_time' => 'nullable|integer',
            'servings'     => 'nullable|integer',
            'difficulty'   => 'nullable|in:mudah,sedang,sulit',
            'calories'     => 'nullable|integer',
            'ingredients'  => 'required|array',
            'steps'        => 'required|array',
            'tags'         => 'nullable|array',
            'tags.*'       => 'nullable',
            'image'        => 'nullable|image|max:5120',
            'video_url'    => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $resolvedTagIds = $this->resolveTagIds(
                $request->input('tags', []),
                $request->input('user_id')
            );

            $data = [
                'user_id'      => $request->input('user_id'),
                'title'        => $request->input('title'),
                'description'  => $request->input('description'),
                'category_id'  => $request->input('category_id'),
                'cooking_time' => $request->input('cooking_time'),
                'servings'     => $request->input('servings'),
                'difficulty'   => $request->input('difficulty', 'sedang'),
                'calories'     => $request->input('calories'),
                'ingredients'  => json_encode($request->input('ingredients')),
                'steps'        => json_encode($request->input('steps')),
                'video_url'    => $request->input('video_url'),
                'status'       => RecipeStatus::PENDING->value,
                'views_count'  => 0,
            ];

            if ($request->hasFile('image')) {
                $image     = $request->file('image');
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $imagePath = "recipes/{$imageName}";

                $this->supabase->uploadFile('recipe-images', $imagePath, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $imagePath);
            }

            $recipe   = $this->supabase->insert('recipes', $data);
            $recipeId = $recipe[0]['id'];

            if (!empty($resolvedTagIds)) {
                foreach ($resolvedTagIds as $tagId) {
                    $this->supabase->insert('recipe_tags', [
                        'recipe_id' => $recipeId,
                        'tag_id'    => $tagId,
                    ]);

                    $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $tagId]);
                    if (!empty($tagData)) {
                        $this->supabase->update('tags', [
                            'usage_count' => ($tagData[0]['usage_count'] ?? 0) + 1,
                        ], ['id' => $tagId]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil dikirim dan menunggu persetujuan admin.',
                'data'    => $recipe[0],
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
            'title'        => 'nullable|string|max:200',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|integer',
            'cooking_time' => 'nullable|integer',
            'servings'     => 'nullable|integer',
            'difficulty'   => 'nullable|in:mudah,sedang,sulit',
            'calories'     => 'nullable|integer',
            'ingredients'  => 'nullable|array',
            'steps'        => 'nullable|array',
            'tags'         => 'nullable|array',
            'tags.*'       => 'nullable',
            'image'        => 'nullable|image|max:5120',
            'video_url'    => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['title', 'description', 'category_id', 'cooking_time', 'servings', 'difficulty', 'calories', 'video_url']);

            if ($request->has('ingredients')) {
                $data['ingredients'] = json_encode($request->input('ingredients'));
            }

            if ($request->has('steps')) {
                $data['steps'] = json_encode($request->input('steps'));
            }

            if ($request->hasFile('image')) {
                $image     = $request->file('image');
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $imagePath = "recipes/{$imageName}";

                $this->supabase->uploadFile('recipe-images', $imagePath, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $imagePath);
            }

            $recipe = $this->supabase->update('recipes', $data, ['id' => $id]);

            if ($request->has('tags')) {
                $resolvedTagIds = $this->resolveTagIds(
                    $request->input('tags', []),
                    $request->input('user_id')
                );

                $oldTags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $id]);
                foreach ($oldTags as $oldTag) {
                    $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $oldTag['tag_id']]);
                    if (!empty($tagData) && $tagData[0]['usage_count'] > 0) {
                        $this->supabase->update('tags', [
                            'usage_count' => $tagData[0]['usage_count'] - 1,
                        ], ['id' => $oldTag['tag_id']]);
                    }
                }

                $this->supabase->delete('recipe_tags', ['recipe_id' => $id]);

                foreach ($resolvedTagIds as $tagId) {
                    $this->supabase->insert('recipe_tags', [
                        'recipe_id' => $id,
                        'tag_id'    => $tagId,
                    ]);

                    $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $tagId]);
                    if (!empty($tagData)) {
                        $this->supabase->update('tags', [
                            'usage_count' => ($tagData[0]['usage_count'] ?? 0) + 1,
                        ], ['id' => $tagId]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data'    => $recipe,
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
            $recipeTags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $id]);
            foreach ($recipeTags as $recipeTag) {
                $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $recipeTag['tag_id']]);
                if (!empty($tagData) && $tagData[0]['usage_count'] > 0) {
                    $this->supabase->update('tags', [
                        'usage_count' => $tagData[0]['usage_count'] - 1,
                    ], ['id' => $recipeTag['tag_id']]);
                }
            }

            $this->supabase->delete('recipe_tags',        ['recipe_id' => $id]);
            $this->supabase->delete('board_recipes',      ['recipe_id' => $id]);
            $this->supabase->delete('collection_recipes', ['recipe_id' => $id]);
            $this->supabase->delete('recipe_ratings',     ['recipe_id' => $id]);
            $this->supabase->delete('comments',           ['recipe_id' => $id]);
            $this->supabase->delete('menu_plans',         ['recipe_id' => $id]);
            $this->supabase->delete('recipes',            ['id'        => $id]);

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
     * Approve recipe — admin only (enforced via middleware + role check)
     * POST /api/recipes/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        try {
            $moderatorId = Auth::id();
            if (!$moderatorId) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            if (!$this->isAdminUser($moderatorId)) {
                return response()->json(['success' => false, 'message' => 'Forbidden: admin access required'], 403);
            }

            $recipe = $this->supabase->update('recipes', [
                'status'       => RecipeStatus::APPROVED->value,
                'moderated_by' => $moderatorId,
                'moderated_at' => now()->toDateTimeString(),
            ], ['id' => $id], true);

            $recipeData = $this->supabase->select('recipes', ['user_id', 'title'], ['id' => $id]);
            if (!empty($recipeData)) {
                $userId = $recipeData[0]['user_id'];
                $title  = $recipeData[0]['title'];

                $existing = $this->supabase->select('notifications', ['id'], [
                    'user_id'             => $userId,
                    'type'                => 'recipe_approved',
                    'related_entity_type' => 'recipe',
                    'related_entity_id'   => $id,
                    'is_read'             => false,
                ]);

                if (empty($existing)) {
                    $this->supabase->insert('notifications', [
                        'user_id'             => $userId,
                        'type'                => 'recipe_approved',
                        'title'               => 'Resep Disetujui',
                        'message'             => "Resep '{$title}' Anda telah disetujui dan dipublikasikan!",
                        'related_entity_type' => 'recipe',
                        'related_entity_id'   => $id,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe approved successfully',
                'data'    => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject recipe — admin only (enforced via middleware + role check)
     * POST /api/recipes/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        try {
            $reason      = $request->input('reason', 'Tidak memenuhi standar');
            $moderatorId = Auth::id();

            if (!$moderatorId) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            if (!$this->isAdminUser($moderatorId)) {
                return response()->json(['success' => false, 'message' => 'Forbidden: admin access required'], 403);
            }

            $recipe = $this->supabase->update('recipes', [
                'status'           => RecipeStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'moderated_by'     => $moderatorId,
                'moderated_at'     => now()->toDateTimeString(),
            ], ['id' => $id], true);

            $recipeData = $this->supabase->select('recipes', ['user_id', 'title'], ['id' => $id]);
            if (!empty($recipeData)) {
                $userId = $recipeData[0]['user_id'];
                $title  = $recipeData[0]['title'];

                $this->supabase->insert('notifications', [
                    'user_id'             => $userId,
                    'type'                => 'recipe_rejected',
                    'title'               => 'Resep Ditolak',
                    'message'             => "Resep '{$title}' ditolak. Alasan: {$reason}",
                    'related_entity_type' => 'recipe',
                    'related_entity_id'   => $id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recipe rejected',
                'data'    => $recipe,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search recipes
     * GET /api/recipes/search
     */
    public function search(Request $request)
    {
        try {
            $query              = trim((string) $request->input('q', ''));
            $categoryId         = $request->input('category_id');
            $difficulty         = $request->input('difficulty');
            $minCalories        = $request->input('min_calories');
            $maxCalories        = $request->input('max_calories');
            $ingredientKeywords = $this->parseIngredientKeywords($request->input('ingredients'));
            $limit              = (int) $request->input('limit', 20);
            $offset             = (int) $request->input('offset', 0);
            $localMaxCalories   = null;

            $filters = ['status' => RecipeStatus::APPROVED->value];

            if ($categoryId) $filters['category_id'] = $categoryId;
            if ($difficulty)  $filters['difficulty']  = $difficulty;

            if ($minCalories !== null && $minCalories !== '') {
                $filters['calories'] = ['operator' => 'gte', 'value' => (int) $minCalories];
            }

            if ($maxCalories !== null && $maxCalories !== '') {
                if (isset($filters['calories'])) {
                    $localMaxCalories = (int) $maxCalories;
                } else {
                    $filters['calories'] = ['operator' => 'lte', 'value' => (int) $maxCalories];
                }
            }

            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                $filters,
                ['limit' => $limit, 'offset' => $offset, 'order' => 'views_count.desc']
            );

            if ($query !== '') {
                $needle  = strtolower($query);
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($needle) {
                    $title       = strtolower((string) ($recipe['title']       ?? ''));
                    $description = strtolower((string) ($recipe['description'] ?? ''));
                    return str_contains($title, $needle) || str_contains($description, $needle);
                }));
            }

            if ($localMaxCalories !== null) {
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($localMaxCalories) {
                    $calories = $recipe['calories'] ?? null;
                    if ($calories === null || $calories === '') return false;
                    return (int) $calories <= $localMaxCalories;
                }));
            }

            if (!empty($ingredientKeywords)) {
                $recipes = array_values(array_filter($recipes, function ($recipe) use ($ingredientKeywords) {
                    $haystack = strtolower($this->flattenIngredientsToText($recipe['ingredients'] ?? []));
                    foreach ($ingredientKeywords as $keyword) {
                        if (!str_contains($haystack, strtolower($keyword))) return false;
                    }
                    return true;
                }));
            }

            foreach ($recipes as &$recipe) {
                $ratings      = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $recipe['id']]);
                $totalRatings = count($ratings);
                $avgRating    = 0;

                if ($totalRatings > 0) {
                    $sum       = array_sum(array_column($ratings, 'rating'));
                    $avgRating = round($sum / $totalRatings, 1);
                }

                $recipe['rating_info'] = ['average' => $avgRating, 'total' => $totalRatings];
            }

            return response()->json([
                'success'    => true,
                'data'       => $recipes,
                'query'      => $query,
                'filters'    => [
                    'category_id' => $categoryId,
                    'difficulty'  => $difficulty,
                    'min_calories'=> $minCalories,
                    'max_calories'=> $maxCalories,
                    'ingredients' => $ingredientKeywords,
                ],
                'pagination' => ['limit' => $limit, 'offset' => $offset],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── PRIVATE HELPERS ──────────────────────────────────────────────────────

    private function parseIngredientKeywords($rawIngredients): array
    {
        if ($rawIngredients === null || $rawIngredients === '') return [];

        $ingredients = is_array($rawIngredients)
            ? $rawIngredients
            : explode(',', (string) $rawIngredients);

        return array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), $ingredients),
            fn ($item) => $item !== ''
        ));
    }

    private function flattenIngredientsToText($ingredients): string
    {
        if (is_string($ingredients)) {
            $decoded = json_decode($ingredients, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ingredients = $decoded;
            } else {
                return $ingredients;
            }
        }

        if (is_array($ingredients)) {
            return implode(' ', array_map(function ($item) {
                if (is_array($item)) {
                    return implode(' ', array_map(fn ($nested) => (string) $nested, $item));
                }
                return (string) $item;
            }, $ingredients));
        }

        return (string) $ingredients;
    }

    private function normalizeArrayInput(Request $request, string $field, bool $castToInt = false): void
    {
        if (!$request->has($field)) return;

        $value = $request->input($field);
        if (is_array($value)) return;

        $parsed = null;
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                $parsed = [];
            } else {
                $decoded = json_decode($trimmed, true);
                $parsed  = is_array($decoded)
                    ? $decoded
                    : array_map('trim', explode(',', $trimmed));
            }
        }

        if (!is_array($parsed)) return;

        if ($castToInt) {
            $parsed = array_values(array_filter(
                array_map(fn ($item) => is_numeric($item) ? (int) $item : null, $parsed),
                fn ($item) => $item !== null
            ));
        }

        $request->merge([$field => $parsed]);
    }

    private function resolveTagIds(array $rawTags, ?string $createdBy): array
    {
        $resolvedTagIds = [];

        foreach ($rawTags as $rawTag) {
            if ($rawTag === null) continue;

            if (is_numeric($rawTag)) {
                $resolvedTagIds[] = (int) $rawTag;
                continue;
            }

            $tagName = trim((string) $rawTag);
            if ($tagName === '') continue;

            $slug = Str::slug($tagName);
            if ($slug === '') continue;

            $existing = $this->supabase->select('tags', ['id'], ['slug' => $slug]);
            if (!empty($existing)) {
                $resolvedTagIds[] = (int) $existing[0]['id'];
                continue;
            }

            $inserted = $this->supabase->insert('tags', [
                'name'        => $tagName,
                'slug'        => $slug,
                'created_by'  => $createdBy,
                'is_approved' => true,
                'usage_count' => 0,
            ]);

            if (!empty($inserted) && isset($inserted[0]['id'])) {
                $resolvedTagIds[] = (int) $inserted[0]['id'];
            }
        }

        return array_values(array_unique($resolvedTagIds));
    }

    private function isAdminUser(string $userId): bool
    {
        $profiles = $this->supabase->select('profiles', ['role'], ['id' => $userId]);
        if (empty($profiles)) return false;
        return ($profiles[0]['role'] ?? null) === 'admin';
    }

    /**
     * Increment recipe views explicitly
     * POST /api/recipes/{id}/view
     */
    public function incrementView($id)
    {
        try {
            $recipes = $this->supabase->select('recipes', ['id', 'views_count'], ['id' => $id]);

            if (empty($recipes)) {
                return response()->json(['success' => false, 'message' => 'Recipe not found'], 404);
            }

            $this->supabase->update('recipes', [
                'views_count' => (int) ($recipes[0]['views_count'] ?? 0) + 1,
            ], ['id' => $id]);

            return response()->json(['success' => true, 'message' => 'Recipe view incremented']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tags attached to a recipe
     * GET /api/recipes/{id}/tags
     */
    public function getRecipeTags($id)
    {
        try {
            $recipeTags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $id]);

            if (empty($recipeTags)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $tagIds = array_values(array_unique(array_column($recipeTags, 'tag_id')));
            $tags   = [];

            foreach ($tagIds as $tagId) {
                $tag = $this->supabase->select('tags', ['id', 'name', 'slug'], ['id' => $tagId]);
                if (!empty($tag)) $tags[] = $tag[0];
            }

            return response()->json(['success' => true, 'data' => $tags]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}