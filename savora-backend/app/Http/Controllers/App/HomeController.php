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

        // Load profile stats
        $profile = null;
        try {
            $profiles = $this->supabase->select(
                'profiles',
                ['id', 'username', 'full_name', 'avatar_url', 'role', 'is_premium'],
                ['id' => $userId]
            );
            $profile = $profiles[0] ?? null;
        } catch (Exception) {}

        $myRecipesCount = 0;
        $bookmarksCount = 0;
        $followersCount = 0;

        try {
            $myRecipesCount = count($this->supabase->select('recipes', ['id'], ['user_id' => $userId, 'status' => 'approved']));
        } catch (Exception) {}

        try {
            $boards = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId]);
            foreach ($boards as $board) {
                $bookmarksCount += count($this->supabase->select('board_recipes', ['id'], ['board_id' => $board['id']]));
            }
        } catch (Exception) {}

        try {
            $followersCount = count($this->supabase->select('follows', ['follower_id'], ['following_id' => $userId]));
        } catch (Exception) {}

        // Load feed — pakai endpoint yang sudah ada di API
        $feed = [];
        try {
            $feed = $this->supabase->select(
                'recipes',
                ['*', 'profiles!recipes_user_id_fkey(username, avatar_url, role)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['status' => 'approved'],
                ['order' => 'created_at.desc', 'limit' => $limit, 'offset' => $offset]
            );

            foreach ($feed as &$recipe) {
                try {
                    $ratings = $this->supabase->select('recipe_ratings', ['rating'], ['recipe_id' => $recipe['id']]);
                    $total   = count($ratings);
                    $recipe['rating_avg']   = $total > 0 ? round(array_sum(array_column($ratings, 'rating')) / $total, 1) : 0;
                    $recipe['rating_count'] = $total;
                } catch (Exception) {
                    $recipe['rating_avg']   = 0;
                    $recipe['rating_count'] = 0;
                }
            }
        } catch (Exception $e) {
            $feed = [];
        }

        $hasMore = count($feed) === $limit;

        return view('app.home', compact(
            'profile', 'feed', 'offset', 'hasMore',
            'myRecipesCount', 'bookmarksCount', 'followersCount'
        ));
    }
}