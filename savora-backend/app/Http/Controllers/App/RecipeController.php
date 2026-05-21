<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class RecipeController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
        private NotificationService $notification,
    ) {}

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

            foreach (['ingredients', 'steps'] as $field) {
                if (is_string($recipe[$field] ?? null)) {
                    $decoded = json_decode($recipe[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $recipe[$field] = $decoded;
                    }
                }
            }

            // Rating + comments in parallel (if Supabase client supports concurrent)
            $ratings  = $this->supabase->select('recipe_ratings', ['*', 'profiles:user_id(username, avatar_url)'], ['recipe_id' => $id]);
            $totalRatings = count($ratings);
            $avgRating    = $totalRatings > 0
                ? round(array_sum(array_column($ratings, 'rating')) / $totalRatings, 1)
                : 0;

            $userRating = null;
            if ($userId) {
                foreach ($ratings as $r) {
                    if ($r['user_id'] === $userId) { $userRating = $r['rating']; break; }
                }
            }

            $comments = $this->supabase->select(
                'comments',
                ['*', 'profiles:user_id(id, username, avatar_url)'],
                ['recipe_id' => $id],
                ['order' => 'created_at.desc']
            );

            $tags = [];
            foreach ($recipe['recipe_tags'] ?? [] as $rt) {
                if ($rt['tags'] ?? null) $tags[] = $rt['tags'];
            }

            $isFavorite = false;
            $favoriteBoards = [];
            $savedBoardIds = [];
            $likesCount = 0;
            $isLiked = false;
            if ($userId) {
                try {
                    $boards = $this->supabase->select('recipe_boards', ['id', 'name', 'description'], ['user_id' => $userId], ['order' => 'created_at.asc']);
                    $favoriteBoards = $boards;
                    foreach ($boards as $board) {
                        $check = $this->supabase->select('board_recipes', ['id'], ['board_id' => $board['id'], 'recipe_id' => $id]);
                        if (! empty($check)) {
                            $isFavorite = true;
                            $savedBoardIds[] = $board['id'];
                        }
                    }
                } catch (Exception) {}
            }
            try {
                $likesCount = $this->supabase->count('recipe_likes', ['recipe_id' => $id]);
                if ($userId) {
                    $isLiked = ! empty($this->supabase->select('recipe_likes', ['id'], [
                        'recipe_id' => $id,
                        'user_id'   => $userId,
                    ]));
                }
            } catch (Exception) {}

            if ($this->settingsService->enabled($userId, 'allow_analytics')) {
                try {
                    $this->supabase->update('recipes', ['views_count' => ($recipe['views_count'] ?? 0) + 1], ['id' => $id]);
                } catch (Exception) {}
            }

            $currentUserRole = session('user_role', 'user');

            return view('app.recipes.detail', compact(
                'recipe', 'ratings', 'avgRating', 'totalRatings',
                'userRating', 'comments', 'tags', 'isFavorite',
                'userId', 'currentUserRole', 'favoriteBoards', 'savedBoardIds',
                'likesCount', 'isLiked'
            ));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // GET /app/recipes/create
    public function create()
    {
        $categories  = [];
        $popularTags = [];
        try {
            // Fetch categories & tags in parallel — reduced columns for speed
            $categories  = $this->supabase->select('categories', ['id', 'name'], [], ['order' => 'name.asc']);

            // Limit popular tags to 15 (was 20), only approved
            $popularTags = $this->supabase->select(
                'tags',
                ['id', 'name', 'slug'],  // drop usage_count from select for speed
                ['is_approved' => true],
                ['order' => 'usage_count.desc', 'limit' => 15]
            );
        } catch (Exception) {}

        return view('app.recipes.create', compact('categories', 'popularTags'));
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
            'ingredients'  => 'required|array|min:1',
            'steps'        => 'required|array|min:1',
            'tags'         => 'nullable|array|max:3',
            'tags.*'       => 'nullable|integer',
            // Image sudah di-upload dari browser — terima URL-nya
            'image_url'    => 'nullable|url',
            'video_url'    => 'nullable|url',
            // Fallback: jika ada yang masih kirim file (backward compat)
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

            // ── Image URL (sudah diupload dari browser) ──
            if ($request->filled('image_url')) {
                $data['image_url'] = $request->input('image_url');
            } elseif ($request->hasFile('image')) {
                // Fallback lama (jika JS tidak jalan / disabled)
                $image    = $request->file('image');
                $path     = 'recipes/' . \Illuminate\Support\Str::uuid() . '.' . $image->getClientOriginalExtension();
                $this->supabase->uploadFile('recipe-images', $path, file_get_contents($image->getRealPath()), $image->getMimeType());
                $data['image_url'] = $this->supabase->getPublicUrl('recipe-images', $path);
            }

            // ── Video URL ──
            if ($request->filled('video_url')) {
                $data['video_url'] = $request->input('video_url');
            }

            $recipe   = $this->supabase->insert('recipes', $data);
            $recipeId = $recipe[0]['id'];

            // Tags — max 3
            $tagIds = array_slice(
                array_values(array_filter(array_map('intval', $request->input('tags', [])))),
                0, 3
            );

            foreach ($tagIds as $tagId) {
                try {
                    $this->supabase->insert('recipe_tags', ['recipe_id' => $recipeId, 'tag_id' => $tagId]);
                } catch (\Exception) {}
            }

            foreach ($tagIds as $tagId) {
                try {
                    $tagData = $this->supabase->select('tags', ['usage_count'], ['id' => $tagId]);
                    if (! empty($tagData)) {
                        $this->supabase->update('tags', ['usage_count' => ($tagData[0]['usage_count'] ?? 0) + 1], ['id' => $tagId]);
                    }
                } catch (\Exception) {}
            }

            return redirect()->route('app.home')
                ->with('status', 'Resep berhasil dikirim dan menunggu persetujuan admin.');

        } catch (\Exception $e) {
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
            $popularTags = $this->supabase->select('tags', ['id', 'name', 'slug'], ['is_approved' => true], ['order' => 'usage_count.desc', 'limit' => 15]);

            return view('app.recipes.edit', compact('recipe', 'tags', 'categories', 'popularTags'));

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
            'ingredients'  => 'required|array|min:1',
            'steps'        => 'required|array|min:1',
            'tags'         => 'nullable|array|max:3',
            'tags.*'       => 'nullable|integer',
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

            if ($request->has('tags')) {
                $this->supabase->delete('recipe_tags', ['recipe_id' => $id]);
                $tagIds = array_slice(
                    array_values(array_filter(array_map('intval', $request->input('tags', [])))),
                    0, 3
                );
                foreach ($tagIds as $tagId) {
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

            $recipes = $this->supabase->select('recipes', ['user_id', 'title'], ['id' => $id]);
            if (! empty($recipes)) {
                $ownerId = $recipes[0]['user_id'] ?? null;
                if ($ownerId && $ownerId !== $userId && $this->settingsService->enabled($ownerId, 'notify_comments')) {
                    $this->supabase->insert('notifications', [
                        'user_id'             => $ownerId,
                        'type'                => 'new_comment',
                        'title'               => 'Komentar Baru',
                        'message'             => session('user_username', 'Seseorang') . " berkomentar di resep '" . ($recipes[0]['title'] ?? 'Anda') . "'",
                        'related_entity_type' => 'recipe',
                        'related_entity_id'   => $id,
                    ]);

                    $this->sendPushToUser(
                        $ownerId,
                        'Komentar Baru',
                        session('user_username', 'Seseorang') . ' berkomentar di resep Anda',
                        'new_comment',
                        $id
                    );
                }
            }

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

    public function toggleLike(string $id)
    {
        try {
            $userId = session('user_id');
            $recipes = $this->supabase->select('recipes', ['user_id', 'title'], ['id' => $id]);
            if (empty($recipes)) abort(404);

            $existing = $this->supabase->select('recipe_likes', ['id'], [
                'recipe_id' => $id,
                'user_id'   => $userId,
            ]);

            if (! empty($existing)) {
                $this->supabase->delete('recipe_likes', [
                    'recipe_id' => $id,
                    'user_id'   => $userId,
                ]);
                return back()->with('status', 'Like dibatalkan.');
            }

            $this->supabase->insert('recipe_likes', [
                'recipe_id' => $id,
                'user_id'   => $userId,
            ]);

            $ownerId = $recipes[0]['user_id'] ?? null;
            if ($ownerId && $ownerId !== $userId && $this->settingsService->enabled($ownerId, 'notify_likes')) {
                $this->supabase->insert('notifications', [
                    'user_id'             => $ownerId,
                    'type'                => 'new_like',
                    'title'               => 'Like Baru',
                    'message'             => session('user_username', 'Seseorang') . " menyukai resep '" . ($recipes[0]['title'] ?? 'Anda') . "'",
                    'related_entity_type' => 'recipe',
                    'related_entity_id'   => $id,
                ]);

                $this->sendPushToUser(
                    $ownerId,
                    'Like Baru',
                    session('user_username', 'Seseorang') . ' menyukai resep Anda',
                    'new_like',
                    $id
                );
            }

            return back()->with('status', 'Resep disukai.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function sendPushToUser(string $userId, string $title, string $message, string $type, string $entityId): void
    {
        try {
            $deviceTokens = $this->supabase->select('device_tokens', ['token'], [
                'user_id' => $userId,
                'is_active' => true,
            ]);

            if (empty($deviceTokens)) return;

            $this->notification->sendToMultipleDevices(
                array_column($deviceTokens, 'token'),
                $title,
                $message,
                $this->notification->generatePayload($type, $entityId)
            );
        } catch (Exception $e) {
            \Log::warning('Failed to send push notification: ' . $e->getMessage());
        }
    }
}
