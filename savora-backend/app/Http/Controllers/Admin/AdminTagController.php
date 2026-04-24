<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Exception;

class AdminTagController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * GET /admin/tags
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status', 'pending'), // pending | approved | all
            'search' => $request->input('search', ''),
        ];

        $error = null;
        $tags  = collect();

        try {
            $dbFilters = [];

            if ($filters['status'] === 'pending') {
                $dbFilters['is_approved'] = false;
            } elseif ($filters['status'] === 'approved') {
                $dbFilters['is_approved'] = true;
            }
            // 'all' → no filter

            $rows = $this->supabase->select(
                'tags',
                ['*', 'profiles:created_by(username, avatar_url)'],
                $dbFilters,
                ['order' => 'created_at.desc', 'limit' => 200]
            );

            // PHP-side search filter
            if ($filters['search'] !== '') {
                $needle = strtolower($filters['search']);
                $rows   = array_values(array_filter($rows, function ($tag) use ($needle) {
                    return str_contains(strtolower($tag['name'] ?? ''), $needle);
                }));
            }

            $tags = collect($rows);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        // Count pending for badge on dashboard / nav
        $pendingCount = 0;
        try {
            $pendingCount = count($this->supabase->select('tags', ['id'], ['is_approved' => false]));
        } catch (Exception) {}

        return view('admin.tags', compact('tags', 'filters', 'error', 'pendingCount'));
    }

    /**
     * POST /admin/tags/{id}/moderate
     * Expects: action = approved | rejected
     */
    public function moderate(Request $request, $id)
    {
        $action = $request->input('action');

        if (!in_array($action, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Invalid action.');
        }

        try {
            if ($action === 'approved') {
                $this->supabase->update('tags', [
                    'is_approved' => true,
                    'approved_at' => now()->toDateTimeString(),
                ], ['id' => $id]);

                // Log activity
                $this->logActivity('approve_tag', $id);

                return back()->with('status', 'Tag approved successfully.');
            }

            // rejected → just delete the tag (it has no recipes attached yet if still pending)
            $this->supabase->delete('tags', ['id' => $id]);

            $this->logActivity('reject_tag', $id);

            return back()->with('status', 'Tag rejected and removed.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * POST /admin/tags/{id}/delete  (force delete an approved tag)
     */
    public function destroy($id)
    {
        try {
            // Detach from recipes first
            $this->supabase->delete('recipe_tags', ['tag_id' => $id]);
            $this->supabase->delete('tags', ['id' => $id]);

            $this->logActivity('delete_tag', $id);

            return back()->with('status', 'Tag deleted.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function logActivity(string $action, $tagId): void
    {
        try {
            $this->supabase->insert('activity_logs', [
                'user_id'     => session('admin_id'),
                'action'      => $action,
                'entity_type' => 'tag',
                'entity_id'   => (string) $tagId,
            ]);
        } catch (Exception) {}
    }
}