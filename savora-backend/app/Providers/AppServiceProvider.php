<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Admin panel shared vars ──────────────────────────────
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

        // ── App (user web) shared vars ───────────────────────────
        if (request()->is('app*')) {
            $appUnreadCount = 0;

            try {
                $userId = session('user_id');
                if ($userId) {
                    $supabase       = app(SupabaseService::class);
                    $appUnreadCount = count($supabase->select(
                        'notifications',
                        ['id'],
                        ['user_id' => $userId, 'is_read' => false]
                    ));
                }
            } catch (\Exception) {}

            View::share('appUnreadCount', $appUnreadCount);

            // Share session info ke semua app views
            View::share('sessionUserId',   session('user_id'));
            View::share('sessionUsername', session('user_username'));
            View::share('sessionRole',     session('user_role', 'user'));
            View::share('sessionAvatar',   session('user_avatar'));
        }
    }
}