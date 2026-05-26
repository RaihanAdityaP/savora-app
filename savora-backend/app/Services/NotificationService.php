<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotificationService
{
    private const ANDROID_CHANNEL_ID = 'savora_high_channel';

    private string $projectId;

    public function __construct()
    {
        $this->projectId = env('FCM_PROJECT_ID', '');
    }

    // ─────────────────────────────────────────────
    // GET CREDENTIALS dari env variable
    // ─────────────────────────────────────────────
    private function getCredentials(): array
    {
        $json = trim((string) env('FCM_CREDENTIALS_JSON', ''));

        if (empty($json)) {
            throw new Exception('FCM_CREDENTIALS_JSON is not set in environment variables.');
        }

        if (
            (str_starts_with($json, "'") && str_ends_with($json, "'")) ||
            (str_starts_with($json, '"') && str_ends_with($json, '"'))
        ) {
            $json = substr($json, 1, -1);
        }

        $credentials = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('FCM_CREDENTIALS_JSON is not valid JSON: ' . json_last_error_msg());
        }

        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new Exception('FCM_CREDENTIALS_JSON missing client_email or private_key.');
        }

        $credentials['private_key'] = str_replace('\\n', "\n", $credentials['private_key']);
        if ($this->projectId === '' && ! empty($credentials['project_id'])) {
            $this->projectId = $credentials['project_id'];
        }

        return $credentials;
    }

    private function fcmUrl(): string
    {
        if ($this->projectId === '') {
            $credentials = $this->getCredentials();
            $this->projectId = $credentials['project_id'] ?? '';
        }

        if ($this->projectId === '') {
            throw new Exception('FCM_PROJECT_ID is not set and project_id is missing from FCM_CREDENTIALS_JSON.');
        }

        return "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    // ─────────────────────────────────────────────
    // GET OAUTH2 ACCESS TOKEN
    // Cached selama 55 menit (token expired setiap 60 menit)
    // ─────────────────────────────────────────────
    private function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', 55 * 60, function () {
            $credentials = $this->getCredentials();

            $now    = time();
            $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
            $claims = rtrim(strtr(base64_encode(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ])), '+/', '-_'), '=');

            $signature = '';
            openssl_sign("{$header}.{$claims}", $signature, $credentials['private_key'], 'SHA256');
            $sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $jwt = "{$header}.{$claims}.{$sig}";

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                throw new Exception('Failed to get FCM access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    // ─────────────────────────────────────────────
    // SEND TO SINGLE DEVICE
    // ─────────────────────────────────────────────
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        return (bool) ($this->sendToDeviceDetailed($deviceToken, $title, $body, $data)['success'] ?? false);
    }

    public function sendToDeviceDetailed(string $deviceToken, string $title, string $body, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $stringData  = array_map('strval', array_merge($data, [
                'title' => $title,
                'body'  => $body,
            ]));

            $payload = [
                'message' => [
                    'token'        => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data'         => $stringData,
                    'android'      => [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id'   => self::ANDROID_CHANNEL_ID,
                            'icon'         => 'ic_launcher',
                            'color'        => '#E76F51',
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'notification_priority' => 'PRIORITY_HIGH',
                            'visibility'   => 'PUBLIC',
                        ],
                    ],
                    'apns'         => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($this->fcmUrl(), $payload);

            if ($response->successful()) {
                Log::info('FCM v1: Notification sent to device: ' . $deviceToken);
                return ['success' => true, 'inactive' => false, 'error' => null];
            }

            // Token OAuth expired → clear cache dan retry sekali
            if ($response->status() === 401) {
                Cache::forget('fcm_access_token');
                Log::warning('FCM v1: Access token expired, retrying...');
                return $this->sendToDeviceDetailed($deviceToken, $title, $body, $data);
            }

            $errorBody = $response->body();
            $inactive = str_contains($errorBody, 'UNREGISTERED')
                || str_contains($errorBody, 'INVALID_ARGUMENT')
                || str_contains($errorBody, 'registration-token-not-registered');

            Log::error('FCM v1 sendToDevice error: ' . $errorBody);
            return ['success' => false, 'inactive' => $inactive, 'error' => $errorBody];
        } catch (Exception $e) {
            Log::error('FCM v1 sendToDevice exception: ' . $e->getMessage());
            return ['success' => false, 'inactive' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────
    // SEND TO MULTIPLE DEVICES
    // FCM v1 tidak support multicast, loop per token
    // ─────────────────────────────────────────────
    public function sendToMultipleDevices(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $success = 0;
        $failure = 0;
        $inactiveTokens = [];
        $lastError = null;

        foreach ($deviceTokens as $token) {
            $result = $this->sendToDeviceDetailed($token, $title, $body, $data);

            if ($result['success'] ?? false) {
                $success++;
                continue;
            }

            $failure++;
            $lastError = $result['error'] ?? $lastError;

            if ($result['inactive'] ?? false) {
                $inactiveTokens[] = $token;
            }
        }

        Log::info("FCM v1 batch: Success={$success}, Failure={$failure}");
        return [
            'success' => $success,
            'failure' => $failure,
            'inactive_tokens' => $inactiveTokens,
            'last_error' => $lastError,
        ];
    }

    // ─────────────────────────────────────────────
    // SEND TO TOPIC
    // ─────────────────────────────────────────────
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            $stringData  = array_map('strval', array_merge($data, [
                'title' => $title,
                'body'  => $body,
            ]));

            $payload = [
                'message' => [
                    'topic'        => $topic,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data'    => $stringData,
                    'android' => [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id'   => self::ANDROID_CHANNEL_ID,
                            'icon'         => 'ic_launcher',
                            'color'        => '#E76F51',
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'notification_priority' => 'PRIORITY_HIGH',
                            'visibility'   => 'PUBLIC',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($this->fcmUrl(), $payload);

            if ($response->successful()) {
                Log::info('FCM v1: Notification sent to topic: ' . $topic);
                return true;
            }

            Log::error('FCM v1 sendToTopic error: ' . $response->body());
            return false;
        } catch (Exception $e) {
            Log::error('FCM v1 sendToTopic exception: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // GENERATE PAYLOAD
    // ─────────────────────────────────────────────
    public function generatePayload(string $type, string $entityId): array
    {
        $payloadMap = [
            'new_recipe_from_following' => ['route' => 'recipe',  'id' => $entityId],
            'recipe_approved'           => ['route' => 'recipe',  'id' => $entityId],
            'recipe_rejected'           => ['route' => 'recipe',  'id' => $entityId],
            'new_follower'              => ['route' => 'profile', 'id' => $entityId],
            'follow_request'            => ['route' => 'notifications', 'id' => $entityId],
            'follow_request_approved'   => ['route' => 'profile', 'id' => $entityId],
            'follow_request_rejected'   => ['route' => 'profile', 'id' => $entityId],
            'new_comment'               => ['route' => 'recipe',  'id' => $entityId],
            'new_like'                  => ['route' => 'recipe',  'id' => $entityId],
        ];

        return $payloadMap[$type] ?? ['route' => 'home', 'id' => ''];
    }
}
