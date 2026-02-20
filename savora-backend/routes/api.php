<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TagController;

// ─── AI ───────────────────────────────────────────────────────────────────
// AI paling mahal resource-nya, limit ketat
Route::prefix('ai')->middleware('throttle:20,1')->group(function () {
    Route::get('test', [AIController::class, 'testConnection']);
    Route::post('ask', [AIController::class, 'askCookingQuestion']);
    Route::post('analyze-image', [AIController::class, 'analyzeRecipeFromImage']);
    Route::post('suggest-recipes', [AIController::class, 'suggestRecipes']);
    Route::post('generate-recipe', [AIController::class, 'generateRecipe']);
    Route::post('suggest-variations', [AIController::class, 'suggestVariations']);
});

// ─── RECIPES ──────────────────────────────────────────────────────────────
Route::prefix('recipes')->group(function () {
    // Read = lebih longgar
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/', [RecipeController::class, 'index']);
        Route::get('search', [RecipeController::class, 'search']);
        Route::get('{id}', [RecipeController::class, 'show']);
    });

    // Write = lebih ketat
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [RecipeController::class, 'store']);
        Route::put('{id}', [RecipeController::class, 'update']);
        Route::delete('{id}', [RecipeController::class, 'destroy']);
    });

    // Admin moderation
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('{id}/approve', [RecipeController::class, 'approve']);
        Route::post('{id}/reject', [RecipeController::class, 'reject']);
    });
});

// ─── USERS ────────────────────────────────────────────────────────────────
Route::prefix('users')->group(function () {
    // Read
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::get('{id}/is-following', [UserController::class, 'isFollowing']);
        Route::get('{id}/followers', [UserController::class, 'followers']);
        Route::get('{id}/following', [UserController::class, 'following']);
    });

    // Write
    Route::middleware('throttle:20,1')->group(function () {
        Route::put('{id}', [UserController::class, 'update']);
        Route::post('{id}/follow', [UserController::class, 'follow']);
        Route::post('{id}/unfollow', [UserController::class, 'unfollow']);
    });

    // Admin only — tetap dibatasi mencegah abuse akun admin
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('{id}/ban', [UserController::class, 'ban']);
        Route::post('{id}/unban', [UserController::class, 'unban']);
        Route::post('{id}/toggle-premium', [UserController::class, 'togglePremium']);
    });
});

// ─── CATEGORIES ───────────────────────────────────────────────────────────
Route::prefix('categories')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('{id}', [CategoryController::class, 'show']);
        Route::get('{id}/recipes', [CategoryController::class, 'recipes']);
    });

    Route::middleware('throttle:15,1')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('{id}', [CategoryController::class, 'update']);
        Route::delete('{id}', [CategoryController::class, 'destroy']);
    });
});

// ─── TAGS ─────────────────────────────────────────────────────────────────
Route::prefix('tags')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::get('search', [TagController::class, 'search']);
        Route::get('popular', [TagController::class, 'popular']);
        Route::get('{id}', [TagController::class, 'show']);
        Route::get('{id}/recipes', [TagController::class, 'recipes']);
    });

    Route::middleware('throttle:15,1')->group(function () {
        Route::post('/', [TagController::class, 'store']);
        Route::put('{id}', [TagController::class, 'update']);
        Route::delete('{id}', [TagController::class, 'destroy']);
        Route::post('{id}/approve', [TagController::class, 'approve']);
    });
});

// ─── RATINGS ──────────────────────────────────────────────────────────────
Route::prefix('ratings')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('recipe/{recipeId}', [RatingController::class, 'getRecipeRatings']);
        Route::get('recipe/{recipeId}/average', [RatingController::class, 'getAverageRating']);
        Route::get('recipe/{recipeId}/stats', [RatingController::class, 'getRecipeRatingStats']);
        Route::get('user/{userId}', [RatingController::class, 'getUserRatings']);
        Route::get('user/{userId}/recipe/{recipeId}', [RatingController::class, 'getUserRecipeRating']);
    });

    // Rating/review spam prevention
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [RatingController::class, 'store']);
        Route::put('{id}', [RatingController::class, 'update']);
        Route::delete('{id}', [RatingController::class, 'destroy']);
    });
});

// ─── FAVORITES ────────────────────────────────────────────────────────────
Route::prefix('favorites')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('user/{userId}', [FavoriteController::class, 'getUserFavorites']);
        Route::get('boards/{userId}', [FavoriteController::class, 'getBoards']);
        Route::get('boards/{boardId}/recipes', [FavoriteController::class, 'getBoardRecipes']);
    });

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/', [FavoriteController::class, 'destroy']);
        Route::post('boards', [FavoriteController::class, 'createBoard']);
        Route::put('boards/{boardId}', [FavoriteController::class, 'updateBoard']);
        Route::delete('boards/{boardId}', [FavoriteController::class, 'deleteBoard']);
        Route::post('boards/{boardId}/recipes', [FavoriteController::class, 'addRecipeToBoard']);
        Route::delete('boards/{boardId}/recipes/{recipeId}', [FavoriteController::class, 'removeRecipeFromBoard']);
    });
});

// ─── NOTIFICATIONS ────────────────────────────────────────────────────────
Route::prefix('notifications')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('user/{userId}', [NotificationController::class, 'getUserNotifications']);
        Route::get('user/{userId}/unread-count', [NotificationController::class, 'getUnreadCount']);
    });

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('user/{userId}/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });

    // Send notification lebih ketat — bisa dipakai spam
    Route::post('send', [NotificationController::class, 'sendNotification'])
        ->middleware('throttle:10,1');

    Route::post('register-device', [NotificationController::class, 'registerDevice'])
        ->middleware('throttle:5,1');
});

// ─── ADMIN ────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('throttle:30,1')->group(function () {
    Route::get('statistics', function () {
        $supabase = app(\App\Services\SupabaseService::class);
        try {
            $totalUsers   = count($supabase->select('profiles', ['id']));
            $bannedUsers  = count($supabase->select('profiles', ['id'], ['is_banned' => true]));
            $totalRecipes = count($supabase->select('recipes', ['id']));
            $pending      = count($supabase->select('recipes', ['id'], ['status' => 'pending']));
            $totalTags    = count($supabase->select('tags', ['id']));
            $pendingTags  = count($supabase->select('tags', ['id'], ['is_approved' => false]));

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users'     => $totalUsers,
                    'banned_users'    => $bannedUsers,
                    'total_recipes'   => $totalRecipes,
                    'pending_recipes' => $pending,
                    'total_tags'      => $totalTags,
                    'pending_tags'    => $pendingTags,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });

    Route::get('activity-logs', function (\Illuminate\Http\Request $request) {
        $supabase = app(\App\Services\SupabaseService::class);
        try {
            $limit  = $request->input('limit', 20);
            $offset = $request->input('offset', 0);

            $logs = $supabase->select(
                'activity_logs',
                ['*', 'profiles:user_id(username)'],
                [],
                ['order' => 'created_at.desc', 'limit' => $limit, 'offset' => $offset]
            );

            return response()->json(['success' => true, 'data' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });
});