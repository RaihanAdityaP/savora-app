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
use App\Http\Controllers\App\TagController;
use App\Http\Controllers\App\UploadTokenController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// ── ANDROID APP LINKS VERIFICATION ───────────────────────────────────────────────
Route::get('/.well-known/assetlinks.json', function () {
    return response()->json([
        [
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target'   => [
                'namespace'              => 'android_app',
                'package_name'           => 'id.savora.app',
                'sha256_cert_fingerprints' => [
                    env('ANDROID_SHA256_FINGERPRINT', 'REPLACE_WITH_YOUR_SHA256_FINGERPRINT'),
                ],
            ],
        ],
    ]);
})->name('assetlinks');

Route::prefix('errors')->name('errors.')->group(function () {
    Route::view('403', 'app.errors.403')->name('403');
    Route::view('404', 'app.errors.404')->name('404');
    Route::view('419', 'app.errors.419')->name('419');
    Route::view('429', 'app.errors.429')->name('429');
    Route::view('500', 'app.errors.500')->name('500');
    Route::view('503', 'app.errors.503')->name('503');
});

// ── Public pages ───────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/license', [LandingController::class, 'license'])->name('license');

// ── Admin auth ─────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', fn() => redirect()->route('app.login'))->name('login');
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
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

    Route::get('tags',                [AdminTagController::class, 'index'])->name('tags');
    Route::post('tags/{id}/moderate', [AdminTagController::class, 'moderate'])->name('tags.moderate');
    Route::post('tags/{id}/delete',   [AdminTagController::class, 'destroy'])->name('tags.destroy');
});

// ══════════════════════════════════════════════════════════════════════════════
// PUBLIC WEB PAGES (tanpa login)
//
// /r/{id}       — share link dari Flutter app, diintercept Android App Links,
//                 fallback ke browser jika app tidak terinstall.
// /recipes/{id} — canonical web detail, hanya dibuka di browser (tidak ada
//                 App Links intent-filter untuk path ini).
// /profile/{id} — sama, dibuka di browser / App Links untuk profile.
// ══════════════════════════════════════════════════════════════════════════════
Route::get('/r/{id}',         [RecipeController::class, 'show'])->name('web.recipe.share');
Route::get('/search',         [SearchController::class, 'index'])->name('web.search');
Route::get('/recipes/{id}',   [RecipeController::class, 'show'])->name('web.recipe.show');
Route::get('/profile/{userId}',[ProfileController::class, 'show'])->name('web.profile.user');

// ── App auth (guest only) ──────────────────────────────────────
Route::prefix('app')->name('app.')->group(function () {
    Route::get('login',    [LoginController::class, 'showLogin'])->name('login');
    Route::post('login',   [LoginController::class, 'login'])->name('login.post');
    Route::get('register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('register',[LoginController::class, 'register'])->name('register.post');
    Route::post('logout',  [LoginController::class, 'logout'])->name('logout');
});

// ── Protected user routes ──────────────────────────────────────
Route::prefix('app')->name('app.')->middleware('user.auth')->group(function () {

    Route::get('home', [HomeController::class, 'index'])->name('home');

    Route::get('search', [SearchController::class, 'index'])->name('search');

    // Recipes — static routes SEBELUM wildcard {id}
    Route::get('recipes/create',       [RecipeController::class, 'create'])->name('recipe.create');
    Route::post('recipes',             [RecipeController::class, 'store'])->name('recipe.store');
    Route::get('recipes/{id}/edit',    [RecipeController::class, 'edit'])->name('recipe.edit');
    Route::post('recipes/{id}',        [RecipeController::class, 'update'])->name('recipe.update');
    Route::post('recipes/{id}/delete', [RecipeController::class, 'destroy'])->name('recipe.destroy');
    Route::post('recipes/{id}/comment',[RecipeController::class, 'postComment'])->name('recipe.comment');
    Route::post('recipes/{id}/rate',   [RecipeController::class, 'rate'])->name('recipe.rate');
    Route::post('comments/{id}/delete',[RecipeController::class, 'deleteComment'])->name('comment.delete');
    Route::get('recipes/{id}',         [RecipeController::class, 'show'])->name('recipe.show');

    // Profile
    Route::get('profile',                    [ProfileController::class, 'show'])->name('profile');
    Route::post('profile',                   [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/{userId}/follow',   [ProfileController::class, 'follow'])->name('profile.follow');
    Route::post('profile/{userId}/unfollow', [ProfileController::class, 'unfollow'])->name('profile.unfollow');
    Route::get('profile/{userId}',           [ProfileController::class, 'show'])->name('profile.user');

    // Favorites
    Route::get('favorites',                         [FavoriteController::class, 'index'])->name('favorites');
    Route::post('favorites/boards',                 [FavoriteController::class, 'createBoard'])->name('favorites.board.create');
    Route::post('favorites/save',                   [FavoriteController::class, 'save'])->name('favorites.save');
    Route::post('favorites/remove',                 [FavoriteController::class, 'remove'])->name('favorites.remove');
    Route::post('favorites/boards/{boardId}',       [FavoriteController::class, 'updateBoard'])->name('favorites.board.update');
    Route::post('favorites/boards/{boardId}/delete',[FavoriteController::class, 'deleteBoard'])->name('favorites.board.delete');
    Route::get('favorites/{boardId}',               [FavoriteController::class, 'show'])->name('favorites.board');

    // Notifications
    Route::get('notifications',              [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all',    [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/delete-all',  [NotificationController::class, 'destroyAll'])->name('notifications.delete-all');
    Route::post('notifications/{id}/read',   [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/{id}/delete', [NotificationController::class, 'destroy'])->name('notifications.delete');

    // AI Chat
    Route::get('ai',                             [AIChatController::class, 'index'])->name('ai');
    Route::post('ai/delete-all',                 [AIChatController::class, 'deleteAll'])->name('ai.delete-all');
    Route::get('ai/settings',                    [AIChatController::class, 'settings'])->name('ai.settings');
    Route::post('ai/settings',                   [AIChatController::class, 'saveSettings'])->name('ai.settings.save');
    Route::post('ai/conversations',              [AIChatController::class, 'createConversation'])->name('ai.create');
    Route::post('ai/conversations/{id}/send',    [AIChatController::class, 'sendMessage'])->name('ai.send');
    Route::post('ai/conversations/{id}/rename',  [AIChatController::class, 'renameConversation'])->name('ai.rename');
    Route::post('ai/conversations/{id}/delete',  [AIChatController::class, 'deleteConversation'])->name('ai.delete');
    Route::get('ai/conversations/{id}',          [AIChatController::class, 'showConversation'])->name('ai.conversation');

    // ── Upload Token ──────────────────────────────────
    Route::post('upload-token', [UploadTokenController::class, 'generate'])->name('upload-token.generate');

    // Settings
    Route::get('settings',  [App\Http\Controllers\App\SettingsController::class, 'show'])->name('settings');
    Route::post('settings', [App\Http\Controllers\App\SettingsController::class, 'save'])->name('settings.save');

    // Tags
    Route::get('tags',  [TagController::class, 'index'])->name('tags');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
});