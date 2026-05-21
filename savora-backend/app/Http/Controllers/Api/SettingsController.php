<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SettingsController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
    ) {}

    public function show(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            return response()->json([
                'success' => true,
                'data' => $this->settingsService->get($userId),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme'            => 'required|in:dark,light',
            'language'         => 'required|in:en,id',
            'font_size'        => 'required|integer|between:12,18',
            'notify_likes'     => 'required|boolean',
            'notify_comments'  => 'required|boolean',
            'notify_follows'   => 'required|boolean',
            'allow_analytics'  => 'required|boolean',
            'profile_public'   => 'required|boolean',
            'auto_save_drafts' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $data = $validator->validated() + [
                'user_id' => $userId,
                'updated_at' => now()->toISOString(),
            ];

            $existing = $this->supabase->select('user_settings', ['user_id'], ['user_id' => $userId]);
            if (empty($existing)) {
                $this->supabase->insert('user_settings', $data);
            } else {
                $updateData = $data;
                unset($updateData['user_id']);
                $this->supabase->update('user_settings', $updateData, ['user_id' => $userId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
