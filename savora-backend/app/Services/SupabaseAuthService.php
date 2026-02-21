<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Exception;

class SupabaseAuthService
{
    private $jwtSecret;

    public function __construct()
    {
        // JWT Secret dari Supabase Dashboard > Settings > API
        $this->jwtSecret = env('SUPABASE_JWT_SECRET');
    }

    /**
     * Validate Supabase JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            return [
                'user_id' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'role' => $decoded->role ?? 'authenticated',
                'exp' => $decoded->exp,
            ];
        } catch (Exception $e) {
            Log::error('JWT Validation Failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user from Supabase by ID
     */
    public function getUserById(string $userId): ?array
    {
        $supabase = app(SupabaseService::class);
        
        try {
            $users = $supabase->select('profiles', ['*'], ['id' => $userId]);
            return !empty($users) ? $users[0] : null;
        } catch (Exception $e) {
            Log::error('Get User Failed: ' . $e->getMessage());
            return null;
        }
    }
}