<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Exception;

class FavoriteController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    // GET /app/favorites
    public function index()
    {
        $userId  = session('user_id');
        $boards  = [];
        $previews = [];

        try {
            $boards = $this->supabase->select(
                'recipe_boards',
                ['*'],
                ['user_id' => $userId],
                ['order' => 'created_at.desc']
            );

            foreach ($boards as &$board) {
                $recipes = $this->supabase->select('board_recipes', ['recipes(id, image_url, title)'], ['board_id' => $board['id']], ['limit' => 4]);
                $board['recipe_count'] = count($this->supabase->select('board_recipes', ['id'], ['board_id' => $board['id']]));
                $previews[$board['id']] = array_map(fn($br) => $br['recipes'], $recipes);
            }
        } catch (Exception) {}

        return view('app.favorites', compact('boards', 'previews'));
    }

    // GET /app/favorites/{boardId}
    public function show(string $boardId)
    {
        $userId = session('user_id');

        try {
            $boards = $this->supabase->select('recipe_boards', ['*'], ['id' => $boardId, 'user_id' => $userId]);
            if (empty($boards)) abort(404);

            $board = $boards[0];

            $boardRecipes = $this->supabase->select(
                'board_recipes',
                ['*, recipes(*, profiles!recipes_user_id_fkey(username, avatar_url), categories(name))'],
                ['board_id' => $boardId],
                ['order' => 'added_at.desc']
            );

            $recipes = array_map(fn($br) => $br['recipes'], $boardRecipes);

            return view('app.favorites-board', compact('board', 'recipes', 'boardRecipes'));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // POST /app/favorites/boards
    public function createBoard(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $userId = session('user_id');
            $this->supabase->insert('recipe_boards', [
                'user_id'     => $userId,
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
            ]);

            return back()->with('status', 'Koleksi berhasil dibuat.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/favorites/boards/{boardId}/update
    public function updateBoard(Request $request, string $boardId)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $userId = session('user_id');
            $this->supabase->update('recipe_boards', [
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
            ], ['id' => $boardId, 'user_id' => $userId]);

            return back()->with('status', 'Koleksi berhasil diperbarui.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/favorites/boards/{boardId}/delete
    public function deleteBoard(string $boardId)
    {
        try {
            $userId = session('user_id');
            $this->supabase->delete('board_recipes', ['board_id' => $boardId]);
            $this->supabase->delete('recipe_boards', ['id' => $boardId, 'user_id' => $userId]);

            return redirect()->route('app.favorites')->with('status', 'Koleksi berhasil dihapus.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/favorites/save
    public function save(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|string',
            'board_id'  => 'nullable|string',
        ]);

        try {
            $userId   = session('user_id');
            $recipeId = $request->input('recipe_id');
            $boardId  = $request->input('board_id');

            // Kalau tidak ada board_id, pakai atau buat board default
            if (! $boardId) {
                $defaults = $this->supabase->select('recipe_boards', ['id'], ['user_id' => $userId, 'name' => 'Favorit Saya']);
                if (empty($defaults)) {
                    $board   = $this->supabase->insert('recipe_boards', ['user_id' => $userId, 'name' => 'Favorit Saya']);
                    $boardId = $board[0]['id'];
                } else {
                    $boardId = $defaults[0]['id'];
                }
            }

            // Cek sudah ada
            $existing = $this->supabase->select('board_recipes', ['id'], ['board_id' => $boardId, 'recipe_id' => $recipeId]);
            if (! empty($existing)) {
                return back()->with('error', 'Resep sudah ada di koleksi ini.');
            }

            $this->supabase->insert('board_recipes', ['board_id' => $boardId, 'recipe_id' => $recipeId]);

            return back()->with('status', 'Resep berhasil disimpan ke koleksi.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // POST /app/favorites/remove
    public function remove(Request $request)
    {
        $request->validate([
            'board_id'  => 'required|string',
            'recipe_id' => 'required|string',
        ]);

        try {
            $this->supabase->delete('board_recipes', [
                'board_id'  => $request->input('board_id'),
                'recipe_id' => $request->input('recipe_id'),
            ]);

            return back()->with('status', 'Resep dihapus dari koleksi.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}