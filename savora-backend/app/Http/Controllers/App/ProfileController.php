<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class ProfileController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    // GET /app/profile atau /app/profile/{userId}
    public function show(?string $userId = null)
    {
        $currentUserId = session('user_id');
        $targetId      = $userId ?? $currentUserId;
        $isOwnProfile  = $targetId === $currentUserId;

        try {
            $profiles = $this->supabase->select(
                'profiles',
                ['*'],
                ['id' => $targetId]
            );

            if (empty($profiles)) abort(404, 'User tidak ditemukan.');

            $profile = $profiles[0];

            // Stats
            $recipesCount  = 0;
            $followersCount = 0;
            $followingCount = 0;

            try { $recipesCount  = count($this->supabase->select('recipes', ['id'], ['user_id' => $targetId, 'status' => 'approved'])); } catch (Exception) {}
            try { $followersCount = count($this->supabase->select('follows', ['follower_id'], ['following_id' => $targetId])); } catch (Exception) {}
            try { $followingCount = count($this->supabase->select('follows', ['following_id'], ['follower_id' => $targetId])); } catch (Exception) {}

            // Resep user
            $recipes = [];
            try {
                $recipes = $this->supabase->select(
                    'recipes',
                    ['*', 'categories(name)', 'recipe_tags(tags(id, name))'],
                    ['user_id' => $targetId, 'status' => 'approved'],
                    ['order' => 'created_at.desc', 'limit' => 20]
                );
            } catch (Exception) {}

            // Follow status
            $isFollowing = false;
            if (! $isOwnProfile && $currentUserId) {
                try {
                    $check = $this->supabase->select('follows', ['id'], [
                        'follower_id'  => $currentUserId,
                        'following_id' => $targetId,
                    ]);
                    $isFollowing = ! empty($check);
                } catch (Exception) {}
            }

            return view('app.profile', compact(
                'profile', 'recipes', 'isOwnProfile',
                'recipesCount', 'followersCount', 'followingCount',
                'isFollowing', 'currentUserId'
            ));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // PUT /app/profile
    public function update(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50',
            'full_name' => 'nullable|string|max:100',
            'bio'       => 'nullable|string|max:500',
            'avatar'    => 'nullable|image|max:2048',
        ]);

        try {
            $userId = session('user_id');
            $data   = [
                'username'  => trim($request->input('username')),
                'full_name' => trim($request->input('full_name', '')),
                'bio'       => trim($request->input('bio', '')),
            ];

            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $path   = 'avatars/' . Str::uuid() . '.' . $avatar->getClientOriginalExtension();
                $this->supabase->uploadFile('avatars', $path, file_get_contents($avatar->getRealPath()), $avatar->getMimeType());
                $data['avatar_url'] = $this->supabase->getPublicUrl('avatars', $path);

                // Update session avatar
                session(['user_avatar' => $data['avatar_url']]);
            }

            $this->supabase->update('profiles', $data, ['id' => $userId]);
            session(['user_username' => $data['username']]);

            return back()->with('status', 'Profil berhasil diperbarui.');

        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/profile/{userId}/follow
    public function follow(string $userId)
    {
        try {
            $currentUserId = session('user_id');
            if ($currentUserId === $userId) return back()->with('error', 'Tidak bisa follow diri sendiri.');

            $existing = $this->supabase->select('follows', ['id'], [
                'follower_id'  => $currentUserId,
                'following_id' => $userId,
            ]);

            if (empty($existing)) {
                $this->supabase->insert('follows', [
                    'follower_id'  => $currentUserId,
                    'following_id' => $userId,
                ]);
            }

            return back()->with('status', 'Berhasil mengikuti.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/profile/{userId}/unfollow
    public function unfollow(string $userId)
    {
        try {
            $currentUserId = session('user_id');
            $this->supabase->delete('follows', [
                'follower_id'  => $currentUserId,
                'following_id' => $userId,
            ]);

            return back()->with('status', 'Berhenti mengikuti.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}