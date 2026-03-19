<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\AIChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AIChatController extends Controller
{
    private $supabase;
    private $aiChat;

    public function __construct(SupabaseService $supabase, AIChatService $aiChat)
    {
        $this->supabase = $supabase;
        $this->aiChat   = $aiChat;
    }

    // ─────────────────────────────────────────────
    // CONVERSATIONS
    // ─────────────────────────────────────────────

    /**
     * List semua conversation milik user
     * GET /api/ai/conversations
     */
    public function listConversations(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $limit  = (int) $request->input('limit', 20);
            $offset = (int) $request->input('offset', 0);
            $search = trim((string) $request->input('search', ''));

            $conversations = $this->supabase->select(
                'ai_conversations',
                ['*'],
                ['user_id' => $userId],
                ['order' => 'updated_at.desc', 'limit' => $limit, 'offset' => $offset]
            );

            // Search filter (client-side karena Supabase ILIKE perlu RPC)
            if ($search !== '') {
                $needle        = strtolower($search);
                $conversations = array_values(array_filter(
                    $conversations,
                    fn($c) => str_contains(strtolower($c['title'] ?? ''), $needle)
                           || str_contains(strtolower($c['last_message'] ?? ''), $needle)
                ));
            }

            return response()->json([
                'success' => true,
                'data'    => $conversations,
                'pagination' => ['limit' => $limit, 'offset' => $offset],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Buat conversation baru
     * POST /api/ai/conversations
     */
    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'    => 'nullable|string|max:200',
            'provider' => 'nullable|string|in:groq,openrouter',
            'model'    => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            // Ambil settings user untuk default provider/model
            $settings = $this->getUserSettings($userId);

            $provider = $request->input('provider', $settings['is_active_provider'] ?? 'groq');
            $model    = $request->input('model',    $this->getDefaultModel($settings, $provider));

            $conversation = $this->supabase->insert('ai_conversations', [
                'user_id'  => $userId,
                'title'    => $request->input('title', 'New Chat'),
                'provider' => $provider,
                'model'    => $model,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $conversation[0],
            ], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get detail conversation + messages
     * GET /api/ai/conversations/{id}
     */
    public function showConversation(Request $request, $id)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            $conversations = $this->supabase->select(
                'ai_conversations', ['*'], ['id' => $id, 'user_id' => $userId]
            );

            if (empty($conversations)) {
                return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
            }

            $conversation = $conversations[0];

            // Ambil semua pesan
            $messages = $this->supabase->select(
                'ai_messages',
                ['*'],
                ['conversation_id' => $id],
                ['order' => 'created_at.asc']
            );

            $conversation['messages'] = $messages;

            return response()->json(['success' => true, 'data' => $conversation]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update judul conversation
     * PUT /api/ai/conversations/{id}
     */
    public function updateConversation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            $existing = $this->supabase->select(
                'ai_conversations', ['id'], ['id' => $id, 'user_id' => $userId]
            );

            if (empty($existing)) {
                return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
            }

            $updated = $this->supabase->update(
                'ai_conversations',
                ['title' => $request->input('title')],
                ['id' => $id, 'user_id' => $userId]
            );

            return response()->json(['success' => true, 'data' => $updated]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus conversation + semua messages-nya (cascade)
     * DELETE /api/ai/conversations/{id}
     */
    public function deleteConversation(Request $request, $id)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            $existing = $this->supabase->select(
                'ai_conversations', ['id'], ['id' => $id, 'user_id' => $userId]
            );

            if (empty($existing)) {
                return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
            }

            // Messages terhapus otomatis via ON DELETE CASCADE
            $this->supabase->delete('ai_conversations', ['id' => $id, 'user_id' => $userId]);

            return response()->json(['success' => true, 'message' => 'Conversation deleted']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus semua conversation milik user
     * DELETE /api/ai/conversations
     */
    public function deleteAllConversations(Request $request)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);
            $this->supabase->delete('ai_conversations', ['user_id' => $userId]);

            return response()->json(['success' => true, 'message' => 'All conversations deleted']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // MESSAGES
    // ─────────────────────────────────────────────

    /**
     * Kirim pesan dan dapatkan balasan AI
     * POST /api/ai/conversations/{id}/messages
     */
    public function sendMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content'   => 'required|string|max:2000',
            'image_url' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            // Verifikasi conversation milik user
            $conversations = $this->supabase->select(
                'ai_conversations', ['*'], ['id' => $id, 'user_id' => $userId]
            );

            if (empty($conversations)) {
                return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
            }

            $conversation = $conversations[0];
            $content      = $request->input('content');
            $imageUrl     = $request->input('image_url');

            // Simpan pesan user
            $userMessage = $this->supabase->insert('ai_messages', [
                'conversation_id' => $id,
                'role'            => 'user',
                'content'         => $content,
                'image_url'       => $imageUrl,
            ]);

            // Ambil history pesan untuk context (max 20 pesan terakhir)
            $history = $this->supabase->select(
                'ai_messages',
                ['role', 'content'],
                ['conversation_id' => $id],
                ['order' => 'created_at.asc', 'limit' => 20]
            );

            // Ambil settings AI user
            $settings = $this->getUserSettings($userId);

            // Kirim ke AI (Groq atau OpenRouter)
            $aiResponse = $this->aiChat->chat(
                messages : $history,
                provider : $conversation['provider'],
                model    : $conversation['model'],
                settings : $settings,
            );

            // Simpan balasan AI
            $assistantMessage = $this->supabase->insert('ai_messages', [
                'conversation_id' => $id,
                'role'            => 'assistant',
                'content'         => $aiResponse,
                'is_error'        => false,
            ]);

            // Auto-generate title dari pesan pertama jika masih "New Chat"
            if ($conversation['title'] === 'New Chat' && $conversation['message_count'] === 0) {
                $autoTitle = $this->generateTitle($content);
                $this->supabase->update(
                    'ai_conversations',
                    ['title' => $autoTitle],
                    ['id' => $id]
                );
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'user_message'      => $userMessage[0],
                    'assistant_message' => $assistantMessage[0],
                ],
            ], 201);
        } catch (Exception $e) {
            // Simpan pesan error ke DB agar terlihat di UI
            try {
                $this->supabase->insert('ai_messages', [
                    'conversation_id' => $id,
                    'role'            => 'assistant',
                    'content'         => 'Maaf, terjadi kesalahan: ' . $e->getMessage(),
                    'is_error'        => true,
                ]);
            } catch (Exception $inner) {
                // Abaikan error saat menyimpan pesan error
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get messages dari conversation
     * GET /api/ai/conversations/{id}/messages
     */
    public function getMessages(Request $request, $id)
    {
        try {
            $userId = $this->getSupabaseUserIdFromRequest($request);

            // Verifikasi ownership
            $existing = $this->supabase->select(
                'ai_conversations', ['id'], ['id' => $id, 'user_id' => $userId]
            );

            if (empty($existing)) {
                return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
            }

            $messages = $this->supabase->select(
                'ai_messages',
                ['*'],
                ['conversation_id' => $id],
                ['order' => 'created_at.asc']
            );

            return response()->json(['success' => true, 'data' => $messages]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get daftar model tersedia
     * GET /api/ai/models?provider=groq
     */
    public function getAvailableModels(Request $request)
    {
        try {
            $provider = $request->input('provider');
            $filters  = [];
            if ($provider) {
                $filters['provider'] = $provider;
            }

            $models = $this->supabase->select(
                'ai_available_models',
                ['*'],
                $filters,
                ['order' => 'sort_order.asc']
            );

            return response()->json(['success' => true, 'data' => $models]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    private function getUserSettings(string $userId): array
    {
        try {
            $settings = $this->supabase->select(
                'user_ai_settings', ['*'], ['user_id' => $userId]
            );
            return $settings[0] ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function getDefaultModel(array $settings, string $provider): string
    {
        if ($provider === 'openrouter') {
            return $settings['openrouter_model'] ?? 'meta-llama/llama-3.3-70b-instruct:free';
        }
        return $settings['groq_model'] ?? 'llama-3.3-70b-versatile';
    }

    private function generateTitle(string $firstMessage): string
    {
        $title = trim($firstMessage);
        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 47) . '...';
        }
        return $title ?: 'New Chat';
    }
}