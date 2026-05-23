<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private NotificationService $notification,
    ) {}

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
                    if (
                        ($n['type'] ?? '') === 'follow_request' &&
                        ! empty($n['related_entity_id'])
                    ) {
                        try {
                            $requests = $this->supabase->select('follow_requests', [
                                'status',
                                'profiles!follow_requests_requester_id_fkey(username, full_name)',
                            ], [
                                'id' => $n['related_entity_id'],
                                'target_id' => $userId,
                            ], ['limit' => 1]);
                            $n['follow_request_status'] = $requests[0]['status'] ?? null;
                            $requester = $requests[0]['profiles'] ?? null;
                            $n['follow_requester_name'] = $requester['username']
                                ?? $requester['full_name']
                                ?? null;
                        } catch (Exception) {
                            $n['follow_request_status'] = null;
                            $n['follow_requester_name'] = null;
                        }
                    }

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
            $userId = session('user_id');
            $notifications = $this->supabase->select('notifications', ['*'], [
                'id' => $id,
                'user_id' => $userId,
            ], ['limit' => 1]);

            if (! empty($notifications)) {
                $notification = $notifications[0];
                $filters = [
                    'user_id' => $userId,
                    'type' => $notification['type'] ?? 'system',
                    'is_read' => false,
                ];
                $filters['related_entity_type'] = ($notification['related_entity_type'] ?? null) === null
                    ? ['operator' => 'is', 'value' => null]
                    : $notification['related_entity_type'];
                $filters['related_entity_id'] = ($notification['related_entity_id'] ?? null) === null
                    ? ['operator' => 'is', 'value' => null]
                    : $notification['related_entity_id'];

                $this->supabase->update('notifications', ['is_read' => true], $filters);
            }
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

        return back()->with('status', $this->tr('All notifications marked as read.', 'Semua notifikasi ditandai sudah dibaca.'));
    }

    // POST /app/notifications/{id}/delete
    public function destroy(string $id)
    {
        try {
            $this->supabase->delete('notifications', ['id' => $id]);
        } catch (Exception) {}

        return back()->with('status', $this->tr('Notification deleted.', 'Notifikasi dihapus.'));
    }

    // POST /app/notifications/delete-all
    public function destroyAll()
    {
        try {
            $userId = session('user_id');
            $this->supabase->delete('notifications', ['user_id' => $userId]);
        } catch (Exception) {}

        return back()->with('status', $this->tr('All notifications deleted.', 'Semua notifikasi dihapus.'));
    }

    public function acceptFollowRequest(string $requestId)
    {
        return $this->respondToFollowRequest($requestId, true);
    }

    public function rejectFollowRequest(string $requestId)
    {
        return $this->respondToFollowRequest($requestId, false);
    }

    private function respondToFollowRequest(string $requestId, bool $accepted)
    {
        $userId = session('user_id');

        try {
            $requests = $this->supabase->select('follow_requests', ['*'], [
                'id' => $requestId,
                'target_id' => $userId,
                'status' => 'pending',
            ]);

            if (empty($requests)) {
                return back()->with('error', $this->tr('Follow request not found.', 'Permintaan follow tidak ditemukan.'));
            }

            $followRequest = $requests[0];
            $requesterId = $followRequest['requester_id'];
            $status = $accepted ? 'approved' : 'rejected';

            $this->supabase->update('follow_requests', [
                'status' => $status,
                'responded_at' => now()->toDateTimeString(),
            ], [
                'id' => $requestId,
            ]);

            if ($accepted) {
                $existing = $this->supabase->select('follows', ['id'], [
                    'follower_id' => $requesterId,
                    'following_id' => $userId,
                ]);

                if (empty($existing)) {
                    $this->supabase->insert('follows', [
                        'follower_id' => $requesterId,
                        'following_id' => $userId,
                    ]);
                }

            }

            $responseType = $accepted ? 'follow_request_approved' : 'follow_request_rejected';
            $responseTitle = $accepted ? 'Follow Request Accepted' : 'Follow Request Rejected';
            $responseMessage = $accepted
                ? 'Your follow request was accepted.'
                : 'Your follow request was rejected.';

            $this->supabase->insert('notifications', [
                'user_id' => $requesterId,
                'type' => $responseType,
                'title' => $responseTitle,
                'message' => $responseMessage,
                'related_entity_type' => 'profile',
                'related_entity_id' => $userId,
            ]);

            $this->sendPushToUser(
                $requesterId,
                $responseTitle,
                $responseMessage,
                $responseType,
                $userId
            );

            $this->supabase->update('notifications', [
                'is_read' => true,
            ], [
                'user_id' => $userId,
                'type' => 'follow_request',
                'related_entity_id' => $requestId,
            ]);

            return back()->with(
                'status',
                $accepted
                    ? $this->tr('Follow request accepted.', 'Permintaan follow diterima.')
                    : $this->tr('Follow request rejected.', 'Permintaan follow ditolak.')
            );
        } catch (Exception $e) {
            return back()->with('error', $this->tr('Failed: ', 'Gagal: ') . $e->getMessage());
        }
    }

    private function sendPushToUser(string $userId, string $title, string $message, string $type, string $entityId): void
    {
        try {
            $deviceTokens = $this->supabase->select('device_tokens', ['token'], [
                'user_id' => $userId,
                'is_active' => true,
            ]);

            if (empty($deviceTokens)) return;

            $this->notification->sendToMultipleDevices(
                array_column($deviceTokens, 'token'),
                $title,
                $message,
                $this->notification->generatePayload($type, $entityId)
            );
        } catch (Exception $e) {
            Log::warning('Failed to send follow request response push: ' . $e->getMessage());
        }
    }

    private function tr(string $english, string $indonesian): string
    {
        return session('user_language', 'en') === 'en' ? $english : $indonesian;
    }
}
