<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function __construct(
        private readonly SupabaseService $supabase,
        private readonly NotificationService $notification,
    ) {}

    public function dashboard(): View
    {
        [$stats, $error] = $this->loadStats();
        return view('admin.dashboard', compact('stats', 'error'));
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        [$stats, $error] = $this->loadStats();
        $users = collect();

        try {
            $users = collect($this->supabase->select(
                'profiles',
                ['id', 'username', 'full_name', 'is_banned', 'banned_reason', 'is_premium', 'role', 'avatar_url', 'created_at'],
                [],
                ['order' => 'created_at.desc', 'limit' => 200, 'offset' => 0]
            ));

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $users = $users->filter(fn($u) =>
                    str_contains(mb_strtolower(($u['username'] ?? '') . ' ' . ($u['full_name'] ?? '')), $needle)
                )->values();
            }

            $users = match ($status) {
                'active'  => $users->where('is_banned', '!=', true)->values(),
                'banned'  => $users->where('is_banned', true)->values(),
                'premium' => $users->where('is_premium', true)->values(),
                default   => $users,
            };
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.users', compact('stats', 'error', 'users') + ['filters' => compact('search', 'status')]);
    }

    public function recipes(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));
        [$stats, $error] = $this->loadStats();
        $recipes = collect();

        try {
            $filters = $status !== 'all' ? ['status' => $status] : [];
            $recipes = collect($this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'ingredients', 'steps', 'status', 'image_url', 'cooking_time', 'servings', 'difficulty', 'created_at', 'profiles!recipes_user_id_fkey(username, avatar_url)'],
                $filters,
                ['order' => 'created_at.desc', 'limit' => 100, 'offset' => 0]
            ));

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $recipes = $recipes->filter(fn($r) =>
                    str_contains(mb_strtolower(($r['title'] ?? '') . ' ' . ($r['description'] ?? '')), $needle)
                )->values();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.recipes', compact('stats', 'error', 'recipes') + ['filters' => compact('status', 'search')]);
    }

    public function logs(Request $request): View
    {
        $action  = (string) $request->query('action', 'all');
        $page    = max((int) $request->query('page', 1), 1);
        $perPage = 20;
        [$stats, $error] = $this->loadStats();
        $logs = collect();
        $paginator = new LengthAwarePaginator([], 0, $perPage, $page, ['path' => route('admin.logs')]);

        try {
            $all = collect($this->supabase->select(
                'activity_logs',
                ['*', 'profiles:user_id(username)'],
                [],
                ['order' => 'created_at.desc', 'limit' => 200, 'offset' => 0]
            ));
            $filtered = $action === 'all' ? $all : $all->where('action', $action)->values();
            $logs = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
            $paginator = new LengthAwarePaginator(
                $logs, $filtered->count(), $perPage, $page,
                ['path' => route('admin.logs'), 'query' => ['action' => $action]]
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.logs', compact('stats', 'error', 'logs', 'paginator') + [
            'filters'          => ['action' => $action],
            'availableActions' => $this->extractActions($logs),
        ]);
    }

    public function broadcast(): View
    {
        [$stats, $error] = $this->loadStats();
        $users = collect();

        try {
            $users = collect($this->supabase->select(
                'profiles',
                ['id', 'username', 'full_name'],
                ['is_banned' => false],
                ['order' => 'username.asc', 'limit' => 300]
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.broadcast', compact('stats', 'error', 'users'));
    }

    public function sendBroadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audience' => 'required|in:all,user',
            'user_id' => 'nullable|uuid',
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:500',
            'route' => 'nullable|in:home,recipe,profile',
            'related_entity_id' => 'nullable|string|max:120',
        ]);

        try {
            $targetIds = [];

            if ($validated['audience'] === 'user') {
                if (empty($validated['user_id'])) {
                    return back()->withInput()->with('error', 'Pilih user tujuan.');
                }
                $targetIds = [$validated['user_id']];
            } else {
                $targetIds = collect($this->supabase->select('profiles', ['id'], ['is_banned' => false], ['limit' => 1000]))
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all();
            }

            $route = $validated['route'] ?? 'home';
            $entityId = $validated['related_entity_id'] ?? '';
            $sent = $this->sendManualNotification($targetIds, $validated['title'], $validated['message'], $route, $entityId);

            return back()->with('status', "Broadcast dibuat untuk {$sent} user.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Broadcast gagal: ' . $e->getMessage());
        }
    }

    public function toggleUserBan(Request $request, string $id): RedirectResponse
    {
        $isBanned     = $request->boolean('is_banned');
        $reasonType   = (string) $request->input('reason_type', 'other');
        $customReason = trim((string) $request->input('reason', ''));
        $reasonText   = $this->banReasonFromType($reasonType, $customReason);

        // Admin UUID from session — profiles.banned_by is a UUID FK, cannot be a string literal
        $adminId = session('admin_id');

        try {
            if ($isBanned) {
                $this->supabase->update('profiles', [
                    'is_banned'     => false,
                    'banned_reason' => null,
                    'banned_at'     => null,
                    'banned_by'     => null,
                ], ['id' => $id], true);
            } else {
                $this->supabase->update('profiles', [
                    'is_banned'     => true,
                    'banned_reason' => $reasonText,
                    'banned_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                    'banned_by'     => $adminId,  // UUID FK — must be a valid profiles.id
                ], ['id' => $id], true);
            }

            return back()->with('status', $isBanned ? 'User berhasil di-unban.' : 'User berhasil di-ban.');
        } catch (Exception $e) {
            return back()->with('error', 'Aksi gagal: ' . $e->getMessage());
        }
    }

    public function togglePremium(string $id): RedirectResponse
    {
        try {
            $users = $this->supabase->select('profiles', ['id', 'is_premium'], ['id' => $id], ['limit' => 1]);
            if (empty($users)) return back()->with('error', 'User tidak ditemukan.');
            $current = (bool) ($users[0]['is_premium'] ?? false);
            $this->supabase->update('profiles', ['is_premium' => !$current], ['id' => $id], true);
            return back()->with('status', !$current ? 'Premium diaktifkan.' : 'Premium dinonaktifkan.');
        } catch (Exception $e) {
            return back()->with('error', 'Toggle premium gagal: ' . $e->getMessage());
        }
    }

    public function moderateRecipe(Request $request, string $id): RedirectResponse
    {
        $action = (string) $request->input('action', 'approved');
        $reason = trim((string) $request->input('reason', 'Does not meet the standards'));

        // Admin UUID from session — recipes.moderated_by is a UUID FK to profiles(id)
        $adminId = session('admin_id');

        try {
            $recipeRows = $this->supabase->select('recipes', ['user_id', 'title'], ['id' => $id], ['limit' => 1]);

            if ($action === 'approved') {
                $this->supabase->update('recipes', [
                    'status'           => 'approved',
                    'rejection_reason' => null,
                    'moderated_by'     => $adminId,  // UUID FK
                    'moderated_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                ], ['id' => $id], true);
            } else {
                $this->supabase->update('recipes', [
                    'status'           => 'rejected',
                    'rejection_reason' => $reason,
                    'moderated_by'     => $adminId,  // UUID FK
                    'moderated_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                ], ['id' => $id], true);
            }

            if (! empty($recipeRows)) {
                $ownerId = $recipeRows[0]['user_id'] ?? null;
                $title = $recipeRows[0]['title'] ?? 'your recipe';

                if ($ownerId) {
                    $type = $action === 'approved' ? 'recipe_approved' : 'recipe_rejected';
                    $notificationTitle = $action === 'approved' ? 'Recipe Approved' : 'Recipe Rejected';
                    $message = $action === 'approved'
                        ? "Your recipe '{$title}' was approved and published!"
                        : "Your recipe '{$title}' was rejected. Reason: {$reason}";

                    $this->supabase->insert('notifications', [
                        'user_id'             => $ownerId,
                        'type'                => $type,
                        'title'               => $notificationTitle,
                        'message'             => $message,
                        'related_entity_type' => 'recipe',
                        'related_entity_id'   => $id,
                    ]);

                    $tokens = $this->supabase->select('device_tokens', ['token'], [
                        'user_id' => $ownerId,
                        'is_active' => true,
                    ]);

                    if (! empty($tokens)) {
                        $this->notification->sendToMultipleDevices(
                            array_column($tokens, 'token'),
                            $notificationTitle,
                            $message,
                            $this->notification->generatePayload($type, $id)
                        );
                    }
                }
            }

            return back()->with('status', $action === 'approved' ? 'Recipe approved.' : 'Recipe rejected.');
        } catch (Exception $e) {
            return back()->with('error', 'Recipe moderation failed: ' . $e->getMessage());
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loadStats(): array
    {
        $stats = [
            'total_users'     => 0,
            'banned_users'    => 0,
            'pending_recipes' => 0,
            'total_recipes'   => 0,
            'pending_tags'    => 0,
        ];
        $error = null;

        try {
            $stats['total_users']     = count($this->supabase->select('profiles', ['id']));
            $stats['banned_users']    = count($this->supabase->select('profiles', ['id'], ['is_banned' => true]));
            $stats['pending_recipes'] = count($this->supabase->select('recipes',  ['id'], ['status' => 'pending']));
            $stats['total_recipes']   = count($this->supabase->select('recipes',  ['id']));
            $stats['pending_tags']    = count($this->supabase->select('tags',     ['id'], ['is_approved' => false]));
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return [$stats, $error];
    }

    private function sendManualNotification(array $userIds, string $title, string $message, string $route, string $entityId = ''): int
    {
        $sent = 0;
        $payload = ['route' => $route, 'id' => $entityId];

        foreach (array_unique($userIds) as $userId) {
            if (! $userId) continue;

            $this->supabase->insert('notifications', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => 'admin',
                'related_entity_type' => $route === 'home' ? null : $route,
                'related_entity_id' => $entityId ?: null,
                'is_read' => false,
            ]);

            try {
                $tokens = $this->supabase->select('device_tokens', ['token'], [
                    'user_id' => $userId,
                    'is_active' => true,
                ]);

                if (! empty($tokens)) {
                    $this->notification->sendToMultipleDevices(array_column($tokens, 'token'), $title, $message, $payload);
                }
            } catch (Exception) {
            }

            $sent++;
        }

        return $sent;
    }

    private function banReasonFromType(string $type, string $customReason): string
    {
        return match ($type) {
            'spam'                  => 'Spam',
            'inappropriate_content' => 'Inappropriate Content',
            'harassment'            => 'Harassment',
            'fake_account'          => 'Fake Account',
            default                 => $customReason !== '' ? $customReason : 'Moderated from web dashboard',
        };
    }

    private function extractActions(Collection $logs): array
    {
        return $logs->pluck('action')->filter()->unique()->values()->all();
    }
}
