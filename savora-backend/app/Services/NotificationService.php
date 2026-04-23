<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotificationService
{
    private string $projectId;
    private string $fcmUrl;

    public function __construct()
    {
        $this->projectId = env('FCM_PROJECT_ID', '');
        $this->fcmUrl    = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    // ─────────────────────────────────────────────
    // GET CREDENTIALS dari env variable
    // ─────────────────────────────────────────────
    private function getCredentials(): array
    {
        $json = env('FCM_CREDENTIALS_JSON', '');

        if (empty($json)) {
            throw new Exception('FCM_CREDENTIALS_JSON is not set in environment variables.');
        }

        $credentials = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('FCM_CREDENTIALS_JSON is not valid JSON: ' . json_last_error_msg());
        }

        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new Exception('FCM_CREDENTIALS_JSON missing client_email or private_key.');
        }

        return $credentials;
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
        try {
            $accessToken = $this->getAccessToken();
            $stringData  = array_map('strval', $data);

            $payload = [
                'message' => [
                    'token'        => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data'    => $stringData,
                    'android' => [
                        'priority'     => 'high',
                        'notification' => [
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('FCM v1: Notification sent to device: ' . $deviceToken);
                return true;
            }

            // Token OAuth expired → clear cache dan retry sekali
            if ($response->status() === 401) {
                Cache::forget('fcm_access_token');
                Log::warning('FCM v1: Access token expired, retrying...');
                return $this->sendToDevice($deviceToken, $title, $body, $data);
            }

            Log::error('FCM v1 sendToDevice error: ' . $response->body());
            return false;
        } catch (Exception $e) {
            Log::error('FCM v1 sendToDevice exception: ' . $e->getMessage());
            return false;
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

        foreach ($deviceTokens as $token) {
            $this->sendToDevice($token, $title, $body, $data) ? $success++ : $failure++;
        }

        Log::info("FCM v1 batch: Success={$success}, Failure={$failure}");
        return ['success' => $success, 'failure' => $failure];
    }

    // ─────────────────────────────────────────────
    // SEND TO TOPIC
    // ─────────────────────────────────────────────
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            $stringData  = array_map('strval', $data);

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
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($this->fcmUrl, $payload);

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
            'new_comment'               => ['route' => 'recipe',  'id' => $entityId],
            'new_like'                  => ['route' => 'recipe',  'id' => $entityId],
        ];

        return $payloadMap[$type] ?? ['route' => 'home', 'id' => ''];
    }
}