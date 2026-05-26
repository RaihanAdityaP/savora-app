<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Exception;

class FeedController extends Controller
{
    private $supabase;
    private UserSettingsService $settingsService;

    public function __construct(SupabaseService $supabase, UserSettingsService $settingsService)
    {
        $this->supabase = $supabase;
        $this->settingsService = $settingsService;
    }

    /**
     * GET /api/v1/feed?limit=10&offset=0
     */
    public function index(Request $request)
    {
        $limit  = max(1, min(50, (int) $request->input('limit', 10)));
        $offset = max(0, (int) $request->input('offset', 0));
        $poolLimit = $limit * 4;

        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);

            if (! $this->settingsService->enabled($userId, 'allow_analytics')) {
                return $this->fallbackFeed($limit, $offset, $userId);
            }

            $feed = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['status' => 'approved'],
                ['order' => 'created_at.desc', 'limit' => $poolLimit, 'offset' => $offset]
            );
            $rawFeedCount = count($feed);

            if (empty($feed)) {
                return $this->fallbackFeed($limit, $offset, $userId);
            }

            $feed = $this->injectRandomRecipes($feed);
            $feed = $this->attachHomeFeedMetrics($feed, $userId, true);
            usort($feed, fn ($a, $b) => ($b['ranking_score'] ?? 0) <=> ($a['ranking_score'] ?? 0));
            $feed = array_slice($feed, 0, $limit);
            $feed = $this->attachSavedState($feed, $userId);

            return response()->json([
                'success'    => true,
                'feed_mode'  => 'scored',
                'data'       => $feed,
                'pagination' => [
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'total'    => null,
                    'has_more' => $rawFeedCount >= $poolLimit || count($feed) === $limit,
                ],
            ]);

        } catch (Exception $e) {
            // Fallback: popular recipes tanpa personalisasi
            return $this->fallbackFeed($limit, $offset, null);
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
        $this->applyPublicLikeSignal($scored, 4);

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
    private function applyPublicLikeSignal(array &$scored, float $weight): void
    {
        try {
            $likes = $this->supabase->select('recipe_likes', ['recipe_id'], [], ['limit' => 1000]);
            $counts = [];

            foreach ($likes as $like) {
                $recipeId = $like['recipe_id'] ?? null;
                if (! $recipeId) {
                    continue;
                }

                $counts[$recipeId] = ($counts[$recipeId] ?? 0) + 1;
            }

            if (empty($counts)) {
                return;
            }

            $maxLikes = max($counts);
            foreach ($counts as $recipeId => $count) {
                $scored[$recipeId] = ($scored[$recipeId] ?? 0) + ($weight * ($count / max(1, $maxLikes)));
            }
        } catch (Exception $e) { /* skip */ }
    }

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
    private function fetchRecipesByIds(array $ids, ?string $userId): array
    {
        if (empty($ids)) return [];

        try {
            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['id' => ['operator' => 'in', 'values' => $ids]],
                ['limit' => count($ids)]
            );
        } catch (Exception $e) {
            try {
                $recipes = $this->supabase->select(
                    'recipes',
                    ['*'],
                    ['id' => ['operator' => 'in', 'values' => $ids]],
                    ['limit' => count($ids)]
                );
            } catch (Exception $e) {
                return [];
            }
        }

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
        return $this->attachSavedState($this->attachLikes($ordered, $userId), $userId);
    }

    private function attachLikes(array $recipes, ?string $userId): array
    {
        $ids = array_values(array_filter(array_column($recipes, 'id')));
        if (empty($ids)) {
            return $recipes;
        }

        $counts = [];
        $liked = [];

        try {
            $rows = $this->supabase->select(
                'recipe_likes',
                ['recipe_id', 'user_id'],
                ['recipe_id' => ['operator' => 'in', 'values' => $ids]]
            );

            foreach ($rows as $row) {
                $recipeId = $row['recipe_id'] ?? null;
                if (! $recipeId) {
                    continue;
                }

                $counts[$recipeId] = ($counts[$recipeId] ?? 0) + 1;
                if ($userId && ($row['user_id'] ?? null) === $userId) {
                    $liked[$recipeId] = true;
                }
            }
        } catch (Exception $e) { /* skip */ }

        foreach ($recipes as $index => $recipe) {
            $recipeId = $recipe['id'] ?? null;
            $recipes[$index]['likes_count'] = $recipeId ? (int) ($counts[$recipeId] ?? 0) : 0;
            $recipes[$index]['is_liked'] = $recipeId ? ! empty($liked[$recipeId]) : false;
        }

        return $recipes;
    }

    private function attachSavedState(array $recipes, ?string $userId): array
    {
        foreach ($recipes as $index => $recipe) {
            $recipes[$index]['is_saved'] = false;
        }

        if (!$userId || empty($recipes)) {
            return $recipes;
        }

        try {
            $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
            $boardIds = array_values(array_filter(array_column($boards, 'id')));
            if (empty($boardIds)) {
                return $recipes;
            }

            $recipeIds = array_values(array_filter(array_column($recipes, 'id')));
            $rows = $this->supabase->select(
                'board_recipes',
                ['recipe_id'],
                [
                    'board_id' => ['operator' => 'in', 'values' => $boardIds],
                    'recipe_id' => ['operator' => 'in', 'values' => $recipeIds],
                ]
            );
            $saved = array_fill_keys(array_column($rows, 'recipe_id'), true);

            foreach ($recipes as $index => $recipe) {
                $recipeId = $recipe['id'] ?? null;
                $recipes[$index]['is_saved'] = $recipeId ? !empty($saved[$recipeId]) : false;
            }
        } catch (Exception $e) { /* skip */ }

        return $recipes;
    }

    // Metrics and ranking follow the Laravel web home feed.
    private function attachHomeFeedMetrics(array $feed, ?string $userId, bool $withRankingScore): array
    {
        $recipeIds = array_values(array_filter(array_column($feed, 'id')));

        if (empty($recipeIds)) {
            return $feed;
        }

        $signals = $withRankingScore ? $this->buildPersonalSignals($userId) : [];
        $ratingSums = [];
        $ratingCounts = [];
        $likeCounts = [];
        $likedRecipeIds = [];

        try {
            $ratingRows = $this->supabase->select(
                'recipe_ratings',
                ['recipe_id', 'rating'],
                ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
            );

            foreach ($ratingRows as $row) {
                $recipeId = $row['recipe_id'] ?? null;
                if ($recipeId === null) {
                    continue;
                }

                $ratingSums[$recipeId] = ($ratingSums[$recipeId] ?? 0.0) + (float) ($row['rating'] ?? 0);
                $ratingCounts[$recipeId] = ($ratingCounts[$recipeId] ?? 0) + 1;
            }
        } catch (Exception) {
        }

        try {
            $likeRows = $this->supabase->select(
                'recipe_likes',
                ['recipe_id', 'user_id'],
                ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
            );

            foreach ($likeRows as $row) {
                $recipeId = $row['recipe_id'] ?? null;
                if (! $recipeId) {
                    continue;
                }

                $likeCounts[$recipeId] = ($likeCounts[$recipeId] ?? 0) + 1;
                if ($userId && ($row['user_id'] ?? null) === $userId) {
                    $likedRecipeIds[$recipeId] = true;
                }
            }
        } catch (Exception) {
        }

        foreach ($feed as $index => $row) {
            $recipeId = $row['id'] ?? null;
            $ratingCount = $recipeId ? (int) ($ratingCounts[$recipeId] ?? 0) : 0;
            $ratingAvg = $ratingCount > 0
                ? round(($ratingSums[$recipeId] ?? 0) / $ratingCount, 1)
                : null;
            $likes = $recipeId ? (int) ($likeCounts[$recipeId] ?? 0) : 0;

            $feed[$index]['rating_avg'] = $ratingAvg;
            $feed[$index]['rating_count'] = $ratingCount;
            $feed[$index]['rating_info'] = [
                'average' => $ratingAvg ?? 0,
                'total' => $ratingCount,
            ];
            $feed[$index]['likes_count'] = $likes;
            $feed[$index]['is_liked'] = $recipeId ? ! empty($likedRecipeIds[$recipeId]) : false;

            if ($withRankingScore) {
                $ratingScore = $ratingAvg ? ((float) $ratingAvg * 2) : 0;
                $viewsScore = log(((int) ($row['views_count'] ?? 0)) + 1);
                $ageHours = max(1, now()->diffInHours(\Carbon\Carbon::parse($row['created_at'] ?? now())));
                $freshnessScore = 12 / sqrt($ageHours);
                $signalScore = $this->personalSignalScore($row, $signals);
                $randomScore = (float) ($row['_random_score'] ?? 0);
                $feed[$index]['signal_score'] = $signalScore;
                $feed[$index]['random_score'] = $randomScore;
                $feed[$index]['ranking_score'] = ($likes * 4) + $ratingScore + $viewsScore + $freshnessScore + $signalScore + $randomScore;
            }
        }

        return $feed;
    }

    private function injectRandomRecipes(array $feed): array
    {
        try {
            $existing = array_fill_keys(array_filter(array_column($feed, 'id')), true);
            $randomRecipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['status' => 'approved'],
                ['limit' => 200]
            );

            shuffle($randomRecipes);
            $injectCount = max(3, min(15, (int) ceil(count($feed) * 0.15)));

            foreach ($randomRecipes as $recipe) {
                $recipeId = $recipe['id'] ?? null;
                if (! $recipeId || isset($existing[$recipeId])) {
                    continue;
                }

                $recipe['_random_score'] = round(mt_rand(10, 90) / 100, 2);
                $feed[] = $recipe;
                $existing[$recipeId] = true;

                if (--$injectCount <= 0) {
                    break;
                }
            }
        } catch (Exception) {
        }

        return $feed;
    }

    private function buildPersonalSignals(?string $userId): array
    {
        $signals = [
            'following_ids' => [],
            'saved_recipe_ids' => [],
            'commented_recipe_ids' => [],
            'follower_saved_recipe_ids' => [],
            'category_counts' => [],
            'tag_counts' => [],
        ];

        if (! $userId) {
            return $signals;
        }

        try {
            $following = $this->supabase->select('follows', ['following_id'], ['follower_id' => $userId]);
            $signals['following_ids'] = array_fill_keys(array_filter(array_column($following, 'following_id')), true);
        } catch (Exception) {
        }

        try {
            $comments = $this->supabase->select('comments', ['recipe_id'], ['user_id' => $userId], ['limit' => 50]);
            $signals['commented_recipe_ids'] = array_fill_keys(array_filter(array_column($comments, 'recipe_id')), true);
        } catch (Exception) {
        }

        try {
            $ratings = $this->supabase->select('recipe_ratings', ['recipe_id'], ['user_id' => $userId], ['limit' => 50]);
            $interactedIds = array_unique(array_merge(
                array_keys($signals['commented_recipe_ids']),
                array_filter(array_column($ratings, 'recipe_id'))
            ));
            $this->addCategoryAndTagCounts($interactedIds, $signals, 1);
        } catch (Exception) {
        }

        try {
            $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
            $savedRecipeIds = $this->recipeIdsFromBoards(array_column($boards, 'id'), 50);
            $signals['saved_recipe_ids'] = array_fill_keys($savedRecipeIds, true);
            $this->addCategoryAndTagCounts($savedRecipeIds, $signals, 2);
        } catch (Exception) {
        }

        try {
            $followers = $this->supabase->select('follows', ['follower_id'], ['following_id' => $userId]);
            $followerIds = array_slice(array_filter(array_column($followers, 'follower_id')), 0, 20);
            $followerSaved = [];
            foreach ($followerIds as $followerId) {
                $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $followerId]);
                $followerSaved = array_merge($followerSaved, $this->recipeIdsFromBoards(array_column($boards, 'id'), 10));
            }
            $signals['follower_saved_recipe_ids'] = array_fill_keys(array_unique($followerSaved), true);
        } catch (Exception) {
        }

        return $signals;
    }

    private function recipeIdsFromBoards(array $boardIds, int $limit): array
    {
        $recipeIds = [];
        foreach (array_filter($boardIds) as $boardId) {
            $rows = $this->supabase->select('board_recipes', ['recipe_id'], ['board_id' => $boardId], ['limit' => $limit]);
            $recipeIds = array_merge($recipeIds, array_filter(array_column($rows, 'recipe_id')));
        }

        return array_values(array_unique($recipeIds));
    }

    private function addCategoryAndTagCounts(array $recipeIds, array &$signals, int $weight): void
    {
        foreach (array_slice(array_filter($recipeIds), 0, 30) as $recipeId) {
            try {
                $recipes = $this->supabase->select('recipes', ['category_id'], ['id' => $recipeId]);
                $categoryId = $recipes[0]['category_id'] ?? null;
                if ($categoryId) {
                    $signals['category_counts'][$categoryId] = ($signals['category_counts'][$categoryId] ?? 0) + $weight;
                }
            } catch (Exception) {
            }

            try {
                $tags = $this->supabase->select('recipe_tags', ['tag_id'], ['recipe_id' => $recipeId]);
                foreach ($tags as $tag) {
                    $tagId = $tag['tag_id'] ?? null;
                    if ($tagId) {
                        $signals['tag_counts'][$tagId] = ($signals['tag_counts'][$tagId] ?? 0) + $weight;
                    }
                }
            } catch (Exception) {
            }
        }
    }

    private function personalSignalScore(array $recipe, array $signals): float
    {
        $score = 0.0;
        $recipeId = $recipe['id'] ?? null;
        $ownerId = $recipe['user_id'] ?? null;
        $categoryId = $recipe['category_id'] ?? null;

        if ($ownerId && ! empty($signals['following_ids'][$ownerId])) {
            $score += 4;
        }
        if ($recipeId && ! empty($signals['follower_saved_recipe_ids'][$recipeId])) {
            $score += 2;
        }
        if ($recipeId && ! empty($signals['commented_recipe_ids'][$recipeId])) {
            $score += 2;
        }
        if ($recipeId && ! empty($signals['saved_recipe_ids'][$recipeId])) {
            $score += 3;
        }
        if ($categoryId && ! empty($signals['category_counts'][$categoryId])) {
            $score += min(9, $signals['category_counts'][$categoryId] * 3);
        }

        foreach ($this->extractTagIds($recipe) as $tagId) {
            if (! empty($signals['tag_counts'][$tagId])) {
                $score += min(5, $signals['tag_counts'][$tagId] * 0.5);
            }
        }

        return $score;
    }

    private function extractTagIds(array $recipe): array
    {
        $ids = [];
        foreach (($recipe['recipe_tags'] ?? []) as $recipeTag) {
            $tag = $recipeTag['tags'] ?? null;
            $tagId = is_array($tag) ? ($tag['id'] ?? null) : ($recipeTag['tag_id'] ?? null);
            if ($tagId) {
                $ids[] = $tagId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function fallbackFeed(int $limit, int $offset, ?string $userId): \Illuminate\Http\JsonResponse
    {
        try {
            $recipes = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(*)', 'categories(*)', 'recipe_tags(tags(*))'],
                ['status' => 'approved'],
                ['order' => 'views_count.desc', 'limit' => $limit, 'offset' => $offset]
            );
            $recipes = $this->attachSavedState(
                $this->attachHomeFeedMetrics($recipes, $userId, false),
                $userId
            );
            return response()->json([
                'success'    => true,
                'feed_mode'  => 'popular',
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
