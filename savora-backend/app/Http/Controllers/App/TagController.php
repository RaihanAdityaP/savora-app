<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class TagController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    // GET /app/tags
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $tags = [];
        try {
            $tags = $this->supabase->select(
                'tags',
                ['*'],
                ['is_approved' => true],
                ['order' => 'usage_count.desc', 'limit' => 50]
            );

            if ($query !== '') {
                $needle = strtolower($query);
                $tags   = array_values(array_filter($tags, fn($t) => str_contains(strtolower($t['name'] ?? ''), $needle)));
            }
        } catch (Exception) {}

        return view('app.tags', compact('tags', 'query'));
    }

    // POST /app/tags
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);

        try {
            $name = trim($request->input('name'));
            $slug = Str::slug($name);
            $userId = session('user_id');

            // Check duplicate
            $existing = $this->supabase->select('tags', ['id'], ['slug' => $slug]);
            if (!empty($existing)) {
                return back()->with('error', 'Tag "' . $name . '" sudah ada.');
            }

            $this->supabase->insert('tags', [
                'name'        => $name,
                'slug'        => $slug,
                'created_by'  => $userId,
                'is_approved' => false,
                'usage_count' => 0,
            ]);

            return back()->with('status', 'Tag "' . $name . '" berhasil dibuat dan menunggu persetujuan admin.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal membuat tag: ' . $e->getMessage());
        }
    }
}
