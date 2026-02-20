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
     * GET /api/notifications/user/{userId}
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
     * POST /api/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        try {
            $this->supabase->update('notifications', 
                ['is_read' => true],
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
     * POST /api/notifications/user/{userId}/read-all
     */
    public function markAllAsRead($userId)
    {
        try {
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
                    \Log::warning('Failed to send push notification: ' . $e->getMessage());
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
    public function getUserDevices($userId)
    {
        try {
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
     * DELETE /api/notifications/{id}
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