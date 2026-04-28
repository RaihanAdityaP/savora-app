<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Exception;

class NotificationController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    // GET /app/notifications
    public function index()
    {
        $userId        = session('user_id');
        $notifications = [];

        try {
            $raw = $this->supabase->select(
                'notifications',
                ['*'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc', 'limit' => 50]
            );

            // Dedupe by type+entity
            $seen = [];
            foreach ($raw as $n) {
                $key = implode('|', [
                    $n['type'] ?? '',
                    $n['related_entity_type'] ?? '',
                    $n['related_entity_id'] ?? '',
                ]);
                if (! isset($seen[$key])) {
                    $seen[$key]      = true;
                    $notifications[] = $n;
                }
            }
        } catch (Exception) {}

        $unreadCount = count(array_filter($notifications, fn($n) => ! ($n['is_read'] ?? false)));

        return view('app.notifications', compact('notifications', 'unreadCount'));
    }

    // POST /app/notifications/{id}/read
    public function markAsRead(string $id)
    {
        try {
            $this->supabase->update('notifications', ['is_read' => true], ['id' => $id]);
        } catch (Exception) {}

        return back();
    }

    // POST /app/notifications/read-all
    public function markAllAsRead()
    {
        try {
            $userId = session('user_id');
            $this->supabase->update('notifications', ['is_read' => true], ['user_id' => $userId, 'is_read' => false]);
        } catch (Exception) {}

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    // POST /app/notifications/{id}/delete
    public function destroy(string $id)
    {
        try {
            $this->supabase->delete('notifications', ['id' => $id]);
        } catch (Exception) {}

        return back()->with('status', 'Notifikasi dihapus.');
    }

    // POST /app/notifications/delete-all
    public function destroyAll()
    {
        try {
            $userId = session('user_id');
            $this->supabase->delete('notifications', ['user_id' => $userId]);
        } catch (Exception) {}

        return back()->with('status', 'Semua notifikasi dihapus.');
    }
}