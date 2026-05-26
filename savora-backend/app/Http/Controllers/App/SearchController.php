<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;

class SearchController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        $query       = trim((string) $request->query('q', ''));
        $categoryId  = $request->query('category_id');
        $tagId       = $request->query('tag_id');
        $difficulty  = $request->query('difficulty');
        $sortBy      = $request->query('sort', 'popular');
        $minCalories = $request->query('min_calories');
        $maxCalories = $request->query('max_calories');

        $categories = [];
        $popularTags = [];
        $results = [];

        try {
            $categories  = Cache::remember('search_categories', 300, fn () => $this->supabase->select('categories', ['id', 'name'], [], ['order' => 'name.asc']));
            $popularTags = Cache::remember('search_popular_tags', 300, fn () => $this->supabase->select('tags', ['id', 'name', 'usage_count'], ['is_approved' => true], ['order' => 'usage_count.desc', 'limit' => 20]));
        } catch (Exception) {}

        // Hanya search kalau ada input
        if ($query !== '' || $categoryId || $tagId || $difficulty || $minCalories || $maxCalories) {
            try {
                $filters = ['status' => 'approved'];

                if ($categoryId) $filters['category_id'] = (int) $categoryId;
                if ($difficulty)  $filters['difficulty']  = $difficulty;

                if ($minCalories) {
                    $filters['calories'] = ['operator' => 'gte', 'value' => (int) $minCalories];
                }

                $results = $this->supabase->select(
                    'recipes',
                    ['*', 'profiles!recipes_user_id_fkey(username, avatar_url)', 'categories(name)', 'recipe_tags(tags(id, name))'],
                    $filters,
                    ['order' => 'views_count.desc', 'limit' => 60]
                );

                // PHP-side filters
                if ($query !== '') {
                    $needle  = strtolower($query);
                    $results = array_values(array_filter($results, function ($r) use ($needle) {
                        return str_contains(strtolower($r['title'] ?? ''), $needle)
                            || str_contains(strtolower($r['description'] ?? ''), $needle);
                    }));
                }

                if ($maxCalories) {
                    $max     = (int) $maxCalories;
                    $results = array_values(array_filter($results, fn($r) => isset($r['calories']) && (int) $r['calories'] <= $max));
                }

                if ($tagId) {
                    $tagId = (int) $tagId;
                    $results = array_values(array_filter($results, function ($r) use ($tagId) {
                        foreach ($r['recipe_tags'] ?? [] as $rt) {
                            if (($rt['tags']['id'] ?? null) === $tagId) return true;
                        }
                        return false;
                    }));
                }

                // Sort
                usort($results, function ($a, $b) use ($sortBy) {
                    return match ($sortBy) {
                        'newest' => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''),
                        default  => ($b['views_count'] ?? 0) <=> ($a['views_count'] ?? 0),
                    };
                });

                $recipeIds = array_values(array_filter(array_column($results, 'id')));
                $ratingSums = [];
                $ratingCounts = [];
                if (! empty($recipeIds)) {
                    try {
                        $ratings = $this->supabase->select(
                            'recipe_ratings',
                            ['recipe_id', 'rating'],
                            ['recipe_id' => ['operator' => 'in', 'values' => $recipeIds]]
                        );
                        foreach ($ratings as $rating) {
                            $rid = $rating['recipe_id'] ?? null;
                            if (! $rid) continue;
                            $ratingSums[$rid] = ($ratingSums[$rid] ?? 0) + (float) ($rating['rating'] ?? 0);
                            $ratingCounts[$rid] = ($ratingCounts[$rid] ?? 0) + 1;
                        }
                    } catch (Exception) {
                    }
                }

                foreach ($results as &$r) {
                    $rid = $r['id'] ?? null;
                    $total = $rid ? (int) ($ratingCounts[$rid] ?? 0) : 0;
                    $r['rating_avg'] = $total > 0 ? round(($ratingSums[$rid] ?? 0) / $total, 1) : 0;
                    $r['rating_count'] = $total;
                }
                unset($r);

            } catch (Exception $e) {
                $results = [];
            }
        }

        return view('app.search', compact(
            'query', 'results', 'categories', 'popularTags',
            'categoryId', 'tagId', 'difficulty', 'sortBy',
            'minCalories', 'maxCalories'
        ));
    }
}
