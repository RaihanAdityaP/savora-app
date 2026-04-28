<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function __construct(private readonly SupabaseService $supabase) {}

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
            // FIX: 'email' does not exist on profiles table — removed.
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

            $users = match($status) {
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

    public function toggleUserBan(Request $request, string $id): RedirectResponse
    {
        $isBanned   = $request->boolean('is_banned');
        $reasonType = (string) $request->input('reason_type', 'other');
        $customReason = trim((string) $request->input('reason', ''));
        $reasonText = $this->banReasonFromType($reasonType, $customReason);

        try {
            if ($isBanned) {
                $this->supabase->update('profiles', ['is_banned' => false, 'banned_reason' => null, 'banned_at' => null, 'banned_by' => null], ['id' => $id], true);
            } else {
                $this->supabase->update('profiles', ['is_banned' => true, 'banned_reason' => $reasonText, 'banned_at' => Carbon::now()->format('Y-m-d H:i:s'), 'banned_by' => 'web_admin_dashboard'], ['id' => $id], true);
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
        $reason = trim((string) $request->input('reason', 'Tidak memenuhi standar'));
        try {
            $payload = ['moderated_by' => 'web_admin_dashboard', 'moderated_at' => Carbon::now()->format('Y-m-d H:i:s')];
            $action === 'approved'
                ? ($payload['status'] = 'approved') && ($payload['rejection_reason'] = null)
                : ($payload['status'] = 'rejected') && ($payload['rejection_reason'] = $reason);
            $this->supabase->update('recipes', $payload, ['id' => $id], true);
            return back()->with('status', $action === 'approved' ? 'Resep disetujui.' : 'Resep ditolak.');
        } catch (Exception $e) {
            return back()->with('error', 'Moderasi resep gagal: ' . $e->getMessage());
        }
    }

    private function loadStats(): array
    {
        $stats = ['total_users' => 0, 'banned_users' => 0, 'pending_recipes' => 0, 'total_recipes' => 0, 'pending_tags' => 0];
        $error = null;
        try {
            $stats['total_users']     = count($this->supabase->select('profiles', ['id']));
            $stats['banned_users']    = count($this->supabase->select('profiles', ['id'], ['is_banned' => true]));
            $stats['pending_recipes'] = count($this->supabase->select('recipes', ['id'], ['status' => 'pending']));
            $stats['total_recipes']   = count($this->supabase->select('recipes', ['id']));
            $stats['pending_tags']    = count($this->supabase->select('tags', ['id'], ['is_approved' => false]));
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        return [$stats, $error];
    }

    private function banReasonFromType(string $type, string $customReason): string
    {
        return match ($type) {
            'spam'                 => 'Spam',
            'inappropriate_content'=> 'Inappropriate Content',
            'harassment'           => 'Harassment',
            'fake_account'         => 'Fake Account',
            default                => $customReason !== '' ? $customReason : 'Moderated from web dashboard',
        };
    }

    private function extractActions(Collection $logs): array
    {
        return $logs->pluck('action')->filter()->unique()->values()->all();
    }
}