<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIService
{
    private $groqApiKey;
    private $groqModel;
    private $groqBaseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    
    private $hfApiKey;
    private $imageModel = 'nlpconnect/vit-gpt2-image-captioning';
    
    private $maxRetries = 3;
    private $retryDelay = 2; // seconds
    private $requestTimeout = 30; // seconds

    public function __construct()
    {
        $this->groqApiKey = env('GROQ_API_KEY');
        $this->groqModel = env('GROQ_MODEL', 'llama-3.3-70b-versatile');
        $this->hfApiKey = env('HF_API_KEY');
        
        if (empty($this->groqApiKey)) {
            Log::error('GROQ_API_KEY not found in environment!');
        }
        if (empty($this->hfApiKey)) {
            Log::warning('HF_API_KEY not found in environment!');
        }
    }

    /**
     * Retry logic wrapper
     */
    private function retryRequest(callable $request, string $operation = 'request')
    {
        $attempts = 0;
        
        while ($attempts < $this->maxRetries) {
            try {
                $attempts++;
                Log::debug("Attempt $attempts/{$this->maxRetries} for $operation");
                return $request();
            } catch (Exception $e) {
                Log::warning("Attempt $attempts failed for $operation: " . $e->getMessage());
                
                if ($attempts >= $this->maxRetries) {
                    Log::error("All retry attempts failed for $operation");
                    throw $e;
                }
                
                sleep($this->retryDelay * $attempts);
            }
        }
        
        throw new Exception("Max retries exceeded for $operation");
    }

    /**
     * 1. Cooking Assistant Chatbot with Groq
     */
    public function askCookingQuestion(string $question, string $recipeContext = ''): string
    {
        if (empty($this->groqApiKey)) {
            throw new Exception('GROQ_API_KEY tidak ditemukan. Periksa file .env Anda.');
        }
        
        return $this->retryRequest(function() use ($question, $recipeContext) {
            Log::debug('Sending cooking question to Groq AI...');
            
            $messages = [
                [
                    'role' => 'system',
                    'content' => "Anda adalah Chef AI Savora, asisten koki profesional Indonesia yang ramah dan membantu.

Tugas Anda:
- Menjawab pertanyaan tentang memasak dalam Bahasa Indonesia
- Memberikan tips praktis dan mudah dipahami
- Menjelaskan dengan detail tapi tidak bertele-tele
- Selalu ramah dan suportif
- Jika ada konteks resep, gunakan untuk memberikan jawaban yang lebih spesifik

Konteks Resep: $recipeContext"
                ],
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ];

            $response = Http::timeout($this->requestTimeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->groqBaseUrl, [
                    'model' => $this->groqModel,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                    'top_p' => 0.9,
                    'stream' => false,
                ]);

            Log::debug('Groq Response Status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['choices'][0]['message']['content'])) {
                    $content = $data['choices'][0]['message']['content'];
                    if (!empty($content)) {
                        return trim($content);
                    }
                }
                
                throw new Exception('Invalid response format from Groq');
            } elseif ($response->status() === 401) {
                throw new Exception('API key Groq tidak valid. Periksa API key Anda.');
            } elseif ($response->status() === 429) {
                throw new Exception('Terlalu banyak permintaan. Tunggu sebentar...');
            } elseif ($response->status() === 503) {
                throw new Exception('Server Groq sedang sibuk. Coba lagi...');
            } else {
                Log::error('Groq Error: ' . $response->status());
                Log::error('Response: ' . $response->body());
                throw new Exception('Error ' . $response->status() . ': Gagal menghubungi Groq AI');
            }
        }, 'askCookingQuestion');
    }

    /**
     * 2. Analyze Recipe from Image (Hybrid: HF for image, Groq for text)
     */
    public function analyzeRecipeFromImage(string $imagePath): string
    {
        if (empty($this->hfApiKey)) {
            throw new Exception('HF_API_KEY tidak ditemukan. Periksa file .env Anda.');
        }
        
        return $this->retryRequest(function() use ($imagePath) {
            Log::debug('Reading image file: ' . $imagePath);
            
            if (!file_exists($imagePath)) {
                throw new Exception('File gambar tidak ditemukan');
            }
            
            $imageBytes = file_get_contents($imagePath);
            $base64Image = base64_encode($imageBytes);

            Log::debug('Analyzing image with Hugging Face...');
            
            // Step 1: Get image caption from Hugging Face
            $response = Http::timeout($this->requestTimeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->hfApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://api-inference.huggingface.co/models/{$this->imageModel}", [
                    'inputs' => $base64Image,
                    'options' => ['wait_for_model' => true]
                ]);

            $caption = 'Gambar makanan';
            
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && !empty($data)) {
                    $caption = $data[0]['generated_text'] ?? 'Gambar makanan';
                }
            }

            Log::debug('Image caption: ' . $caption);
            
            // Step 2: Generate recipe using Groq based on caption
            return $this->generateRecipeFromCaption($caption);
        }, 'analyzeRecipeFromImage');
    }

    /**
     * Generate recipe from caption
     */
    private function generateRecipeFromCaption(string $caption): string
    {
        $prompt = "Berdasarkan gambar makanan dengan deskripsi: \"$caption\"

Tolong analisis dan berikan:
1. Prediksi nama makanan (jika terlihat jelas)
2. Bahan-bahan utama yang mungkin digunakan
3. Perkiraan cara memasak (3-5 langkah)
4. Tips memasak makanan ini

Jawab dalam Bahasa Indonesia dengan format yang rapi.";

        try {
            return $this->askCookingQuestion($prompt, 'Analisis gambar makanan');
        } catch (Exception $e) {
            Log::error('Error generating recipe: ' . $e->getMessage());
            return "Analisis Gambar: $caption\n\nMaaf, tidak dapat menghasilkan resep lengkap saat ini. \nSilakan coba lagi atau tanyakan langsung!";
        }
    }

    /**
     * 3. Smart Recipe Suggestions
     */
    public function suggestRecipes(array $availableIngredients, ?string $cuisine = null, ?string $difficulty = null): array
    {
        return $this->retryRequest(function() use ($availableIngredients, $cuisine, $difficulty) {
            $ingredientsStr = implode(", ", $availableIngredients);
            $cuisineStr = $cuisine ? "Jenis masakan: $cuisine" : '';
            $difficultyStr = $difficulty ? "Tingkat kesulitan: $difficulty" : '';

            $prompt = "Saya punya bahan-bahan: $ingredientsStr
$cuisineStr
$difficultyStr

Sarankan 5 resep yang bisa saya buat. Untuk setiap resep berikan dalam format:

RESEP 1:
Nama: [nama resep]
Deskripsi: [deskripsi singkat]
Waktu: [waktu dalam menit]
Tingkat: [mudah/sedang/sulit]

RESEP 2:
...

Dan seterusnya. Jawab dalam Bahasa Indonesia.";

            $response = $this->askCookingQuestion($prompt, 'Saran resep');
            return $this->parseRecipeSuggestions($response);
        }, 'suggestRecipes');
    }

    /**
     * 4. Generate Recipe from Description
     */
    public function generateRecipe(string $description): array
    {
        return $this->retryRequest(function() use ($description) {
            $prompt = "Buatkan resep lengkap untuk: \"$description\"

Format:
JUDUL: [judul resep]
DESKRIPSI: [deskripsi singkat]

BAHAN-BAHAN:
- [bahan 1 dengan takaran]
- [bahan 2 dengan takaran]
...

LANGKAH-LANGKAH:
1. [langkah 1]
2. [langkah 2]
...

WAKTU MEMASAK: [X menit]
PORSI: [X porsi]
TINGKAT: [mudah/sedang/sulit]

TIPS: [tips memasak]

Jawab dalam Bahasa Indonesia dengan format yang jelas.";

            $response = $this->askCookingQuestion($prompt, 'Generate resep');
            return $this->parseRecipeData($response);
        }, 'generateRecipe');
    }

    /**
     * 5. Recipe Variation Suggestions
     */
    public function suggestVariations(string $recipeTitle): array
    {
        return $this->retryRequest(function() use ($recipeTitle) {
            $prompt = "Resep: $recipeTitle

Sarankan 3 variasi kreatif dari resep ini dalam Bahasa Indonesia.
Format setiap variasi sebagai poin terpisah dengan penjelasan singkat.

1. [Variasi 1 dan penjelasannya]
2. [Variasi 2 dan penjelasannya]
3. [Variasi 3 dan penjelasannya]";

            $response = $this->askCookingQuestion($prompt, 'Variasi resep');
            return $this->parseVariations($response);
        }, 'suggestVariations');
    }

    /**
     * Parse recipe suggestions
     */
    private function parseRecipeSuggestions(string $text): array
    {
        $suggestions = [];
        
        try {
            preg_match_all('/RESEP \d+:(.*?)(?=RESEP \d+:|$)/is', $text, $matches);
            
            foreach ($matches[1] as $recipe) {
                preg_match('/Nama:\s*(.+)/i', $recipe, $nameMatch);
                preg_match('/Deskripsi:\s*(.+)/i', $recipe, $descMatch);
                preg_match('/Waktu:\s*(\d+)/i', $recipe, $timeMatch);
                preg_match('/Tingkat:\s*(\w+)/i', $recipe, $diffMatch);
                
                if ($nameMatch) {
                    $suggestions[] = [
                        'name' => trim($nameMatch[1]),
                        'description' => isset($descMatch[1]) ? trim($descMatch[1]) : 'Resep lezat',
                        'time' => isset($timeMatch[1]) ? $timeMatch[1] . ' menit' : '30 menit',
                        'difficulty' => isset($diffMatch[1]) ? trim($diffMatch[1]) : 'sedang',
                    ];
                }
            }
            
            return array_slice($suggestions, 0, 5);
        } catch (Exception $e) {
            Log::error('Error parsing suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse recipe data
     */
    private function parseRecipeData(string $text): array
    {
        try {
            return [
                'title' => $this->extractValue($text, ['JUDUL:', 'Judul:'], 'Resep Generated'),
                'description' => $this->extractValue($text, ['DESKRIPSI:', 'Deskripsi:'], 'Resep lezat'),
                'ingredients' => $this->extractListItems($text, ['BAHAN-BAHAN:', 'BAHAN:']),
                'steps' => $this->extractListItems($text, ['LANGKAH-LANGKAH:', 'LANGKAH:']),
                'cooking_time' => $this->extractNumber($text, ['WAKTU MEMASAK:', 'Waktu:']),
                'servings' => $this->extractNumber($text, ['PORSI:', 'Porsi:']),
                'difficulty' => $this->extractValue($text, ['TINGKAT:', 'Tingkat:'], 'sedang'),
                'tips' => $this->extractValue($text, ['TIPS:', 'Tips:'], ''),
            ];
        } catch (Exception $e) {
            Log::error('Error parsing recipe: ' . $e->getMessage());
            return [
                'title' => 'Resep Generated',
                'description' => strlen($text) > 200 ? substr($text, 0, 200) : $text,
                'ingredients' => [],
                'steps' => [],
                'cooking_time' => 30,
                'servings' => 4,
                'difficulty' => 'sedang',
                'tips' => '',
            ];
        }
    }

    private function extractValue(string $text, array $keywords, string $defaultValue): string
    {
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '\s*(.+?)(?=\n\n|\n[A-Z]|\Z)/is', $text, $match)) {
                return trim($match[1]) ?: $defaultValue;
            }
        }
        return $defaultValue;
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
        $items = [];
        
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '(.+?)(?=\n\n|\n[A-Z][A-Z]|\Z)/is', $text, $sectionMatch)) {
                $section = $sectionMatch[1];
                $lines = explode("\n", $section);
                
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (!empty($trimmed)) {
                        // Remove bullets and numbers
                        $cleaned = preg_replace('/^[-•\d.]+\s*/', '', $trimmed);
                        if (!empty($cleaned)) {
                            $items[] = $cleaned;
                        }
                    }
                }
                
                if (!empty($items)) break;
            }
        }
        
        return !empty($items) ? $items : ['Tidak ada data'];
    }

    private function parseVariations(string $text): array
    {
        $variations = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;
            
            if (preg_match('/^[\d•\-]+\.?\s*(.+)/', $trimmed, $match)) {
                $variation = trim($match[1]);
                if (!empty($variation)) {
                    $variations[] = $variation;
                }
            }
        }
        
        return array_slice($variations, 0, 3);
    }

    /**
     * Test connection
     */
    public function testConnection(): bool
    {
        try {
            Log::debug('Testing Groq AI connection...');
            $response = $this->askCookingQuestion('Halo', 'Test');
            $isSuccess = stripos($response, 'error') === false;
            Log::debug('Connection test: ' . ($isSuccess ? 'SUCCESS ✓' : 'FAILED ✗'));
            return $isSuccess;
        } catch (Exception $e) {
            Log::error('Connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}