<?php

use App\Http\Controllers\AdminWebController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminTagController;

Route::redirect('/', '/admin/dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
    Route::get('users', [AdminWebController::class, 'users'])->name('users');
    Route::get('recipes', [AdminWebController::class, 'recipes'])->name('recipes');
    Route::get('logs', [AdminWebController::class, 'logs'])->name('logs');

    Route::post('users/{id}/toggle-ban', [AdminWebController::class, 'toggleUserBan'])->name('users.toggle-ban');
    Route::post('users/{id}/toggle-premium', [AdminWebController::class, 'togglePremium'])->name('users.toggle-premium');
    Route::post('recipes/{id}/moderate', [AdminWebController::class, 'moderateRecipe'])->name('recipes.moderate');

    Route::get('tags', [AdminTagController::class, 'index'])->name('tags');
    Route::post('tags/{id}/moderate', [AdminTagController::class, 'moderate'])->name('tags.moderate');
    Route::post('tags/{id}/delete', [AdminTagController::class, 'destroy'])->name('tags.destroy');
});