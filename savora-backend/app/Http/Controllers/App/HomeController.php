<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;

class HomeController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private UserSettingsService $settingsService
    ) {}

    public function index(Request $request)
    {
        $userId = session('user_id');
        $limit  = 10;
        $offset = (int) $request->query('offset', 0);
        $poolLimit = $limit * 4;
        $personalizedFeedEnabled = $this->settingsService->enabled($userId, 'allow_analytics');
        $feedQueryLimit = $personalizedFeedEnabled ? $poolLimit : $limit;
        $feedOrder = $personalizedFeedEnabled ? 'created_at.desc' : 'views_count.desc';

        // Load profile stats
        $profile = null;
        try {
            $profiles = $this->supabase->select(
                'profiles',
                ['id', 'username', 'full_name', 'avatar_url', 'role', 'is_premium', 'total_bookmarks'],
                ['id' => $userId]
            );
            $profile = $profiles[0] ?? null;
        } catch (Exception) {}

        // Use count() method yang lebih cepat daripada select + count
        $myRecipesCount = 0;
        $bookmarksCount = 0;
        $followersCount = 0;

        try {
            $myRecipesCount = Cache::remember("home_count_recipes:{$userId}", 60, fn () => $this->supabase->count('recipes', ['user_id' => $userId, 'status' => 'approved']));
        } catch (Exception) {}

        try {
            $followersCount = Cache::remember("home_count_followers:{$userId}", 60, fn () => $this->supabase->count('follows', ['following_id' => $userId]));
        } catch (Exception) {}

        // Count saved recipes (not boards) to match mobile behavior.
        // A recipe saved in multiple boards is counted once.
        try {
            $bookmarksCount = Cache::remember("home_count_bookmarks:{$userId}", 60, function () use ($userId) {
                $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
                $boardIds = array_column($boards, 'id');
                if (empty($boardIds)) return 0;

                $savedRows = $this->supabase->select(
                    'board_recipes',
                    ['recipe_id'],
                    ['board_id' => ['operator' => 'in', 'values' => $boardIds]]
                );

                return count(array_unique(array_column($savedRows, 'recipe_id')));
            });
        } catch (Exception) {
            // Keep default 0 when favorites lookup fails.
        }

        // Feed + agregasi rating/like per resep (untuk kartu home)
        $feed = [];
        $rawFeedCount = 0;
        try {
            $feed = $this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'image_url', 'created_at', 'user_id', 'category_id', 'views_count',
                 'profiles!recipes_user_id_fkey(username, avatar_url, role)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['status' => 'approved'],
                ['order' => $feedOrder, 'limit' => $feedQueryLimit, 'offset' => $offset]
            );
            $rawFeedCount = count($feed);
        } catch (Exception $e) {
            $feed = [];
        }

        if ($personalizedFeedEnabled && ! empty($feed)) {
            $feed = $this->injectRandomRecipes($feed);
        }

        $recipeIds = array_column($feed, 'id');
        $recipeIds = array_values(array_filter($recipeIds));

        if (! empty($recipeIds)) {
            $sums   = [];
            $counts = [];
            $likeCounts = [];
            $likedRecipeIds = [];
            $signals = $personalizedFeedEnabled ? $this->buildPersonalSignals($userId) : [];
            try {
                $ratingRows = $this->supabase->select(
                    'recipe_ratings',
                    ['recipe_id', 'rating'],
                    ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
                );
                foreach ($ratingRows as $row) {
                    $rid = $row['recipe_id'] ?? null;
                    if ($rid === null) {
                        continue;
                    }
                    $r = (float) ($row['rating'] ?? 0);
                    if (! isset($sums[$rid])) {
                        $sums[$rid]   = 0.0;
                        $counts[$rid] = 0;
                    }
                    $sums[$rid] += $r;
                    $counts[$rid]++;
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
                    $rid = $row['recipe_id'] ?? null;
                    if (! $rid) continue;
                    $likeCounts[$rid] = ($likeCounts[$rid] ?? 0) + 1;
                    if (($row['user_id'] ?? null) === $userId) {
                        $likedRecipeIds[$rid] = true;
                    }
                }
            } catch (Exception) {
            }

            foreach ($feed as $i => $row) {
                $rid = $row['id'] ?? null;
                if ($rid !== null && ! empty($counts[$rid])) {
                    $feed[$i]['rating_avg']   = round($sums[$rid] / $counts[$rid], 1);
                    $feed[$i]['rating_count'] = $counts[$rid];
                } else {
                    $feed[$i]['rating_avg']   = null;
                    $feed[$i]['rating_count'] = 0;
                }
                $likes = $rid ? (int) ($likeCounts[$rid] ?? 0) : 0;
                $feed[$i]['likes_count'] = $likes;
                $feed[$i]['is_liked'] = $rid ? ! empty($likedRecipeIds[$rid]) : false;

                if ($personalizedFeedEnabled) {
                    $ratingScore = $feed[$i]['rating_avg'] ? ((float) $feed[$i]['rating_avg'] * 2) : 0;
                    $viewsScore = log(((int) ($row['views_count'] ?? 0)) + 1);
                    $ageHours = max(1, now()->diffInHours(\Carbon\Carbon::parse($row['created_at'] ?? now())));
                    $freshnessScore = 12 / sqrt($ageHours);
                    $signalScore = $this->personalSignalScore($row, $signals);
                    $randomScore = (float) ($row['_random_score'] ?? 0);
                    $feed[$i]['signal_score'] = $signalScore;
                    $feed[$i]['random_score'] = $randomScore;
                    $feed[$i]['ranking_score'] = ($likes * 4) + $ratingScore + $viewsScore + $freshnessScore + $signalScore + $randomScore;
                }
            }

            if ($personalizedFeedEnabled) {
                usort($feed, fn ($a, $b) => ($b['ranking_score'] ?? 0) <=> ($a['ranking_score'] ?? 0));
                $feed = array_slice($feed, 0, $limit);
            }
            $recipeIds = array_values(array_filter(array_column($feed, 'id')));
        }

        $favoriteBoards      = [];
        $recipeSavedBoards   = [];
        if ($userId && ! empty($recipeIds)) {
            try {
                $favoriteBoards = $this->supabase->select(
                    'recipe_boards',
                    ['id', 'name', 'description'],
                    ['user_id' => $userId],
                    ['order' => 'created_at.asc']
                );
                $boardIds = array_column($favoriteBoards, 'id');
                if (! empty($boardIds)) {
                    $links = $this->supabase->select(
                        'board_recipes',
                        ['board_id', 'recipe_id'],
                        [
                            'board_id'   => ['operator' => 'in', 'values' => $boardIds],
                            'recipe_id'  => ['operator' => 'in', 'values' => $recipeIds],
                        ]
                    );
                    foreach ($links as $link) {
                        $rid = $link['recipe_id'] ?? null;
                        $bid = $link['board_id'] ?? null;
                        if ($rid === null || $bid === null) {
                            continue;
                        }
                        if (! isset($recipeSavedBoards[$rid])) {
                            $recipeSavedBoards[$rid] = [];
                        }
                        $recipeSavedBoards[$rid][] = $bid;
                    }
                }
            } catch (Exception) {
            }
        }

        $hasMore = $rawFeedCount >= $feedQueryLimit || count($feed) === $limit;

        // Get unread notifications count
        $unreadCount = 0;
        try {
            $unreadCount = Cache::remember("app_unread_count:{$userId}", 30, function () use ($userId) {
                $notifications = $this->supabase->select(
                    'notifications',
                    ['type', 'related_entity_type', 'related_entity_id', 'is_read'],
                    ['user_id' => $userId],
                    ['order' => 'created_at.desc', 'limit' => 50]
                );

                $unread = 0;
                $seenNotifications = [];
                foreach ($notifications as $notification) {
                    $key = implode('|', [
                        $notification['type'] ?? '',
                        $notification['related_entity_type'] ?? '',
                        $notification['related_entity_id'] ?? '',
                    ]);

                    if (isset($seenNotifications[$key])) continue;
                    $seenNotifications[$key] = true;

                    if (! ($notification['is_read'] ?? false)) {
                        $unread++;
                    }
                }
                return $unread;
            });
        } catch (Exception) {}

        return view('app.home', compact(
            'profile', 'feed', 'offset', 'hasMore',
            'myRecipesCount', 'bookmarksCount', 'followersCount', 'unreadCount',
            'favoriteBoards', 'recipeSavedBoards'
        ));
    }

    private function injectRandomRecipes(array $feed): array
    {
        try {
            $existing = array_fill_keys(array_filter(array_column($feed, 'id')), true);
            $randomRecipes = $this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'image_url', 'created_at', 'user_id', 'category_id', 'views_count',
                 'profiles!recipes_user_id_fkey(username, avatar_url, role)', 'categories(name)', 'recipe_tags(tags(id, name))'],
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
}
