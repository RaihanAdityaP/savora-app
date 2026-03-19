<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Exception;

class FeedController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * GET /api/v1/feed?limit=10&offset=0
     */
    public function index(Request $request)
    {
        $limit  = max(1, min(50, (int) $request->input('limit', 10)));
        $offset = max(0, (int) $request->input('offset', 0));

        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);
            $scored   = $this->buildScoredFeed($userId);
            $total    = count($scored);
            $pageIds  = array_slice(array_keys($scored), $offset, $limit);

            if (empty($pageIds)) {
                return response()->json([
                    'success'    => true,
                    'data'       => [],
                    'pagination' => [
                        'limit'    => $limit,
                        'offset'   => $offset,
                        'total'    => $total,
                        'has_more' => false,
                    ],
                ]);
            }

            $recipes = $this->fetchRecipesByIds($pageIds);

            return response()->json([
                'success'    => true,
                'data'       => $recipes,
                'pagination' => [
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'total'    => $total,
                    'has_more' => ($offset + $limit) < $total,
                ],
            ]);

        } catch (Exception $e) {
            // Fallback: popular recipes tanpa personalisasi
            return $this->fallbackFeed($limit, $offset);
        }
    }

    // ─────────────────────────────────────────────────────────
    // SCORING ENGINE
    // ─────────────────────────────────────────────────────────

    private function buildScoredFeed(string $userId): array
    {
        $scored = []; // [recipe_id => float score]

        // ── Sinyal 1: Following (bobot 4) ──────────────────
        // Resep dari orang yang diikuti user
        $this->applyFollowingSignal($userId, $scored, 4);

        // ── Sinyal 2: Riwayat Views (bobot 3) ──────────────
        // Kategori dari resep yang pernah di-view (proxy via views_count tinggi
        // pada resep user sendiri + resep yang di-komentar)
        $this->applyViewHistorySignal($userId, $scored, 3);

        // ── Sinyal 3: Favorit / Saved (bobot 3) ─────────────
        // Kategori & tag dari resep yang disimpan user
        $this->applyFavoriteSignal($userId, $scored, 3);

        // ── Sinyal 4: Followers (bobot 2) ───────────────────
        // Resep populer dari orang yang mengikuti user
        // (social proof: kalau followers suka, mungkin kamu juga suka)
        $this->applyFollowersSignal($userId, $scored, 2);

        // ── Sinyal 5: Popularitas global (bobot 1) ──────────
        // Padding + fallback
        $this->applyPopularitySignal($scored, 1);

        // ── Random injection ~15% ────────────────────────────
        // Selipkan beberapa resep acak agar feed tidak terlalu predictable
        $this->injectRandom($scored, 0.15);

        // Sort by score descending
        arsort($scored);

        // Hilangkan resep milik user sendiri dari feed
        $this->removeOwnRecipes($userId, $scored);

        return $scored;
    }

    // ── Sinyal 1: Following ──────────────────────────────────
    private function applyFollowingSignal(string $userId, array &$scored, float $weight): void
    {
        try {
            $following = $this->supabase->select(
                'follows', ['following_id'], ['follower_id' => $userId]
            );
            if (empty($following)) return;

            $followingIds = array_unique(array_column($following, 'following_id'));

            foreach ($followingIds as $fId) {
                $recipes = $this->supabase->select(
                    'recipes', ['id', 'created_at'],
                    ['user_id' => $fId, 'status' => 'approved'],
                    ['order' => 'created_at.desc', 'limit' => 30]
                );
                foreach ($recipes as $r) {
                    // Resep baru dapat bonus kecil (recency boost)
                    $daysDiff = max(0, now()->diffInDays($r['created_at'] ?? now()));
                    $recency  = max(0.5, 1 - ($daysDiff / 30)); // 1.0 → 0.5 selama 30 hari
                    $scored[$r['id']] = ($scored[$r['id']] ?? 0) + ($weight * $recency);
                }
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Sinyal 2: Riwayat Views (proxy via komentar + rating) ──
    private function applyViewHistorySignal(string $userId, array &$scored, float $weight): void
    {
        try {
            // Ambil kategori dari resep yang pernah di-rating user
            $ratings = $this->supabase->select(
                'recipe_ratings', ['recipe_id'],
                ['user_id' => $userId],
                ['limit' => 50]
            );

            // Ambil kategori dari resep yang pernah di-komentari user
            $comments = $this->supabase->select(
                'comments', ['recipe_id'],
                ['user_id' => $userId],
                ['limit' => 50]
            );

            $interactedIds = array_unique(array_merge(
                array_column($ratings,  'recipe_id'),
                array_column($comments, 'recipe_id'),
            ));

            if (empty($interactedIds)) return;

            // Fetch kategori & tag dari resep yang diinteraksi
            $categoryCount = [];
            $tagCount      = [];

            foreach (array_slice($interactedIds, 0, 20) as $rId) {
                $r = $this->supabase->select('recipes', ['category_id'], ['id' => $rId]);
                if (!empty($r) && $r[0]['category_id']) {
                    $catId = $r[0]['category_id'];
                    $categoryCount[$catId] = ($categoryCount[$catId] ?? 0) + 1;
                }
                $tags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $rId]);
                foreach ($tags as $t) {
                    $tagId = $t['tag_id'];
                    $tagCount[$tagId] = ($tagCount[$tagId] ?? 0) + 1;
                }
            }

            // Rekomendasikan resep dari kategori & tag yang sering diinteraksi
            arsort($categoryCount);
            foreach (array_slice(array_keys($categoryCount), 0, 5) as $catId) {
                $multiplier = min(3, $categoryCount[$catId]); // max 3x boost
                $catRecipes = $this->supabase->select(
                    'recipes', ['id'],
                    ['category_id' => $catId, 'status' => 'approved'],
                    ['order' => 'views_count.desc', 'limit' => 20]
                );
                foreach ($catRecipes as $r) {
                    $scored[$r['id']] = ($scored[$r['id']] ?? 0) + ($weight * $multiplier);
                }
            }

            arsort($tagCount);
            foreach (array_slice(array_keys($tagCount), 0, 8) as $tagId) {
                $tagRecipes = $this->supabase->select(
                    'recipe_tags', ['recipe_id'], ['tag_id' => $tagId]
                );
                foreach ($tagRecipes as $tr) {
                    $scored[$tr['recipe_id']] = ($scored[$tr['recipe_id']] ?? 0) + ($weight * 0.5);
                }
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Sinyal 3: Favorit / Saved ────────────────────────────
    private function applyFavoriteSignal(string $userId, array &$scored, float $weight): void
    {
        try {
            $boards = $this->supabase->select(
                'recipe_boards', ['id'], ['user_id' => $userId]
            );
            if (empty($boards)) return;

            $boardIds = array_column($boards, 'id');
            $savedRecipeIds = [];

            foreach ($boardIds as $bId) {
                $br = $this->supabase->select(
                    'board_recipes', ['recipe_id'],
                    ['board_id' => $bId],
                    ['limit' => 50]
                );
                foreach ($br as $row) {
                    $savedRecipeIds[] = $row['recipe_id'];
                }
            }

            if (empty($savedRecipeIds)) return;

            $savedRecipeIds = array_unique($savedRecipeIds);
            $categoryCount  = [];
            $tagCount       = [];

            foreach (array_slice($savedRecipeIds, 0, 30) as $rId) {
                $r = $this->supabase->select('recipes', ['category_id'], ['id' => $rId]);
                if (!empty($r) && $r[0]['category_id']) {
                    $catId = $r[0]['category_id'];
                    $categoryCount[$catId] = ($categoryCount[$catId] ?? 0) + 1;
                }
                $tags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $rId]);
                foreach ($tags as $t) {
                    $tagId = $t['tag_id'];
                    $tagCount[$tagId] = ($tagCount[$tagId] ?? 0) + 1;
                }
            }

            // Resep dari kategori favorit
            arsort($categoryCount);
            foreach (array_slice(array_keys($categoryCount), 0, 6) as $catId) {
                $multiplier = min(3, $categoryCount[$catId]);
                $catRecipes = $this->supabase->select(
                    'recipes', ['id'],
                    ['category_id' => $catId, 'status' => 'approved'],
                    ['order' => 'views_count.desc', 'limit' => 25]
                );
                foreach ($catRecipes as $r) {
                    // Jangan rekomendasikan yang sudah disimpan
                    if (!in_array($r['id'], $savedRecipeIds)) {
                        $scored[$r['id']] = ($scored[$r['id']] ?? 0) + ($weight * $multiplier);
                    }
                }
            }

            // Resep dari tag favorit
            arsort($tagCount);
            foreach (array_slice(array_keys($tagCount), 0, 10) as $tagId) {
                $tagRecipes = $this->supabase->select(
                    'recipe_tags', ['recipe_id'], ['tag_id' => $tagId]
                );
                foreach ($tagRecipes as $tr) {
                    if (!in_array($tr['recipe_id'], $savedRecipeIds)) {
                        $scored[$tr['recipe_id']] = ($scored[$tr['recipe_id']] ?? 0) + ($weight * 0.5);
                    }
                }
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Sinyal 4: Followers (social proof) ──────────────────
    private function applyFollowersSignal(string $userId, array &$scored, float $weight): void
    {
        try {
            $followers = $this->supabase->select(
                'follows', ['follower_id'], ['following_id' => $userId]
            );
            if (empty($followers)) return;

            $followerIds = array_unique(array_column($followers, 'follower_id'));

            // Lihat apa yang di-save oleh followers (social proof)
            foreach (array_slice($followerIds, 0, 20) as $fId) {
                $boards = $this->supabase->select(
                    'recipe_boards', ['id'], ['user_id' => $fId]
                );
                foreach ($boards as $board) {
                    $br = $this->supabase->select(
                        'board_recipes', ['recipe_id'],
                        ['board_id' => $board['id']],
                        ['limit' => 10]
                    );
                    foreach ($br as $row) {
                        $scored[$row['recipe_id']] = ($scored[$row['recipe_id']] ?? 0) + $weight;
                    }
                }
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Sinyal 5: Popularitas global ────────────────────────
    private function applyPopularitySignal(array &$scored, float $weight): void
    {
        try {
            $popular = $this->supabase->select(
                'recipes', ['id', 'views_count'],
                ['status' => 'approved'],
                ['order' => 'views_count.desc', 'limit' => 100]
            );
            $maxViews = max(1, (int) ($popular[0]['views_count'] ?? 1));

            foreach ($popular as $r) {
                // Normalisasi views ke 0.0–1.0
                $normalizedViews = (int) ($r['views_count'] ?? 0) / $maxViews;
                $scored[$r['id']] = ($scored[$r['id']] ?? 0) + ($weight * $normalizedViews);
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Random injection ─────────────────────────────────────
    private function injectRandom(array &$scored, float $ratio): void
    {
        try {
            $randomRecipes = $this->supabase->select(
                'recipes', ['id'],
                ['status' => 'approved'],
                ['limit' => 200]
            );
            if (empty($randomRecipes)) return;

            shuffle($randomRecipes);
            $injectCount = (int) ceil(count($scored) * $ratio);
            $injectCount = max(3, min(15, $injectCount));

            foreach (array_slice($randomRecipes, 0, $injectCount) as $r) {
                if (!isset($scored[$r['id']])) {
                    // Random score: 0.1–0.9 agar tetap di bawah resep bersinyal
                    $scored[$r['id']] = round(mt_rand(10, 90) / 100, 2);
                }
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Remove own recipes ───────────────────────────────────
    private function removeOwnRecipes(string $userId, array &$scored): void
    {
        try {
            $own = $this->supabase->select(
                'recipes', ['id'], ['user_id' => $userId]
            );
            foreach ($own as $r) {
                unset($scored[$r['id']]);
            }
        } catch (Exception $e) { /* skip */ }
    }

    // ── Fetch full recipe data ───────────────────────────────
    private function fetchRecipesByIds(array $ids): array
    {
        if (empty($ids)) return [];

        try {
            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['id' => ['operator' => 'in', 'values' => $ids]],
                ['limit' => count($ids)]
            );

            // Reorder sesuai urutan scoring
            $map     = array_column($recipes, null, 'id');
            $ordered = [];
            foreach ($ids as $id) {
                if (isset($map[$id])) {
                    $recipe = $map[$id];
                    // Rating info
                    try {
                        $ratings = $this->supabase->select(
                            'recipe_ratings', ['rating'], ['recipe_id' => $id]
                        );
                        $total   = count($ratings);
                        $avg     = $total > 0
                            ? round(array_sum(array_column($ratings, 'rating')) / $total, 1)
                            : 0;
                        $recipe['rating_info'] = ['average' => $avg, 'total' => $total];
                    } catch (Exception $e) {
                        $recipe['rating_info'] = ['average' => 0, 'total' => 0];
                    }
                    $ordered[] = $recipe;
                }
            }
            return $ordered;
        } catch (Exception $e) {
            return [];
        }
    }

    // ── Fallback ─────────────────────────────────────────────
    private function fallbackFeed(int $limit, int $offset): \Illuminate\Http\JsonResponse
    {
        try {
            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['status' => 'approved'],
                ['order' => 'views_count.desc', 'limit' => $limit, 'offset' => $offset]
            );
            return response()->json([
                'success'    => true,
                'data'       => $recipes,
                'pagination' => [
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'total'    => null,
                    'has_more' => count($recipes) === $limit,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}