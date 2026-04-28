<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use App\Services\AIChatService;
use Illuminate\Http\Request;
use Exception;

class AIChatController extends Controller
{
    public function __construct(
        private SupabaseService $supabase,
        private AIChatService $aiChat,
    ) {}

    // GET /app/ai
    public function index()
    {
        $userId        = session('user_id');
        $conversations = [];

        try {
            $conversations = $this->supabase->select(
                'ai_conversations',
                ['*'],
                ['user_id' => $userId],
                ['order' => 'updated_at.desc', 'limit' => 30]
            );
        } catch (Exception) {}

        return view('app.ai-chat', compact('conversations'));
    }

    // GET /app/ai/conversations/{id}
    public function showConversation(string $id)
    {
        $userId = session('user_id');

        try {
            $conversations = $this->supabase->select(
                'ai_conversations',
                ['*'],
                ['id' => $id, 'user_id' => $userId]
            );

            if (empty($conversations)) abort(404);

            $conversation = $conversations[0];

            $messages = $this->supabase->select(
                'ai_messages',
                ['*'],
                ['conversation_id' => $id],
                ['order' => 'created_at.asc']
            );

            // List semua conversation untuk sidebar
            $conversations = $this->supabase->select(
                'ai_conversations',
                ['id', 'title', 'updated_at'],
                ['user_id' => $userId],
                ['order' => 'updated_at.desc', 'limit' => 30]
            );

            return view('app.ai-conversation', compact('conversation', 'messages', 'conversations'));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // POST /app/ai/conversations
    public function createConversation(Request $request)
    {
        try {
            $userId   = session('user_id');
            $settings = $this->getUserSettings($userId);
            $provider = $settings['is_active_provider'] ?? 'groq';
            $model    = $this->getDefaultModel($settings, $provider);

            $conv = $this->supabase->insert('ai_conversations', [
                'user_id'  => $userId,
                'title'    => 'New Chat',
                'provider' => $provider,
                'model'    => $model,
            ]);

            return redirect()->route('app.ai.conversation', $conv[0]['id']);
        } catch (Exception $e) {
            return back()->with('error', 'Gagal membuat chat: ' . $e->getMessage());
        }
    }

    // POST /app/ai/conversations/{id}/send
    public function sendMessage(Request $request, string $id)
    {
        $request->validate(['content' => 'required|string|max:2000']);

        try {
            $userId = session('user_id');

            $convs = $this->supabase->select('ai_conversations', ['*'], ['id' => $id, 'user_id' => $userId]);
            if (empty($convs)) abort(404);
            $conversation = $convs[0];

            // Simpan pesan user
            $this->supabase->insert('ai_messages', [
                'conversation_id' => $id,
                'role'            => 'user',
                'content'         => $request->input('content'),
            ]);

            // Ambil history
            $history = $this->supabase->select(
                'ai_messages',
                ['role', 'content'],
                ['conversation_id' => $id],
                ['order' => 'created_at.asc', 'limit' => 20]
            );

            $settings = $this->getUserSettings($userId);

            // Kirim ke AI
            $aiResponse = $this->aiChat->chat(
                messages: $history,
                provider: $conversation['provider'] ?? 'groq',
                model:    $conversation['model'] ?? 'llama-3.3-70b-versatile',
                settings: $settings,
            );

            // Simpan balasan AI
            $this->supabase->insert('ai_messages', [
                'conversation_id' => $id,
                'role'            => 'assistant',
                'content'         => $aiResponse,
                'is_error'        => false,
            ]);

            // Auto-title dari pesan pertama
            if ($conversation['title'] === 'New Chat') {
                $content   = $request->input('content');
                $autoTitle = mb_strlen($content) > 50
                    ? mb_substr($content, 0, 47) . '...'
                    : $content;
                $this->supabase->update('ai_conversations', ['title' => $autoTitle], ['id' => $id]);
            }

            return redirect()->route('app.ai.conversation', $id);

        } catch (Exception $e) {
            // Simpan error message ke DB
            try {
                $this->supabase->insert('ai_messages', [
                    'conversation_id' => $id,
                    'role'            => 'assistant',
                    'content'         => 'Maaf, terjadi kesalahan: ' . $e->getMessage(),
                    'is_error'        => true,
                ]);
            } catch (Exception) {}

            return redirect()->route('app.ai.conversation', $id)
                ->with('error', 'Gagal mendapatkan respons AI.');
        }
    }

    // POST /app/ai/conversations/{id}/rename
    public function renameConversation(Request $request, string $id)
    {
        $request->validate(['title' => 'required|string|max:200']);

        try {
            $userId = session('user_id');
            $this->supabase->update(
                'ai_conversations',
                ['title' => $request->input('title')],
                ['id' => $id, 'user_id' => $userId]
            );
            return back()->with('status', 'Judul berhasil diubah.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/ai/conversations/{id}/delete
    public function deleteConversation(string $id)
    {
        try {
            $userId = session('user_id');
            $this->supabase->delete('ai_conversations', ['id' => $id, 'user_id' => $userId]);
            return redirect()->route('app.ai')->with('status', 'Chat dihapus.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/ai/delete-all
    public function deleteAll()
    {
        try {
            $userId = session('user_id');
            $this->supabase->delete('ai_conversations', ['user_id' => $userId]);
            return redirect()->route('app.ai')->with('status', 'Semua chat dihapus.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // GET /app/ai/settings
    public function settings()
    {
        $userId   = session('user_id');
        $settings = $this->getUserSettings($userId);

        return view('app.ai-settings', compact('settings'));
    }

    // POST /app/ai/settings
    public function saveSettings(Request $request)
    {
        $request->validate([
            'is_active_provider' => 'required|in:groq,openrouter',
            'openrouter_model'   => 'nullable|string|max:200',
            'openrouter_api_key' => 'nullable|string|max:500',
        ]);

        try {
            $userId   = session('user_id');
            $existing = $this->supabase->select('user_ai_settings', ['id'], ['user_id' => $userId]);

            $data = [
                'user_id'            => $userId,
                'is_active_provider' => $request->input('is_active_provider'),
                'provider'           => $request->input('is_active_provider'),
                'openrouter_model'   => $request->input('openrouter_model', 'meta-llama/llama-3.3-70b-instruct:free'),
            ];

            $apiKey = $request->input('openrouter_api_key');
            if ($apiKey && $apiKey !== '***SAVED***') {
                $data['openrouter_api_key'] = $apiKey;
            }

            if (empty($existing)) {
                $this->supabase->insert('user_ai_settings', $data);
            } else {
                unset($data['user_id']);
                $this->supabase->update('user_ai_settings', $data, ['user_id' => $userId]);
            }

            return back()->with('status', 'Settings berhasil disimpan.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function getUserSettings(string $userId): array
    {
        try {
            $settings = $this->supabase->select('user_ai_settings', ['*'], ['user_id' => $userId]);
            return $settings[0] ?? [];
        } catch (Exception) {
            return [];
        }
    }

    private function getDefaultModel(array $settings, string $provider): string
    {
        if ($provider === 'openrouter') {
            return $settings['openrouter_model'] ?? 'meta-llama/llama-3.3-70b-instruct:free';
        }
        return 'llama-3.3-70b-versatile';
    }
}