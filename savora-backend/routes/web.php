<?php

use App\Http\Controllers\AdminWebController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
    Route::get('users', [AdminWebController::class, 'users'])->name('users');
    Route::get('recipes', [AdminWebController::class, 'recipes'])->name('recipes');
    Route::get('logs', [AdminWebController::class, 'logs'])->name('logs');

    Route::post('users/{id}/toggle-ban', [AdminWebController::class, 'toggleUserBan'])->name('users.toggle-ban');
    Route::post('users/{id}/toggle-premium', [AdminWebController::class, 'togglePremium'])->name('users.toggle-premium');
    Route::post('recipes/{id}/moderate', [AdminWebController::class, 'moderateRecipe'])->name('recipes.moderate');
});