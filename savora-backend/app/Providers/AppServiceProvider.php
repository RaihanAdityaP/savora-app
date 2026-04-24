<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()->is('admin*')) {
            $pendingTagCount    = 0;
            $pendingRecipeCount = 0;

            try {
                $supabase           = app(SupabaseService::class);
                $pendingTagCount    = count($supabase->select('tags',    ['id'], ['is_approved' => false]));
                $pendingRecipeCount = count($supabase->select('recipes', ['id'], ['status'      => 'pending']));
            } catch (\Exception) {}

            View::share('pendingTagCount',    $pendingTagCount);
            View::share('pendingRecipeCount', $pendingRecipeCount);
        }
    }
}