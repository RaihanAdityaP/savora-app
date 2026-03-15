<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SupabaseAuthService
{
    private $jwtSecret;
    private $supabaseUrl;

    public function __construct()
    {
        $this->jwtSecret = env('SUPABASE_JWT_SECRET');
        $this->supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
    }

    /**
     * Validate Supabase JWT token
     * Supports both HS256 (email/password) and RS256 (Google OAuth)
     */
    public function validateToken(string $token): ?array
    {
        // Peek at header to determine algorithm
        $algorithm = $this->getTokenAlgorithm($token);

        if ($algorithm === 'RS256') {
            return $this->validateRS256Token($token);
        }

        return $this->validateHS256Token($token);
    }

    /**
     * Validate HS256 token (email/password login)
     */
    private function validateHS256Token(string $token): ?array
    {
        try {
            JWT::$leeway = 60;
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            return [
                'user_id' => $decoded->sub,
                'email'   => $decoded->email ?? null,
                'role'    => $decoded->role ?? 'authenticated',
                'exp'     => $decoded->exp,
            ];
        } catch (Exception $e) {
            Log::error('HS256 JWT Validation Failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate RS256 token (Google OAuth / social login)
     * Fetches Supabase JWKS and caches for 1 hour
     */
    private function validateRS256Token(string $token): ?array
    {
        try {
            JWT::$leeway = 60;
            $jwks = $this->getJwks();
            if (empty($jwks)) {
                Log::error('RS256 Validation Failed: Could not fetch JWKS');
                return null;
            }

            $keys = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);

            return [
                'user_id' => $decoded->sub,
                'email'   => $decoded->email ?? null,
                'role'    => $decoded->role ?? 'authenticated',
                'exp'     => $decoded->exp,
            ];
        } catch (Exception $e) {
            Log::error('RS256 JWT Validation Failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch Supabase JWKS (cached 1 hour)
     */
    private function getJwks(): ?array
    {
        return Cache::remember('supabase_jwks', 3600, function () {
            try {
                $url = $this->supabaseUrl . '/.well-known/jwks.json';
                $response = file_get_contents($url);
                if ($response === false) {
                    return null;
                }
                return json_decode($response, true);
            } catch (Exception $e) {
                Log::error('Failed to fetch Supabase JWKS: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Peek at JWT header to get algorithm without full validation
     */
    private function getTokenAlgorithm(string $token): string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return 'HS256';
            }
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            return $header['alg'] ?? 'HS256';
        } catch (Exception $e) {
            return 'HS256';
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