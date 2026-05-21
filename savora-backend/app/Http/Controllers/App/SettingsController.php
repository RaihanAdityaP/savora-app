<?php

namespace App\Http\Controllers\App;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\UserSettingsService;
use Exception;

class SettingsController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private UserSettingsService $settingsService,
    ) {}

    public function show()
    {
        $userId = session('user_id');
        $userSettings = $this->settingsService->get($userId);

        return view('app.settings', compact('userSettings'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'theme'            => 'required|in:dark,light',
            'language'         => 'required|in:en,id',
            'font_size'        => 'required|integer|between:12,18',
            'notify_likes'     => 'nullable|boolean',
            'notify_comments'  => 'nullable|boolean',
            'notify_follows'   => 'nullable|boolean',
            'allow_analytics'  => 'nullable|boolean',
            'profile_public'   => 'nullable|boolean',
            'auto_save_drafts' => 'nullable|boolean',
        ]);

        $userId = session('user_id');

        $data = [
            'user_id'          => $userId,
            'theme'            => $validated['theme'],
            'language'         => $validated['language'],
            'font_size'        => $validated['font_size'],
            'notify_likes'     => $request->boolean('notify_likes'),
            'notify_comments'  => $request->boolean('notify_comments'),
            'notify_follows'   => $request->boolean('notify_follows'),
            'allow_analytics'  => $request->boolean('allow_analytics'),
            'profile_public'   => $request->boolean('profile_public'),
            'auto_save_drafts' => $request->boolean('auto_save_drafts'),
            'updated_at'       => now()->toISOString(),
        ];

        try {
            $existing = $this->supabase->select('user_settings', ['user_id'], ['user_id' => $userId]);
            if (empty($existing)) {
                $this->supabase->insert('user_settings', $data);
            } else {
                unset($data['user_id']);
                $this->supabase->update('user_settings', $data, ['user_id' => $userId]);
            }

            // Sync ke session supaya app-theme langsung reflect
            session([
                'user_theme'           => $validated['theme'],
                'user_language'        => $validated['language'],
                'user_font_size'       => $validated['font_size'],
                'user_settings'        => $data + ['user_id' => $userId],
                'user_settings_loaded' => true,
            ]);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        $message = $validated['language'] === 'id'
            ? 'Pengaturan berhasil disimpan!'
            : 'Settings saved successfully!';

        return back()->with('status', $message);
    }
}
