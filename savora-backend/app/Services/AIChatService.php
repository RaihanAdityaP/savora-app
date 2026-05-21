<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIChatService
{
    private string $groqApiKey;
    private string $groqBaseUrl    = 'https://api.groq.com/openai/v1/chat/completions';
    private string $openRouterUrl  = 'https://openrouter.ai/api/v1/chat/completions';
    private int    $timeout        = 30;

    // System prompt Chef AI
    private string $systemPrompt = "Anda adalah Chef AI Savora, asisten koki profesional Indonesia yang ramah dan membantu.

Tugas Anda:
- Menjawab pertanyaan tentang memasak dalam Bahasa Indonesia
- Memberikan tips praktis dan mudah dipahami
- Menjelaskan teknik memasak dengan detail
- Menyarankan variasi dan modifikasi resep
- Memberikan informasi nutrisi dan bahan makanan
- Selalu ramah, suportif, dan antusias tentang kuliner Indonesia

Format jawaban:
- Gunakan Bahasa Indonesia yang natural dan mudah dipahami
- Boleh gunakan emoji yang relevan untuk membuat jawaban lebih menarik
- Untuk resep atau langkah-langkah, gunakan format terstruktur
- Jangan terlalu panjang, fokus pada informasi yang paling berguna";

    public function __construct()
    {
        $this->groqApiKey = env('GROQ_API_KEY', '');
    }

    /**
     * Main chat method — otomatis pilih provider
     *
     * @param array  $messages  [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @param string $provider  'groq' | 'openrouter'
     * @param string $model     model ID
     * @param array  $settings  user AI settings dari DB
     */
    public function chat(
        array  $messages,
        string $provider = 'groq',
        ?string $model   = null,
        array  $settings = []
    ): string {
        $model ??= $this->defaultModel($provider);

        // Tambahkan system prompt di awal jika belum ada
        $hasSystem = collect($messages)->contains('role', 'system');
        if (!$hasSystem) {
            array_unshift($messages, [
                'role'    => 'system',
                'content' => $this->systemPrompt,
            ]);
        }

        return match ($provider) {
            'openrouter' => $this->chatOpenRouter($messages, $model, $settings),
            default      => $this->chatGroq($messages, $model),
        };
    }

    public function analyzeImage(string $imagePath, string $mimeType): string
    {
        if (empty($this->groqApiKey)) {
            throw new Exception('GROQ_API_KEY tidak ditemukan di .env');
        }

        $model = $this->requiredConfig('ai.groq_vision_model', 'GROQ_VISION_MODEL');
        $imageData = base64_encode(file_get_contents($imagePath));

        $prompt = "Berdasarkan gambar makanan yang dikirim, berikan analisis:\n"
                . "1. Prediksi nama makanan\n"
                . "2. Bahan-bahan utama yang mungkin digunakan\n"
                . "3. Perkiraan cara memasak (3-5 langkah)\n"
                . "4. Tips memasak makanan ini\n\n"
                . "Jawab dalam Bahasa Indonesia dengan format yang rapi.";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post($this->groqBaseUrl, [
                'model'    => $model,
                'messages' => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
                            ],
                        ],
                    ],
                ]],
                'temperature' => 0.7,
                'max_tokens'  => 1500,
                'top_p'       => 0.9,
                'stream'      => false,
            ]);

        return $this->parseOpenAICompatibleResponse($response, 'Groq Vision');
    }

    // ─────────────────────────────────────────────
    // GROQ
    // ─────────────────────────────────────────────

    private function chatGroq(array $messages, string $model): string
    {
        if (empty($this->groqApiKey)) {
            throw new Exception('GROQ_API_KEY tidak ditemukan di .env');
        }

        Log::debug('AIChatService: Sending to Groq', ['model' => $model]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post($this->groqBaseUrl, [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => 1500,
                'top_p'       => 0.9,
                'stream'      => false,
            ]);

        return $this->parseOpenAICompatibleResponse($response, 'Groq');
    }

    // ─────────────────────────────────────────────
    // OPENROUTER
    // ─────────────────────────────────────────────

    private function chatOpenRouter(array $messages, string $model, array $settings): string
    {
        // Ambil API key dari settings user atau dari env sebagai fallback
        $apiKey = $settings['openrouter_api_key'] ?? env('OPENROUTER_API_KEY', '');

        if (empty($apiKey)) {
            throw new Exception('OpenRouter API key belum dikonfigurasi. Silakan set di AI Settings.');
        }

        Log::debug('AIChatService: Sending to OpenRouter', ['model' => $model]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => env('APP_URL', 'https://savora.app'),
                'X-Title'       => 'Savora Chef AI',
            ])
            ->post($this->openRouterUrl, [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => 1500,
                'top_p'       => 0.9,
            ]);

        return $this->parseOpenAICompatibleResponse($response, 'OpenRouter');
    }

    // ─────────────────────────────────────────────
    // RESPONSE PARSER (OpenAI-compatible format)
    // ─────────────────────────────────────────────

    private function parseOpenAICompatibleResponse($response, string $providerName): string
    {
        if ($response->successful()) {
            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($content !== null && $content !== '') {
                return trim($content);
            }

            throw new Exception("Response kosong dari {$providerName}");
        }

        $status = $response->status();
        $body   = $response->json();

        Log::error("{$providerName} Error", [
            'status' => $status,
            'body'   => $body,
        ]);

        $message = $body['error']['message'] ?? $body['message'] ?? "Error {$status}";

        throw match ($status) {
            401     => new Exception("API key {$providerName} tidak valid atau kadaluarsa."),
            429     => new Exception("Terlalu banyak permintaan ke {$providerName}. Tunggu sebentar."),
            503     => new Exception("Server {$providerName} sedang sibuk. Coba lagi."),
            default => new Exception("Gagal menghubungi {$providerName}: {$message}"),
        };
    }

    // ─────────────────────────────────────────────
    // COOKING QUESTION (backward-compat dengan AIService lama)
    // ─────────────────────────────────────────────

    public function askCookingQuestion(string $question, string $recipeContext = '', array $settings = []): string
    {
        $provider = $settings['is_active_provider'] ?? 'groq';
        $model    = $provider === 'openrouter'
            ? ($settings['openrouter_model'] ?? null)
            : ($settings['groq_model']       ?? $this->defaultModel('groq'));

        $messages = [];
        if (!empty($recipeContext)) {
            $messages[] = [
                'role'    => 'system',
                'content' => $this->systemPrompt . "\n\nKonteks Resep: {$recipeContext}",
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        return $this->chat($messages, $provider, $model, $settings);
    }

    public function defaultModel(string $provider): string
    {
        if ($provider === 'openrouter') {
            throw new Exception('OpenRouter model wajib dipilih oleh user.');
        }

        return $this->requiredConfig('ai.groq_model', 'GROQ_MODEL');
    }

    private function requiredConfig(string $key, string $envName): string
    {
        $value = config($key);

        if (!is_string($value) || trim($value) === '') {
            throw new Exception("{$envName} belum dikonfigurasi di .env");
        }

        return $value;
    }
}
