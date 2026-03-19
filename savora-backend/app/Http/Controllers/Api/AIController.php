<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIChatService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AIController extends Controller
{
    private $aiChat;
    private $supabase;

    public function __construct(AIChatService $aiChat, SupabaseService $supabase)
    {
        $this->aiChat   = $aiChat;
        $this->supabase = $supabase;
    }

    // ─────────────────────────────────────────────
    // HELPER: ambil settings user (provider, model, api key)
    // ─────────────────────────────────────────────
    private function getUserSettings(Request $request): array
    {
        try {
            $userId   = $this->getSupabaseUserIdFromRequest($request);
            $settings = $this->supabase->select('user_ai_settings', ['*'], ['user_id' => $userId]);
            return $settings[0] ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function resolveProviderAndModel(array $settings): array
    {
        $provider = $settings['is_active_provider'] ?? 'groq';
        $model    = $provider === 'openrouter'
            ? ($settings['openrouter_model'] ?? 'meta-llama/llama-3.3-70b-instruct:free')
            : ($settings['groq_model']       ?? 'llama-3.3-70b-versatile');

        return [$provider, $model];
    }

    // ─────────────────────────────────────────────
    // POST /api/ai/ask
    // ─────────────────────────────────────────────
    public function askCookingQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question'       => 'required|string|max:1000',
            'recipe_context' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $messages = [];

            $recipeContext = $request->input('recipe_context', '');
            if (!empty($recipeContext)) {
                $messages[] = [
                    'role'    => 'system',
                    'content' => "Konteks Resep:\n{$recipeContext}",
                ];
            }

            $messages[] = [
                'role'    => 'user',
                'content' => $request->input('question'),
            ];

            $answer = $this->aiChat->chat($messages, $provider, $model, $settings);

            return response()->json([
                'success' => true,
                'data'    => ['answer' => $answer],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // POST /api/ai/analyze-image
    // ─────────────────────────────────────────────
    public function analyzeRecipeFromImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $image    = $request->file('image');
            $tempPath = $image->store('temp', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            // Buat caption sederhana dari nama file / ekstensi
            $caption = 'Gambar makanan yang dikirim pengguna';

            // Generate analisis menggunakan AI
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $prompt = "Berdasarkan gambar makanan yang dikirim, berikan analisis:\n"
                    . "1. Prediksi nama makanan\n"
                    . "2. Bahan-bahan utama yang mungkin digunakan\n"
                    . "3. Perkiraan cara memasak (3-5 langkah)\n"
                    . "4. Tips memasak makanan ini\n\n"
                    . "Jawab dalam Bahasa Indonesia dengan format yang rapi.";

            $messages = [['role' => 'user', 'content' => $prompt]];
            $analysis = $this->aiChat->chat($messages, $provider, $model, $settings);

            // Hapus temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'data'    => ['analysis' => $analysis],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // POST /api/ai/suggest-recipes
    // ─────────────────────────────────────────────
    public function suggestRecipes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ingredients'   => 'required|array|min:1',
            'ingredients.*' => 'string',
            'cuisine'       => 'nullable|string|max:50',
            'difficulty'    => 'nullable|string|in:mudah,sedang,sulit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $ingredientsStr = implode(', ', $request->input('ingredients'));
            $cuisineStr     = $request->input('cuisine')    ? "Jenis masakan: {$request->input('cuisine')}" : '';
            $difficultyStr  = $request->input('difficulty') ? "Tingkat kesulitan: {$request->input('difficulty')}" : '';

            $prompt = "Saya punya bahan-bahan: {$ingredientsStr}\n{$cuisineStr}\n{$difficultyStr}\n\n"
                    . "Sarankan 5 resep yang bisa saya buat. Untuk setiap resep berikan:\n\n"
                    . "RESEP 1:\nNama: [nama resep]\nDeskripsi: [deskripsi singkat]\nWaktu: [waktu dalam menit]\nTingkat: [mudah/sedang/sulit]\n\n"
                    . "RESEP 2:\n...\n\nDan seterusnya. Jawab dalam Bahasa Indonesia.";

            $messages     = [['role' => 'user', 'content' => $prompt]];
            $response     = $this->aiChat->chat($messages, $provider, $model, $settings);
            $suggestions  = $this->parseRecipeSuggestions($response);

            return response()->json([
                'success' => true,
                'data'    => ['suggestions' => $suggestions],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // POST /api/ai/generate-recipe
    // ─────────────────────────────────────────────
    public function generateRecipe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $description = $request->input('description');
            $prompt      = "Buatkan resep lengkap untuk: \"{$description}\"\n\n"
                         . "Format:\nJUDUL: [judul resep]\nDESKRIPSI: [deskripsi singkat]\n\n"
                         . "BAHAN-BAHAN:\n- [bahan 1 dengan takaran]\n...\n\n"
                         . "LANGKAH-LANGKAH:\n1. [langkah 1]\n...\n\n"
                         . "WAKTU MEMASAK: [X menit]\nPORSI: [X porsi]\nTINGKAT: [mudah/sedang/sulit]\n\nTIPS: [tips memasak]\n\n"
                         . "Jawab dalam Bahasa Indonesia.";

            $messages = [['role' => 'user', 'content' => $prompt]];
            $response = $this->aiChat->chat($messages, $provider, $model, $settings);
            $recipe   = $this->parseRecipeData($response);

            return response()->json([
                'success' => true,
                'data'    => ['recipe' => $recipe],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // POST /api/ai/suggest-variations
    // ─────────────────────────────────────────────
    public function suggestVariations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipe_title' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $recipeTitle = $request->input('recipe_title');
            $prompt      = "Resep: {$recipeTitle}\n\n"
                         . "Sarankan 3 variasi kreatif dari resep ini dalam Bahasa Indonesia.\n"
                         . "Format setiap variasi:\n1. [Variasi dan penjelasannya]\n2. ...\n3. ...";

            $messages   = [['role' => 'user', 'content' => $prompt]];
            $response   = $this->aiChat->chat($messages, $provider, $model, $settings);
            $variations = $this->parseVariations($response);

            return response()->json([
                'success' => true,
                'data'    => ['variations' => $variations],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // GET /api/ai/test
    // ─────────────────────────────────────────────
    public function testConnection(Request $request)
    {
        try {
            $settings = $this->getUserSettings($request);
            [$provider, $model] = $this->resolveProviderAndModel($settings);

            $messages    = [['role' => 'user', 'content' => 'Halo, balas dengan: OK']];
            $response    = $this->aiChat->chat($messages, $provider, $model, $settings);
            $isConnected = !empty($response);

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected
                    ? "AI service connected ({$provider} / {$model})"
                    : 'AI service connection failed',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // PARSERS
    // ─────────────────────────────────────────────

    private function parseRecipeSuggestions(string $text): array
    {
        $suggestions = [];
        preg_match_all('/RESEP \d+:(.*?)(?=RESEP \d+:|$)/is', $text, $matches);

        foreach ($matches[1] as $recipe) {
            preg_match('/Nama:\s*(.+)/i',    $recipe, $nameMatch);
            preg_match('/Deskripsi:\s*(.+)/i',$recipe, $descMatch);
            preg_match('/Waktu:\s*(\d+)/i',  $recipe, $timeMatch);
            preg_match('/Tingkat:\s*(\w+)/i', $recipe, $diffMatch);

            if ($nameMatch) {
                $suggestions[] = [
                    'name'        => trim($nameMatch[1]),
                    'description' => isset($descMatch[1]) ? trim($descMatch[1]) : 'Resep lezat',
                    'time'        => isset($timeMatch[1]) ? $timeMatch[1] . ' menit' : '30 menit',
                    'difficulty'  => isset($diffMatch[1]) ? trim($diffMatch[1]) : 'sedang',
                ];
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    private function parseRecipeData(string $text): array
    {
        return [
            'title'        => $this->extractValue($text, ['JUDUL:', 'Judul:'], 'Resep Generated'),
            'description'  => $this->extractValue($text, ['DESKRIPSI:', 'Deskripsi:'], 'Resep lezat'),
            'ingredients'  => $this->extractListItems($text, ['BAHAN-BAHAN:', 'BAHAN:']),
            'steps'        => $this->extractListItems($text, ['LANGKAH-LANGKAH:', 'LANGKAH:']),
            'cooking_time' => $this->extractNumber($text, ['WAKTU MEMASAK:', 'Waktu:']),
            'servings'     => $this->extractNumber($text, ['PORSI:', 'Porsi:']),
            'difficulty'   => $this->extractValue($text, ['TINGKAT:', 'Tingkat:'], 'sedang'),
            'tips'         => $this->extractValue($text, ['TIPS:', 'Tips:'], ''),
        ];
    }

    private function parseVariations(string $text): array
    {
        $variations = [];
        foreach (explode("\n", $text) as $line) {
            $trimmed = trim($line);
            if (preg_match('/^[\d•\-]+\.?\s*(.+)/', $trimmed, $match)) {
                $variation = trim($match[1]);
                if (!empty($variation)) {
                    $variations[] = $variation;
                }
            }
        }
        return array_slice($variations, 0, 3);
    }

    private function extractValue(string $text, array $keywords, string $default): string
    {
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '\s*(.+?)(?=\n\n|\n[A-Z]|\Z)/is', $text, $match)) {
                return trim($match[1]) ?: $default;
            }
        }
        return $default;
    }

    private function extractNumber(string $text, array $keywords): int
    {
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '\s*(\d+)/i', $text, $match)) {
                return intval($match[1]) ?: 30;
            }
        }
        return 30;
    }

    private function extractListItems(string $text, array $keywords): array
    {
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '(.+?)(?=\n\n|\n[A-Z][A-Z]|\Z)/is', $text, $sectionMatch)) {
                $items = [];
                foreach (explode("\n", $sectionMatch[1]) as $line) {
                    $cleaned = preg_replace('/^[-•\d.]+\s*/', '', trim($line));
                    if (!empty($cleaned)) {
                        $items[] = $cleaned;
                    }
                }
                if (!empty($items)) return $items;
            }
        }
        return ['Tidak ada data'];
    }
}