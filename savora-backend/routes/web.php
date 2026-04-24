<?php

use App\Http\Controllers\AdminWebController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminTagController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

// ── Auth (guest only) ──────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login'])->name('login.post');
    Route::post('logout',[AdminLoginController::class, 'logout'])->name('logout');
});

// ── Protected admin routes ─────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    Route::get('dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
    Route::get('users',     [AdminWebController::class, 'users'])->name('users');
    Route::get('recipes',   [AdminWebController::class, 'recipes'])->name('recipes');
    Route::get('logs',      [AdminWebController::class, 'logs'])->name('logs');

    Route::post('users/{id}/toggle-ban',     [AdminWebController::class, 'toggleUserBan'])->name('users.toggle-ban');
    Route::post('users/{id}/toggle-premium', [AdminWebController::class, 'togglePremium'])->name('users.toggle-premium');
    Route::post('recipes/{id}/moderate',     [AdminWebController::class, 'moderateRecipe'])->name('recipes.moderate');

    Route::get('tags',              [AdminTagController::class, 'index'])->name('tags');
    Route::post('tags/{id}/moderate',[AdminTagController::class, 'moderate'])->name('tags.moderate');
    Route::post('tags/{id}/delete', [AdminTagController::class, 'destroy'])->name('tags.destroy');
});