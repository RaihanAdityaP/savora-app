<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Force HTTPS when behind Railway's reverse proxy
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ── Admin panel shared vars ──────────────────────────────
        if (request()->is('admin*')) {
            $pendingTagCount    = 0;
            $pendingRecipeCount = 0;

            try {
                $supabase           = app(SupabaseService::class);
                $pendingTagCount    = Cache::remember('admin_pending_tag_count', 60, fn () => $supabase->count('tags', ['is_approved' => false]));
                $pendingRecipeCount = Cache::remember('admin_pending_recipe_count', 60, fn () => $supabase->count('recipes', ['status' => 'pending']));
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
                    $appUnreadCount = Cache::remember("app_unread_count:{$userId}", 30, function () use ($userId) {
                        $supabase = app(SupabaseService::class);
                        $notifications = $supabase->select(
                            'notifications',
                            ['type', 'related_entity_type', 'related_entity_id', 'is_read'],
                            ['user_id' => $userId],
                            ['order' => 'created_at.desc', 'limit' => 50]
                        );

                        $unread = 0;
                        $seen = [];
                        foreach ($notifications as $notification) {
                            $key = implode('|', [
                                $notification['type'] ?? '',
                                $notification['related_entity_type'] ?? '',
                                $notification['related_entity_id'] ?? '',
                            ]);

                            if (isset($seen[$key])) continue;
                            $seen[$key] = true;

                            if (! ($notification['is_read'] ?? false)) {
                                $unread++;
                            }
                        }
                        return $unread;
                    });
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
