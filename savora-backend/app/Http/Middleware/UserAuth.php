<?php

namespace App\Http\Middleware;

use App\Services\UserSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuth
{
    public function __construct(private UserSettingsService $settingsService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! session('user_id')) {
            // redirect()->guest() menyimpan intended URL ke session
            // sehingga setelah login bisa redirect()->intended() ke sini lagi
            return redirect()->guest(route('app.login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $this->loadUserSettingsIntoSession((string) session('user_id'));

        return $next($request);
    }

    private function loadUserSettingsIntoSession(string $userId): void
    {
        if (session('user_settings_loaded')) {
            return;
        }

        $settings = $this->settingsService->get($userId);

        session([
            'user_theme'           => $settings['theme'],
            'user_language'        => $settings['language'],
            'user_font_size'       => (int) $settings['font_size'],
            'user_settings'        => $settings,
            'user_settings_loaded' => true,
        ]);
    }
}
