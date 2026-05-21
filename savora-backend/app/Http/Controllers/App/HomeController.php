<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Exception;

class HomeController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        $userId = session('user_id');
        $limit  = 10;
        $offset = (int) $request->query('offset', 0);
        $poolLimit = $limit * 4;

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
            $myRecipesCount = $this->supabase->count('recipes', ['user_id' => $userId, 'status' => 'approved']);
        } catch (Exception) {}

        try {
            $followersCount = $this->supabase->count('follows', ['following_id' => $userId]);
        } catch (Exception) {}

        // Count saved recipes (not boards) to match mobile behavior.
        // A recipe saved in multiple boards is counted once.
        try {
            $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
            $boardIds = array_column($boards, 'id');

            if (!empty($boardIds)) {
                $savedRows = $this->supabase->select(
                    'board_recipes',
                    ['recipe_id'],
                    ['board_id' => ['operator' => 'in', 'values' => $boardIds]]
                );

                $bookmarksCount = count(array_unique(array_column($savedRows, 'recipe_id')));
            }
        } catch (Exception) {
            // Keep default 0 when favorites lookup fails.
        }

        // Feed + agregasi rating/like per resep (untuk kartu home + FYP)
        $feed = [];
        $rawFeedCount = 0;
        try {
            $feed = $this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'image_url', 'created_at', 'user_id', 'category_id', 'views_count',
                 'profiles!recipes_user_id_fkey(username, avatar_url, role)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['status' => 'approved'],
                ['order' => 'created_at.desc', 'limit' => $poolLimit, 'offset' => $offset]
            );
            $rawFeedCount = count($feed);
        } catch (Exception $e) {
            $feed = [];
        }

        $recipeIds = array_column($feed, 'id');
        $recipeIds = array_values(array_filter($recipeIds));

        if (! empty($recipeIds)) {
            $sums   = [];
            $counts = [];
            $likeCounts = [];
            $likedRecipeIds = [];
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

                $ratingScore = $feed[$i]['rating_avg'] ? ((float) $feed[$i]['rating_avg'] * 2) : 0;
                $viewsScore = log(((int) ($row['views_count'] ?? 0)) + 1);
                $ageHours = max(1, now()->diffInHours(\Carbon\Carbon::parse($row['created_at'] ?? now())));
                $freshnessScore = 12 / sqrt($ageHours);
                $feed[$i]['fyp_score'] = ($likes * 4) + $ratingScore + $viewsScore + $freshnessScore;
            }

            usort($feed, fn ($a, $b) => ($b['fyp_score'] ?? 0) <=> ($a['fyp_score'] ?? 0));
            $feed = array_slice($feed, 0, $limit);
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

        $hasMore = $rawFeedCount >= $poolLimit || count($feed) === $limit;

        // Get unread notifications count
        $unreadCount = 0;
        try {
            $unreadCount = $this->supabase->count('notifications', ['user_id' => $userId, 'is_read' => false]);
        } catch (Exception) {}

        return view('app.home', compact(
            'profile', 'feed', 'offset', 'hasMore',
            'myRecipesCount', 'bookmarksCount', 'followersCount', 'unreadCount',
            'favoriteBoards', 'recipeSavedBoards'
        ));
    }
}
