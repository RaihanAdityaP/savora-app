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

        // Use count() method yang lebih cepat daripada select + count
        $myRecipesCount = 0;
        $bookmarksCount = 0;  // Lazy load ini di blade atau remove karena expensive
        $followersCount = 0;

        try {
            $myRecipesCount = $this->supabase->count('recipes', ['user_id' => $userId, 'status' => 'approved']);
        } catch (Exception) {}

        // Skip bookmarksCount dulu karena terlalu expensive (nested loop queries)
        // Try later dengan aggregate functions

        try {
            $followersCount = $this->supabase->count('follows', ['following_id' => $userId]);
        } catch (Exception) {}

        // Load feed tanpa per-recipe rating queries (load ratings lazy di frontend)
        $feed = [];
        try {
            $feed = $this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'image_url', 'created_at', 'user_id', 'category_id',
                 'profiles!recipes_user_id_fkey(username, avatar_url, role)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                ['status' => 'approved'],
                ['order' => 'created_at.desc', 'limit' => $limit, 'offset' => $offset]
            );
        } catch (Exception $e) {
            $feed = [];
        }

        $hasMore = count($feed) === $limit;

        // Get unread notifications count
        $unreadCount = 0;
        try {
            $unreadCount = $this->supabase->count('notifications', ['user_id' => $userId, 'is_read' => false]);
        } catch (Exception) {}

        return view('app.home', compact(
            'profile', 'feed', 'offset', 'hasMore',
            'myRecipesCount', 'bookmarksCount', 'followersCount', 'unreadCount'
        ));
    }
}
