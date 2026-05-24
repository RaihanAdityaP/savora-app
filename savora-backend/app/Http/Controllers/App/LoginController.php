<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(
        private SupabaseAuthService $supabaseAuth,
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
    ) {}

    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('app.home');
        }

        return view('app.auth.login', [
            'supabaseUrl' => rtrim((string) env('SUPABASE_URL'), '/'),
            'supabaseAnonKey' => env('SUPABASE_ANON_KEY'),
            'supabaseOAuthRedirectUrl' => rtrim((string) config('app.url'), '/') . '/app/login',
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email    = trim($request->input('email'));
        $password = $request->input('password');

        try {
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_ANON_KEY');

            $authResp = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
                'email'    => $email,
                'password' => $password,
            ]);

            if (! $authResp->successful()) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Email or password is incorrect.');
            }

            $userId = $authResp->json('user.id');
            if (! $userId) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Unable to load user data.');
            }

            $profiles = $this->getProfilesForUser($userId);

            if (empty($profiles)) {
                $this->ensureProfileForAuthUser(
                    $userId,
                    $email,
                    $authResp->json('user.user_metadata') ?? []
                );

                $profiles = $this->getProfilesForUser($userId);

                if (empty($profiles)) {
                    return back()->withInput(['email' => $email])
                        ->with('error', 'User profile was not found.');
                }
            }

            return $this->finishLoginWithProfile($profiles[0], ['email' => $email]);
        } catch (Exception $e) {
            return back()->withInput(['email' => $email])
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function loginWithSupabaseToken(Request $request)
    {
        $request->validate([
            'supabase_token' => 'required|string',
        ]);

        try {
            $userData = $this->supabaseAuth->validateToken($request->input('supabase_token'));

            if (! $userData || empty($userData['user_id'])) {
                return redirect()->route('app.login')
                    ->with('error', 'Google session is invalid. Please try again.');
            }

            $this->ensureProfileForAuthUser(
                $userData['user_id'],
                (string) ($userData['email'] ?? ''),
                $userData['metadata'] ?? []
            );

            $profiles = $this->getProfilesForUser($userData['user_id']);
            if (empty($profiles)) {
                return redirect()->route('app.login')
                    ->with('error', 'User profile was not found.');
            }

            return $this->finishLoginWithProfile($profiles[0]);
        } catch (Exception $e) {
            return redirect()->route('app.login')
                ->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }

    public function sendPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = trim($request->input('email'));

        try {
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_ANON_KEY');

            $response = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/recover", [
                'email'       => $email,
                'redirect_to' => route('app.login'),
            ]);

            if (! $response->successful()) {
                $msg = $response->json('msg') ?? $response->json('message') ?? 'Failed to send password reset email.';
                return back()->withInput(['reset_email' => $email])->with('error', $msg);
            }

            return back()->with('status', 'Password reset link has been sent to your email.');
        } catch (Exception $e) {
            return back()->withInput(['reset_email' => $email])
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = trim($request->input('email'));

        try {
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_ANON_KEY');

            $response = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/resend", [
                'type'  => 'signup',
                'email' => $email,
            ]);

            if (! $response->successful()) {
                $msg = $response->json('msg') ?? $response->json('message') ?? 'Failed to send verification email.';
                return back()->withInput(['email' => $email])->with('error', $msg);
            }

            return back()->with('status', 'Verification email has been sent.');
        } catch (Exception $e) {
            return back()->withInput(['email' => $email])
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget(['user_id', 'user_username', 'user_role', 'user_avatar']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('app.login')
            ->with('status', 'You have been logged out.');
    }

    public function showRegister()
    {
        if (session('user_id')) {
            return redirect()->route('app.home');
        }
        return view('app.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50',
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email',
            'password'  => 'required|string|min:6',
        ]);

        try {
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_ANON_KEY');

            $signupResp = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/signup", [
                'email'    => trim($request->input('email')),
                'password' => $request->input('password'),
                'data'     => [
                    'username'  => trim($request->input('username')),
                    'full_name' => trim($request->input('full_name')),
                ],
            ]);

            if (! $signupResp->successful()) {
                $msg = $signupResp->json('msg') ?? $signupResp->json('message') ?? 'Registration failed.';
                return back()->withInput()->with('error', $msg);
            }

            $userId = $signupResp->json('user.id');
            if ($userId) {
                $this->ensureProfileForAuthUser($userId, trim($request->input('email')), [
                    'username' => trim($request->input('username')),
                    'full_name' => trim($request->input('full_name')),
                ]);
            }

            return redirect()->route('app.login')
                ->with('status', 'Registration successful. Check your email to verify your account.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    private function getProfilesForUser(string $userId): array
    {
        return $this->supabase->select(
            'profiles',
            ['id', 'username', 'full_name', 'role', 'is_banned', 'banned_reason', 'avatar_url'],
            ['id' => $userId]
        );
    }

    private function finishLoginWithProfile(array $profile, array $withInput = [])
    {
        if ($profile['is_banned'] ?? false) {
            return back()->withInput($withInput)
                ->with('banned', true)
                ->with('banned_reason', $profile['banned_reason'] ?? 'Not specified');
        }

        if (($profile['role'] ?? '') === 'admin') {
            session()->regenerate();
            session([
                'admin_id'       => $profile['id'],
                'admin_username' => $profile['username'] ?? $profile['full_name'] ?? 'Admin',
                'admin_role'     => 'admin',
            ]);
            return redirect()->route('admin.dashboard');
        }

        session()->regenerate();
        session([
            'user_id'       => $profile['id'],
            'user_username' => $profile['username'] ?? $profile['full_name'] ?? 'User',
            'user_role'     => $profile['role'] ?? 'user',
            'user_avatar'   => $profile['avatar_url'] ?? null,
        ]);

        $settings = $this->settingsService->get($profile['id']);
        session([
            'user_theme'           => $settings['theme'],
            'user_language'        => $settings['language'],
            'user_font_size'       => (int) $settings['font_size'],
            'user_settings'        => $settings,
            'user_settings_loaded' => true,
        ]);

        return redirect()->intended(route('app.home'));
    }

    private function ensureProfileForAuthUser(string $userId, string $email, array $metadata = []): void
    {
        $existing = $this->supabase->select('profiles', ['id'], ['id' => $userId]);
        if (! empty($existing)) return;

        $username = trim((string) ($metadata['username'] ?? ''));
        if ($username === '') {
            $username = Str::of($email)->before('@')->slug('_')->limit(30, '')->toString();
        }
        if ($username === '') {
            $username = 'user_' . Str::lower(Str::random(8));
        }

        $this->supabase->insert('profiles', [
            'id' => $userId,
            'username' => $username,
            'full_name' => trim((string) ($metadata['full_name'] ?? $username)),
            'avatar_url' => $metadata['avatar_url'] ?? $metadata['picture'] ?? null,
            'role' => 'user',
            'is_premium' => false,
            'is_banned' => false,
            'social_links' => new \stdClass(),
            'cooking_level' => 'pemula',
            'total_followers' => 0,
            'total_following' => 0,
            'total_recipes' => 0,
            'total_bookmarks' => 0,
        ]);

        $settings = $this->settingsService->defaults();
        $settings['user_id'] = $userId;
        $this->supabase->insert('user_settings', $settings);
    }
}
