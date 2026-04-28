<?php

use App\Http\Controllers\AdminWebController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\App\LoginController;
use App\Http\Controllers\App\HomeController;
use App\Http\Controllers\App\RecipeController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\SearchController;
use App\Http\Controllers\App\FavoriteController;
use App\Http\Controllers\App\NotificationController;
use App\Http\Controllers\App\AIChatController;
use Illuminate\Support\Facades\Route;

// ── Root redirect ──────────────────────────────────────────────
Route::redirect('/', '/app/home');

// ── Admin auth (guest only) ────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login'])->name('login.post');
    Route::post('logout',[AdminLoginController::class, 'logout'])->name('logout');
});

// ── Admin protected ────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    Route::get('dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
    Route::get('users',     [AdminWebController::class, 'users'])->name('users');
    Route::get('recipes',   [AdminWebController::class, 'recipes'])->name('recipes');
    Route::get('logs',      [AdminWebController::class, 'logs'])->name('logs');

    Route::post('users/{id}/toggle-ban',     [AdminWebController::class, 'toggleUserBan'])->name('users.toggle-ban');
    Route::post('users/{id}/toggle-premium', [AdminWebController::class, 'togglePremium'])->name('users.toggle-premium');
    Route::post('recipes/{id}/moderate',     [AdminWebController::class, 'moderateRecipe'])->name('recipes.moderate');

    Route::get('tags',               [AdminTagController::class, 'index'])->name('tags');
    Route::post('tags/{id}/moderate',[AdminTagController::class, 'moderate'])->name('tags.moderate');
    Route::post('tags/{id}/delete',  [AdminTagController::class, 'destroy'])->name('tags.destroy');
});

// ─────────────────────────────────────────────────────────────
// APP (User Web)
// ─────────────────────────────────────────────────────────────

// Guest only
Route::prefix('app')->name('app.')->group(function () {
    Route::get('login',    [LoginController::class, 'showLogin'])->name('login');
    Route::post('login',   [LoginController::class, 'login'])->name('login.post');
    Route::get('register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('register',[LoginController::class, 'register'])->name('register.post');
    Route::post('logout',  [LoginController::class, 'logout'])->name('logout');
});

// Protected user routes
Route::prefix('app')->name('app.')->middleware('user.auth')->group(function () {

    // ── Home / Feed ──────────────────────────────────────
    Route::get('home', [HomeController::class, 'index'])->name('home');

    // ── Search ───────────────────────────────────────────
    Route::get('search', [SearchController::class, 'index'])->name('search');

    // ── Recipes ──────────────────────────────────────────
    Route::get('recipes/create',      [RecipeController::class, 'create'])->name('recipe.create');
    Route::post('recipes',            [RecipeController::class, 'store'])->name('recipe.store');
    Route::get('recipes/{id}',        [RecipeController::class, 'show'])->name('recipe.show');
    Route::get('recipes/{id}/edit',   [RecipeController::class, 'edit'])->name('recipe.edit');
    Route::post('recipes/{id}',       [RecipeController::class, 'update'])->name('recipe.update');       // POST + _method=PUT
    Route::post('recipes/{id}/delete',[RecipeController::class, 'destroy'])->name('recipe.destroy');
    Route::post('recipes/{id}/comment',[RecipeController::class, 'postComment'])->name('recipe.comment');
    Route::post('comments/{id}/delete',[RecipeController::class, 'deleteComment'])->name('comment.delete');
    Route::post('recipes/{id}/rate',  [RecipeController::class, 'rate'])->name('recipe.rate');

    // ── Profile ───────────────────────────────────────────
    Route::get('profile',             [ProfileController::class, 'show'])->name('profile');
    Route::get('profile/{userId}',    [ProfileController::class, 'show'])->name('profile.user');
    Route::post('profile',            [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/{userId}/follow',   [ProfileController::class, 'follow'])->name('profile.follow');
    Route::post('profile/{userId}/unfollow', [ProfileController::class, 'unfollow'])->name('profile.unfollow');

    // ── Favorites ─────────────────────────────────────────
    Route::get('favorites',                        [FavoriteController::class, 'index'])->name('favorites');
    Route::get('favorites/{boardId}',              [FavoriteController::class, 'show'])->name('favorites.board');
    Route::post('favorites/boards',                [FavoriteController::class, 'createBoard'])->name('favorites.board.create');
    Route::post('favorites/boards/{boardId}',      [FavoriteController::class, 'updateBoard'])->name('favorites.board.update');
    Route::post('favorites/boards/{boardId}/delete',[FavoriteController::class, 'deleteBoard'])->name('favorites.board.delete');
    Route::post('favorites/save',                  [FavoriteController::class, 'save'])->name('favorites.save');
    Route::post('favorites/remove',                [FavoriteController::class, 'remove'])->name('favorites.remove');

    // ── Notifications ─────────────────────────────────────
    Route::get('notifications',                    [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/{id}/read',         [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all',          [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/{id}/delete',       [NotificationController::class, 'destroy'])->name('notifications.delete');
    Route::post('notifications/delete-all',        [NotificationController::class, 'destroyAll'])->name('notifications.delete-all');

    // ── AI Chat ───────────────────────────────────────────
    Route::get('ai',                                  [AIChatController::class, 'index'])->name('ai');
    Route::post('ai/conversations',                   [AIChatController::class, 'createConversation'])->name('ai.create');
    Route::get('ai/conversations/{id}',               [AIChatController::class, 'showConversation'])->name('ai.conversation');
    Route::post('ai/conversations/{id}/send',         [AIChatController::class, 'sendMessage'])->name('ai.send');
    Route::post('ai/conversations/{id}/rename',       [AIChatController::class, 'renameConversation'])->name('ai.rename');
    Route::post('ai/conversations/{id}/delete',       [AIChatController::class, 'deleteConversation'])->name('ai.delete');
    Route::post('ai/delete-all',                      [AIChatController::class, 'deleteAll'])->name('ai.delete-all');
    Route::get('ai/settings',                         [AIChatController::class, 'settings'])->name('ai.settings');
    Route::post('ai/settings',                        [AIChatController::class, 'saveSettings'])->name('ai.settings.save');
});