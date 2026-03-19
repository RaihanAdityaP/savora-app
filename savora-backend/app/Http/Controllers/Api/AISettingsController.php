<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AISettingsController extends Controller
{
    private $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get settings AI milik user
     * GET /api/ai/settings
     */
    public function show(Request $request)
    {
        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);
            $settings = $this->supabase->select('user_ai_settings', ['*'], ['user_id' => $userId]);

            if (empty($settings)) {
                // Return default settings jika belum ada
                return response()->json([
                    'success' => true,
                    'data'    => $this->defaultSettings($userId),
                ]);
            }

            // Sembunyikan API key — hanya kirim status ada/tidak
            $data = $settings[0];
            $data['openrouter_api_key'] = !empty($data['openrouter_api_key']) ? '***SAVED***' : null;

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simpan/update settings AI user (upsert)
     * POST /api/ai/settings
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_active_provider'  => 'required|string|in:groq,openrouter',
            'groq_model'          => 'nullable|string|max:200',
            'openrouter_model'    => 'nullable|string|max:200',
            'openrouter_api_key'  => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);
            $existing = $this->supabase->select('user_ai_settings', ['id'], ['user_id' => $userId]);

            $data = [
                'user_id'            => $userId,
                'is_active_provider' => $request->input('is_active_provider'),
                'provider'           => $request->input('is_active_provider'),
                'groq_model'         => $request->input('groq_model', 'llama-3.3-70b-versatile'),
                'openrouter_model'   => $request->input('openrouter_model', 'meta-llama/llama-3.3-70b-instruct:free'),
            ];

            // Hanya update API key jika dikirim dan bukan placeholder
            $apiKey = $request->input('openrouter_api_key');
            if ($apiKey && $apiKey !== '***SAVED***') {
                $data['openrouter_api_key'] = $apiKey;
            }

            if (empty($existing)) {
                $result = $this->supabase->insert('user_ai_settings', $data);
            } else {
                unset($data['user_id']); // jangan update user_id
                $result = $this->supabase->update('user_ai_settings', $data, ['user_id' => $userId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test koneksi ke provider yang dipilih
     * POST /api/ai/settings/test
     */
    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider'           => 'required|string|in:groq,openrouter',
            'model'              => 'required|string',
            'openrouter_api_key' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);
            $provider = $request->input('provider');
            $model    = $request->input('model');

            // Ambil API key: dari request atau dari DB
            $apiKey = $request->input('openrouter_api_key');
            if (!$apiKey || $apiKey === '***SAVED***') {
                $savedSettings = $this->supabase->select('user_ai_settings', ['openrouter_api_key'], ['user_id' => $userId]);
                $apiKey = $savedSettings[0]['openrouter_api_key'] ?? null;
            }

            // Test dengan mengirim pesan sederhana
            $aiChat = app(\App\Services\AIChatService::class);
            $response = $aiChat->chat(
                messages: [['role' => 'user', 'content' => 'Hi, respond with just: OK']],
                provider: $provider,
                model   : $model,
                settings: [
                    'is_active_provider'  => $provider,
                    'openrouter_api_key'  => $apiKey,
                    'groq_model'          => $model,
                    'openrouter_model'    => $model,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'data'    => ['response' => $response],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset settings ke default
     * DELETE /api/ai/settings
     */
    public function reset(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $this->supabase->delete('user_ai_settings', ['user_id' => $userId]);

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to default',
                'data'    => $this->defaultSettings($userId),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function defaultSettings(string $userId): array
    {
        return [
            'user_id'             => $userId,
            'is_active_provider'  => 'groq',
            'provider'            => 'groq',
            'groq_model'          => 'llama-3.3-70b-versatile',
            'openrouter_model'    => 'meta-llama/llama-3.3-70b-instruct:free',
            'openrouter_api_key'  => null,
        ];
    }
}