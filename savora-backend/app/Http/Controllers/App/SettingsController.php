<?php

namespace App\Http\Controllers\App;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    /**
     * Show the user settings page.
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        
        // Get user settings from session or database
        $userSettings = [
            'theme' => session('user_theme', 'light'),
            'language' => session('user_language', 'en'),
            'font_size' => session('user_font_size', 14),
            'notify_likes' => session('notify_likes', true),
            'notify_comments' => session('notify_comments', true),
            'notify_follows' => session('notify_follows', true),
            'notify_email' => session('notify_email', false),
            'allow_analytics' => session('allow_analytics', true),
            'profile_public' => session('profile_public', true),
            'auto_save_drafts' => session('auto_save_drafts', true),
        ];

        return view('app.settings', compact('userSettings'));
    }

    /**
     * Save user settings.
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:dark,light',
            'language' => 'required|in:en,id',
            'font_size' => 'required|integer|between:12,18',
            'notify_likes' => 'boolean',
            'notify_comments' => 'boolean',
            'notify_follows' => 'boolean',
            'notify_email' => 'boolean',
            'allow_analytics' => 'boolean',
            'profile_public' => 'boolean',
            'auto_save_drafts' => 'boolean',
        ]);

        // Store settings in session
        session([
            'user_theme' => $validated['theme'],
            'user_language' => $validated['language'],
            'user_font_size' => $validated['font_size'],
            'notify_likes' => $validated['notify_likes'] ?? false,
            'notify_comments' => $validated['notify_comments'] ?? false,
            'notify_follows' => $validated['notify_follows'] ?? false,
            'notify_email' => $validated['notify_email'] ?? false,
            'allow_analytics' => $validated['allow_analytics'] ?? false,
            'profile_public' => $validated['profile_public'] ?? false,
            'auto_save_drafts' => $validated['auto_save_drafts'] ?? false,
        ]);

        $message = $validated['language'] === 'id'
            ? 'Pengaturan berhasil disimpan!'
            : 'Settings saved successfully!';

        return back()->with('status', $message);
    }
}
