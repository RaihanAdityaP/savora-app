<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use App\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class NotificationController extends Controller
{
    private SupabaseService $supabase;
    private NotificationService $notification;
    private UserSettingsService $settingsService;

    public function __construct(SupabaseService $supabase, NotificationService $notification, UserSettingsService $settingsService)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
        $this->settingsService = $settingsService;
    }

    /**
     * Get user notifications
     * GET /api/notifications/user/{userId}
     */
    public function getUserNotifications(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $notifications = $this->supabase->select('notifications', 
                ['*'], 
                ['user_id' => $userId],
                ['order' => 'created_at.desc', 'limit' => 50]
            );

            foreach ($notifications as $index => $notification) {
                if (
                    ($notification['type'] ?? '') !== 'follow_request' ||
                    empty($notification['related_entity_id'])
                ) {
                    continue;
                }

                try {
                    $requests = $this->supabase->select('follow_requests', [
                        'status',
                        'profiles!follow_requests_requester_id_fkey(username, full_name)',
                    ], [
                        'id' => $notification['related_entity_id'],
                        'target_id' => $userId,
                    ], ['limit' => 1]);

                    $requester = $requests[0]['profiles'] ?? null;
                    $notifications[$index]['follow_request_status'] = $requests[0]['status'] ?? null;
                    $notifications[$index]['follow_requester_name'] = $requester['username']
                        ?? $requester['full_name']
                        ?? null;
                } catch (Exception) {
                    $notifications[$index]['follow_request_status'] = null;
                    $notifications[$index]['follow_requester_name'] = null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $notifications,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read
     * POST /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
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

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     * POST /api/notifications/user/{userId}/read-all
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $this->supabase->update('notifications', 
                ['is_read' => true],
                ['user_id' => $userId, 'is_read' => false]
            );

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notification to user
     * POST /api/notifications/send
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'type' => 'required|string',
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:500',
            'related_entity_type' => 'nullable|string',
            'related_entity_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if (! $this->settingsService->notificationEnabledForType(
                $request->input('user_id'),
                $request->input('type')
            )) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification skipped by user settings',
                    'data' => null,
                ]);
            }

            // Insert notification to database
            $notification = $this->supabase->insert('notifications', [
                'user_id' => $request->input('user_id'),
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'related_entity_type' => $request->input('related_entity_type'),
                'related_entity_id' => $request->input('related_entity_id'),
                'is_read' => false,
            ]);

            // Send push notification if device tokens exist
            $deviceTokens = $this->supabase->select('device_tokens',
                ['token'],
                ['user_id' => $request->input('user_id'), 'is_active' => true]
            );

            if (!empty($deviceTokens)) {
                $tokens = array_column($deviceTokens, 'token');
                $payload = $this->notification->generatePayload(
                    $request->input('type'),
                    $request->input('related_entity_id', '')
                );

                try {
                    $this->notification->sendToMultipleDevices(
                        $tokens,
                        $request->input('title'),
                        $request->input('message'),
                        $payload
                    );
                } catch (Exception $e) {
                    // Log but don't fail the notification creation
                    Log::warning('Failed to send push notification: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => $notification[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function broadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:all,user',
            'user_id' => 'nullable|uuid',
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:500',
            'route' => 'nullable|in:home,recipe,profile',
            'related_entity_id' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $adminId = $this->getSupabaseUserIdFromRequest($request);
            if (! $this->isAdminUser($adminId)) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $data = $validator->validated();
            $targetIds = [];

            if ($data['audience'] === 'user') {
                if (empty($data['user_id'])) {
                    return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
                }
                $targetIds = [$data['user_id']];
            } else {
                $targetIds = collect($this->supabase->select('profiles', ['id'], ['is_banned' => false], ['limit' => 1000]))
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all();
            }

            $route = $data['route'] ?? 'home';
            $entityId = $data['related_entity_id'] ?? '';
            $result = $this->sendManualNotification($targetIds, $data['title'], $data['message'], $route, $entityId);

            return response()->json([
                'success' => true,
                'message' => 'Broadcast sent successfully',
                'data' => [
                    'sent_count' => $result['users'],
                    'device_tokens' => $result['device_tokens'],
                    'push_success' => $result['push_success'],
                    'push_failure' => $result['push_failure'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Register device token for push notifications
     * POST /api/notifications/register-device
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios',
            'device_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $token = $request->input('token');
            $userId = $request->input('user_id');

            // Check if token already exists
            $existing = $this->supabase->select('device_tokens',
                ['id', 'user_id'],
                ['token' => $token]
            );

            if (!empty($existing)) {
                // Token exists, update it
                $deviceToken = $this->supabase->update('device_tokens', [
                    'user_id' => $userId,
                    'device_type' => $request->input('device_type', 'android'),
                    'device_name' => $request->input('device_name'),
                    'is_active' => true,
                    'last_used_at' => date('Y-m-d H:i:s'),
                ], ['token' => $token]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device token updated successfully',
                    'data' => $deviceToken,
                ]);
            } else {
                // Insert new token
                $deviceToken = $this->supabase->insert('device_tokens', [
                    'user_id' => $userId,
                    'token' => $token,
                    'device_type' => $request->input('device_type', 'android'),
                    'device_name' => $request->input('device_name'),
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device registered successfully',
                    'data' => $deviceToken[0],
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unregister device token
     * DELETE /api/notifications/unregister-device
     */
    public function unregisterDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Soft delete by marking as inactive
            $this->supabase->update('device_tokens', [
                'is_active' => false,
            ], ['token' => $request->input('token')]);

            return response()->json([
                'success' => true,
                'message' => 'Device unregistered successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's registered devices
     * GET /api/notifications/devices/{userId}
     */
    public function getUserDevices(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $devices = $this->supabase->select('device_tokens',
                ['*'],
                ['user_id' => $userId, 'is_active' => true],
                ['order' => 'last_used_at.desc']
            );

            return response()->json([
                'success' => true,
                'data' => $devices,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread notification count
     * GET /api/notifications/user/{userId}/unread-count
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $notifications = $this->supabase->select('notifications',
                ['id'],
                [
                    'user_id' => $userId,
                    'is_read' => false,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => count($notifications),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all notifications for authenticated user
     * DELETE /api/notifications
     */
    public function destroyAll(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $this->supabase->delete('notifications', ['user_id' => $userId]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete notification
     * DELETE /api/notifications/{id}
     */
    public function destroy(string $id)
    {
        try {
            $this->supabase->delete('notifications', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendManualNotification(array $userIds, string $title, string $message, string $route, string $entityId = ''): array
    {
        $sent = 0;
        $deviceTokens = 0;
        $pushSuccess = 0;
        $pushFailure = 0;
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
                    $tokenList = array_column($tokens, 'token');
                    $deviceTokens += count($tokenList);
                    $result = $this->notification->sendToMultipleDevices($tokenList, $title, $message, $payload);
                    $pushSuccess += $result['success'] ?? 0;
                    $pushFailure += $result['failure'] ?? 0;
                }
            } catch (Exception $e) {
                Log::warning('Manual API broadcast push failed for user ' . $userId . ': ' . $e->getMessage());
                $pushFailure++;
            }

            $sent++;
        }

        return [
            'users' => $sent,
            'device_tokens' => $deviceTokens,
            'push_success' => $pushSuccess,
            'push_failure' => $pushFailure,
        ];
    }

    private function isAdminUser(string $userId): bool
    {
        $profiles = $this->supabase->select('profiles', ['role'], ['id' => $userId], ['limit' => 1]);
        return ($profiles[0]['role'] ?? null) === 'admin';
    }
}
