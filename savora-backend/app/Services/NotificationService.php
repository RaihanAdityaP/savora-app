<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    private $fcmServerKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = env('FCM_SERVER_KEY');
        
        if (empty($this->fcmServerKey)) {
            Log::warning('FCM_SERVER_KEY not found in environment!');
        }
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (empty($this->fcmServerKey)) {
            throw new Exception('FCM_SERVER_KEY not configured');
        }

        try {
            $payload = [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('Notification sent successfully to device: ' . $deviceToken);
                return true;
            } else {
                Log::error('FCM Error: ' . $response->body());
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToMultipleDevices(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        if (empty($this->fcmServerKey)) {
            throw new Exception('FCM_SERVER_KEY not configured');
        }

        try {
            $payload = [
                'registration_ids' => $deviceTokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Batch notification sent. Success: ' . $result['success'] . ', Failure: ' . $result['failure']);
                return $result;
            } else {
                Log::error('FCM Batch Error: ' . $response->body());
                return ['success' => 0, 'failure' => count($deviceTokens)];
            }
        } catch (Exception $e) {
            Log::error('Error sending batch notifications: ' . $e->getMessage());
            return ['success' => 0, 'failure' => count($deviceTokens)];
        }
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (empty($this->fcmServerKey)) {
            throw new Exception('FCM_SERVER_KEY not configured');
        }

        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('Notification sent successfully to topic: ' . $topic);
                return true;
            } else {
                Log::error('FCM Topic Error: ' . $response->body());
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error sending topic notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate notification payload for different types
     */
    public function generatePayload(string $type, string $entityId): array
    {
        $payloadMap = [
            'new_recipe_from_following' => ['route' => 'recipe', 'id' => $entityId],
            'recipe_approved' => ['route' => 'recipe', 'id' => $entityId],
            'recipe_rejected' => ['route' => 'recipe', 'id' => $entityId],
            'new_follower' => ['route' => 'profile', 'id' => $entityId],
            'new_comment' => ['route' => 'recipe', 'id' => $entityId],
            'new_like' => ['route' => 'recipe', 'id' => $entityId],
        ];

        return $payloadMap[$type] ?? ['route' => 'home', 'id' => ''];
    }
}