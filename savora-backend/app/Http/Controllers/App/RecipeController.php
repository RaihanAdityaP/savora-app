<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class RecipeController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    // GET /app/recipes/{id}
    public function show(string $id)
    {
        $userId = session('user_id');

        try {
            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(id, username, full_name, avatar_url, role, is_premium)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['id' => $id]
            );

            if (empty($recipes)) {
                abort(404, 'Resep tidak ditemukan.');
            }

            $recipe = $recipes[0];

            // Decode ingredients & steps jika masih string JSON
            foreach (['ingredients', 'steps'] as $field) {
                if (is_string($recipe[$field] ?? null)) {
                    $decoded = json_decode($recipe[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $recipe[$field] = $decoded;
                    }
                }
            }

            // Rating
            $ratings = $this->supabase->select('recipe_ratings', ['*', 'profiles:user_id(username, avatar_url)'], ['recipe_id' => $id]);
            $totalRatings = count($ratings);
            $avgRating    = $totalRatings > 0
                ? round(array_sum(array_column($ratings, 'rating')) / $totalRatings, 1)
                : 0;

            // User's rating
            $userRating = null;
            if ($userId) {
                foreach ($ratings as $r) {
                    if ($r['user_id'] === $userId) {
                        $userRating = $r['rating'];
                        break;
                    }
                }
            }

            // Comments
            $comments = $this->supabase->select(
                'comments',
                ['*', 'profiles:user_id(id, username, avatar_url)'],
                ['recipe_id' => $id],
                ['order' => 'created_at.desc']
            );

            // Tags
            $tags = [];
            foreach ($recipe['recipe_tags'] ?? [] as $rt) {
                if ($rt['tags'] ?? null) $tags[] = $rt['tags'];
            }

            // Favorit check
            $isFavorite = false;
            if ($userId) {
                try {
                    $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
                    foreach ($boards as $board) {
                        $check = $this->supabase->select('board_recipes', ['id'], ['board_id' => $board['id'], 'recipe_id' => $id]);
                        if (! empty($check)) { $isFavorite = true; break; }
                    }
                } catch (Exception) {}
            }

            // Increment views
            try {
                $this->supabase->update('recipes', ['views_count' => ($recipe['views_count'] ?? 0) + 1], ['id' => $id]);
            } catch (Exception) {}

            $currentUserRole = session('user_role', 'user');

            return view('app.recipe-detail', compact(
                'recipe', 'ratings', 'avgRating', 'totalRatings',
                'userRating', 'comments', 'tags', 'isFavorite',
                'userId', 'currentUserRole'
            ));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // GET /app/recipes/create
    public function create()
    {
        $categories = [];
        $popularTags = [];
        try {
            $categories  = $this->supabase->select('categories', ['id', 'name'], [], ['order' => 'name.asc']);
            $popularTags = $this->supabase->select('tags', ['id', 'name', 'slug'], ['is_approved' => true], ['order' => 'usage_count.desc', 'limit' => 20]);
        } catch (Exception) {}

        return view('app.recipe-create', compact('categories', 'popularTags'));
    }

    // POST /app/recipes
    public function store(Request $request)
    {
        $request->validate([
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
            'image'        => 'nullable|image|max:5120',
        ]);

        try {
            $userId = session('user_id');

            $data = [
                'user_id'      => $userId,
                'title'        => $request->input('title'),
                'description'  => $request->input('description'),
                'category_id'  => (int) $request->input('category_id'),
                'cooking_time' => $request->input('cooking_time') ? (int) $request->input('cooking_time') : null,
                'servings'     => $request->input('servings') ? (int) $request->input('servings') : null,
                'difficulty'   => $request->input('difficulty', 'mudah'),
                'calories'     => $request->input('calories') ? (int) $request->input('calories') : null,
                'ingredients'  => json_encode($request->input('ingredients')),
                'steps'        => json_encode($request->input('steps')),
                'status'       => 'pending',
                'views_count'  => 0,
            ];

            if ($request->hasFile('image')) {
                $image    = $request->file('image');
                $path     = 'recipes/' . Str::uuid() . '.' . $image->getClientOriginalExtension();
                $this->supabase->uploadFile('recipe-images', $path, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $path);
            }

            $recipe   = $this->supabase->insert('recipes', $data);
            $recipeId = $recipe[0]['id'];

            // Tags
            $tagIds = array_filter(array_map('intval', $request->input('tags', [])));
            foreach ($tagIds as $tagId) {
                try {
                    $this->supabase->insert('recipe_tags', ['recipe_id' => $recipeId, 'tag_id' => $tagId]);
                    $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $tagId]);
                    if (! empty($tagData)) {
                        $this->supabase->update('tags', ['usage_count' => ($tagData[0]['usage_count'] ?? 0) + 1], ['id' => $tagId]);
                    }
                } catch (Exception) {}
            }

            return redirect()->route('app.home')
                ->with('status', 'Resep berhasil dikirim dan menunggu persetujuan admin.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat resep: ' . $e->getMessage());
        }
    }

    // GET /app/recipes/{id}/edit
    public function edit(string $id)
    {
        $userId = session('user_id');

        try {
            $recipes = $this->supabase->select('recipes', ['*', 'recipe_tags(tags(id, name))'], ['id' => $id]);
            if (empty($recipes)) abort(404);

            $recipe = $recipes[0];

            // Hanya pemilik atau admin yang bisa edit
            if ($recipe['user_id'] !== $userId && session('user_role') !== 'admin') {
                abort(403, 'Tidak diizinkan.');
            }

            foreach (['ingredients', 'steps'] as $field) {
                if (is_string($recipe[$field] ?? null)) {
                    $decoded = json_decode($recipe[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) $recipe[$field] = $decoded;
                }
            }

            $tags = array_map(fn($rt) => $rt['tags'], array_filter($recipe['recipe_tags'] ?? [], fn($rt) => $rt['tags'] ?? null));

            $categories  = $this->supabase->select('categories', ['id', 'name'], [], ['order' => 'name.asc']);
            $popularTags = $this->supabase->select('tags', ['id', 'name', 'slug'], ['is_approved' => true], ['order' => 'usage_count.desc', 'limit' => 20]);

            return view('app.recipe-edit', compact('recipe', 'tags', 'categories', 'popularTags'));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // PUT /app/recipes/{id}
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'required|string',
            'category_id'  => 'required|integer',
            'cooking_time' => 'nullable|integer',
            'servings'     => 'nullable|integer',
            'difficulty'   => 'nullable|in:mudah,sedang,sulit',
            'calories'     => 'nullable|integer',
            'ingredients'  => 'required|array',
            'steps'        => 'required|array',
            'image'        => 'nullable|image|max:5120',
        ]);

        try {
            $userId = session('user_id');
            $existing = $this->supabase->select('recipes', ['user_id'], ['id' => $id]);
            if (empty($existing)) abort(404);
            if ($existing[0]['user_id'] !== $userId && session('user_role') !== 'admin') abort(403);

            $data = [
                'title'        => $request->input('title'),
                'description'  => $request->input('description'),
                'category_id'  => (int) $request->input('category_id'),
                'cooking_time' => $request->input('cooking_time') ? (int) $request->input('cooking_time') : null,
                'servings'     => $request->input('servings') ? (int) $request->input('servings') : null,
                'difficulty'   => $request->input('difficulty', 'mudah'),
                'calories'     => $request->input('calories') ? (int) $request->input('calories') : null,
                'ingredients'  => json_encode($request->input('ingredients')),
                'steps'        => json_encode($request->input('steps')),
            ];

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $path  = 'recipes/' . Str::uuid() . '.' . $image->getClientOriginalExtension();
                $this->supabase->uploadFile('recipe-images', $path, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $path);
            }

            $this->supabase->update('recipes', $data, ['id' => $id]);

            // Update tags
            if ($request->has('tags')) {
                $this->supabase->delete('recipe_tags', ['recipe_id' => $id]);
                foreach (array_filter(array_map('intval', $request->input('tags', []))) as $tagId) {
                    try {
                        $this->supabase->insert('recipe_tags', ['recipe_id' => $id, 'tag_id' => $tagId]);
                    } catch (Exception) {}
                }
            }

            return redirect()->route('app.recipe.show', $id)
                ->with('status', 'Resep berhasil diperbarui.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    // DELETE /app/recipes/{id}
    public function destroy(string $id)
    {
        try {
            $userId   = session('user_id');
            $existing = $this->supabase->select('recipes', ['user_id'], ['id' => $id]);
            if (empty($existing)) abort(404);
            if ($existing[0]['user_id'] !== $userId && session('user_role') !== 'admin') abort(403);

            $this->supabase->delete('recipe_tags',    ['recipe_id' => $id]);
            $this->supabase->delete('board_recipes',  ['recipe_id' => $id]);
            $this->supabase->delete('recipe_ratings', ['recipe_id' => $id]);
            $this->supabase->delete('comments',       ['recipe_id' => $id]);
            $this->supabase->delete('recipes',        ['id'        => $id]);

            return redirect()->route('app.home')
                ->with('status', 'Resep berhasil dihapus.');

        } catch (Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // POST /app/recipes/{id}/comment
    public function postComment(Request $request, string $id)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        try {
            $userId = session('user_id');
            $this->supabase->insert('comments', [
                'recipe_id' => $id,
                'user_id'   => $userId,
                'content'   => $request->input('content'),
            ]);

            return back()->with('status', 'Komentar berhasil dikirim.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // DELETE /app/comments/{commentId}
    public function deleteComment(string $commentId)
    {
        try {
            $userId   = session('user_id');
            $existing = $this->supabase->select('comments', ['user_id'], ['id' => $commentId]);
            if (empty($existing)) abort(404);
            if ($existing[0]['user_id'] !== $userId && session('user_role') !== 'admin') abort(403);

            $this->supabase->delete('comments', ['id' => $commentId]);
            return back()->with('status', 'Komentar dihapus.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/recipes/{id}/rate
    public function rate(Request $request, string $id)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5']);

        try {
            $userId   = session('user_id');
            $existing = $this->supabase->select('recipe_ratings', ['id'], ['user_id' => $userId, 'recipe_id' => $id]);

            if (! empty($existing)) {
                $this->supabase->update('recipe_ratings', ['rating' => (int) $request->input('rating')], ['user_id' => $userId, 'recipe_id' => $id]);
            } else {
                $this->supabase->insert('recipe_ratings', ['user_id' => $userId, 'recipe_id' => $id, 'rating' => (int) $request->input('rating')]);
            }

            return back()->with('status', 'Rating berhasil dikirim.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}