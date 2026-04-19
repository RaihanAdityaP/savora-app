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
    public function __construct(private readonly SupabaseService $supabase)
    {
    }

    public function dashboard(): View
    {
        [$stats, $error] = $this->loadStats();

        return view('admin.dashboard', [
            'stats' => $stats,
            'error' => $error,
        ]);
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
                ['id', 'username', 'full_name', 'email', 'is_banned', 'banned_reason', 'is_premium', 'role', 'created_at'],
                [],
                ['order' => 'created_at.desc', 'limit' => 200, 'offset' => 0]
            ));

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $users = $users->filter(function (array $user) use ($needle) {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($user['username'] ?? ''),
                        (string) ($user['full_name'] ?? ''),
                        (string) ($user['email'] ?? ''),
                    ]));

                    return str_contains($haystack, $needle);
                })->values();
            }

            if ($status === 'active') {
                $users = $users->where('is_banned', '!=', true)->values();
            } elseif ($status === 'banned') {
                $users = $users->where('is_banned', true)->values();
            } elseif ($status === 'premium') {
                $users = $users->where('is_premium', true)->values();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.users', [
            'stats' => $stats,
            'error' => $error,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function recipes(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));

        [$stats, $error] = $this->loadStats();
        $recipes = collect();

        try {
            $filters = [];
            if ($status !== 'all') {
                $filters['status'] = $status;
            }

            $recipes = collect($this->supabase->select(
                'recipes',
                ['id', 'title', 'description', 'ingredients', 'steps', 'status', 'created_at', 'profiles!recipes_user_id_fkey(username)'],
                $filters,
                ['order' => 'created_at.desc', 'limit' => 100, 'offset' => 0]
            ));

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $recipes = $recipes->filter(function (array $recipe) use ($needle) {
                    $haystack = mb_strtolower((string) ($recipe['title'] ?? '') . ' ' . (string) ($recipe['description'] ?? ''));
                    return str_contains($haystack, $needle);
                })->values();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.recipes', [
            'stats' => $stats,
            'error' => $error,
            'recipes' => $recipes,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function logs(Request $request): View
    {
        $action = (string) $request->query('action', 'all');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 20;

        [$stats, $error] = $this->loadStats();
        $logs = collect();
        $paginator = new LengthAwarePaginator([], 0, $perPage, $page, ['path' => route('admin.logs')]);

        try {
            $offset = ($page - 1) * $perPage;
            $allLogsForFilter = collect($this->supabase->select(
                'activity_logs',
                ['*', 'profiles:user_id(username)'],
                [],
                ['order' => 'created_at.desc', 'limit' => 200, 'offset' => 0]
            ));

            $filtered = $action === 'all'
                ? $allLogsForFilter
                : $allLogsForFilter->where('action', $action)->values();

            $logs = $filtered->slice($offset, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $logs,
                $filtered->count(),
                $perPage,
                $page,
                [
                    'path' => route('admin.logs'),
                    'query' => ['action' => $action],
                ]
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.logs', [
            'stats' => $stats,
            'error' => $error,
            'logs' => $logs,
            'paginator' => $paginator,
            'filters' => [
                'action' => $action,
            ],
            'availableActions' => $this->extractActions($logs),
        ]);
    }

    public function toggleUserBan(Request $request, string $id): RedirectResponse
    {
        $isBanned = $request->boolean('is_banned');
        $reasonType = (string) $request->input('reason_type', 'other');
        $customReason = trim((string) $request->input('reason', ''));
        $reasonText = $this->banReasonFromType($reasonType, $customReason);

        try {
            if ($isBanned) {
                $this->supabase->update('profiles', [
                    'is_banned' => false,
                    'banned_reason' => null,
                    'banned_at' => null,
                    'banned_by' => null,
                ], ['id' => $id], true);
            } else {
                $this->supabase->update('profiles', [
                    'is_banned' => true,
                    'banned_reason' => $reasonText,
                    'banned_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'banned_by' => 'web_admin_dashboard',
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
            if (empty($users)) {
                return back()->with('error', 'User tidak ditemukan.');
            }

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
            $payload = [
                'moderated_by' => 'web_admin_dashboard',
                'moderated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            if ($action === 'approved') {
                $payload['status'] = 'approved';
                $payload['rejection_reason'] = null;
            } else {
                $payload['status'] = 'rejected';
                $payload['rejection_reason'] = $reason;
            }

            $this->supabase->update('recipes', $payload, ['id' => $id], true);

            return back()->with('status', $action === 'approved' ? 'Resep disetujui.' : 'Resep ditolak.');
        } catch (Exception $e) {
            return back()->with('error', 'Moderasi resep gagal: ' . $e->getMessage());
        }
    }

    private function loadStats(): array
    {
        $stats = [
            'total_users' => 0,
            'banned_users' => 0,
            'pending_recipes' => 0,
            'total_recipes' => 0,
        ];
        $error = null;

        try {
            $stats['total_users'] = count($this->supabase->select('profiles', ['id']));
            $stats['banned_users'] = count($this->supabase->select('profiles', ['id'], ['is_banned' => true]));
            $stats['pending_recipes'] = count($this->supabase->select('recipes', ['id'], ['status' => 'pending']));
            $stats['total_recipes'] = count($this->supabase->select('recipes', ['id']));
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return [$stats, $error];
    }

    private function banReasonFromType(string $type, string $customReason): string
    {
        return match ($type) {
            'spam' => 'Spam',
            'inappropriate_content' => 'Inappropriate Content',
            'harassment' => 'Harassment',
            'fake_account' => 'Fake Account',
            default => $customReason !== '' ? $customReason : 'Moderated from web dashboard',
        };
    }

    private function extractActions(Collection $logs): array
    {
        return $logs->pluck('action')->filter()->unique()->values()->all();
    }
}