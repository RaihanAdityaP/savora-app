<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AIController extends Controller
{
    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Ask cooking question
     * POST /api/ai/ask
     */
    public function askCookingQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'recipe_context' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $question = $request->input('question');
            $recipeContext = $request->input('recipe_context', '');

            $answer = $this->aiService->askCookingQuestion($question, $recipeContext);

            return response()->json([
                'success' => true,
                'data' => [
                    'answer' => $answer,
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
     * Analyze recipe from image
     * POST /api/ai/analyze-image
     */
    public function analyzeRecipeFromImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $image = $request->file('image');
            $imagePath = $image->store('temp', 'local');
            $fullPath = storage_path('app/' . $imagePath);

            $analysis = $this->aiService->analyzeRecipeFromImage($fullPath);

            // Clean up temp file
            unlink($fullPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'analysis' => $analysis,
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
     * Suggest recipes based on ingredients
     * POST /api/ai/suggest-recipes
     */
    public function suggestRecipes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'string',
            'cuisine' => 'nullable|string|max:50',
            'difficulty' => 'nullable|string|in:mudah,sedang,sulit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ingredients = $request->input('ingredients');
            $cuisine = $request->input('cuisine');
            $difficulty = $request->input('difficulty');

            $suggestions = $this->aiService->suggestRecipes($ingredients, $cuisine, $difficulty);

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
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
     * Generate recipe from description
     * POST /api/ai/generate-recipe
     */
    public function generateRecipe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $description = $request->input('description');
            $recipe = $this->aiService->generateRecipe($description);

            return response()->json([
                'success' => true,
                'data' => [
                    'recipe' => $recipe,
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
     * Suggest recipe variations
     * POST /api/ai/suggest-variations
     */
    public function suggestVariations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipe_title' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $recipeTitle = $request->input('recipe_title');
            $variations = $this->aiService->suggestVariations($recipeTitle);

            return response()->json([
                'success' => true,
                'data' => [
                    'variations' => $variations,
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
     * Test AI connection
     * GET /api/ai/test
     */
    public function testConnection()
    {
        try {
            $isConnected = $this->aiService->testConnection();

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected ? 'AI service connected successfully' : 'AI service connection failed',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}   