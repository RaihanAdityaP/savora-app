<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class NotificationController extends Controller
{
    private $supabase;
    private $notification;

    public function __construct(SupabaseService $supabase, NotificationService $notification)
    {
        $this->supabase = $supabase;
        $this->notification = $notification;
    }

    /**
     * Get user notifications
     * GET /api/v1/notifications/user/{userId}
     */
    public function getUserNotifications($userId)
    {
        try {
            $notifications = $this->supabase->select('notifications', 
                ['*'], 
                ['user_id' => $userId],
                ['order' => 'created_at.desc', 'limit' => 50]
            );

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
     * POST /api/v1/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        try {
            $this->supabase->update('notifications', 
                ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
                ['id' => $id]
            );

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
     * POST /api/v1/notifications/user/{userId}/read-all
     */
    public function markAllAsRead($userId)
    {
        try {
            $this->supabase->update('notifications', 
                ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
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
     * POST /api/v1/notifications/send
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'type' => 'required|string',
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:500',
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
            // Insert notification to database
            $notification = $this->supabase->insert('notifications', [
                'user_id' => $request->input('user_id'),
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'related_entity_id' => $request->input('related_entity_id'),
                'is_read' => false,
            ]);

            // Send push notification if device token exists
            $deviceTokens = $this->supabase->select('device_tokens',
                ['token'],
                ['user_id' => $request->input('user_id')]
            );

            if (!empty($deviceTokens)) {
                $tokens = array_column($deviceTokens, 'token');
                $payload = $this->notification->generatePayload(
                    $request->input('type'),
                    $request->input('related_entity_id', '')
                );

                $this->notification->sendToMultipleDevices(
                    $tokens,
                    $request->input('title'),
                    $request->input('message'),
                    $payload
                );
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

    /**
     * Register device token for push notifications
     * POST /api/v1/notifications/register-device
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'device_token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if token already exists
            $existing = $this->supabase->select('device_tokens',
                ['id'],
                [
                    'user_id' => $request->input('user_id'),
                    'token' => $request->input('device_token'),
                ]
            );

            if (empty($existing)) {
                // Insert new token
                $this->supabase->insert('device_tokens', [
                    'user_id' => $request->input('user_id'),
                    'token' => $request->input('device_token'),
                    'device_type' => $request->input('device_type', 'android'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
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
     * GET /api/v1/notifications/user/{userId}/unread-count
     */
    public function getUnreadCount($userId)
    {
        try {
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
     * Delete notification
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy($id)
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
}