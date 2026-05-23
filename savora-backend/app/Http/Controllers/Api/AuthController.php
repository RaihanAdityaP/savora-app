<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private $supabaseAuth;

    public function __construct(
        SupabaseAuthService $supabaseAuth,
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
    ) {
        $this->supabaseAuth = $supabaseAuth;
    }

    /**
     * Exchange Supabase JWT for Sanctum token
     * POST /api/auth/token
     */
    public function exchangeToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supabase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate Supabase JWT
        $userData = $this->supabaseAuth->validateToken($request->supabase_token);

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        $this->ensureProfileForAuthUser(
            $userData['user_id'],
            (string) ($userData['email'] ?? ''),
            $userData['metadata'] ?? []
        );

        // Get or create Laravel user
        $user = User::firstOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['email'],
                'password' => bcrypt(str()->random(32)), // Random password
                'supabase_user_id' => $userData['user_id'],
            ]
        );

        // Keep mapping updated for existing users created before this field existed
        if ($user->supabase_user_id !== $userData['user_id']) {
            $user->supabase_user_id = $userData['user_id'];
            $user->save();
        }

        // Create Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'sanctum_token' => $token,
                'user' => [
                    'id' => $userData['user_id'],
                    'email' => $userData['email'],
                    'role' => $userData['role'],
                ],
            ],
        ]);
    }

    /**
     * Validate current token
     * GET /api/auth/me
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Logout (revoke token)
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
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
