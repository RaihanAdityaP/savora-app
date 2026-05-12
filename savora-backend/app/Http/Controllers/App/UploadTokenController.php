<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class UploadTokenController extends Controller
{
    /**
     * POST /app/upload-token
     * Generate a Supabase signed upload URL so the browser can upload directly.
     * Tidak ada file yang melewati server Railway — hanya UUID path yang di-generate.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:image,video',
            'extension' => 'required|string|max:10',
            'mime_type' => 'required|string|max:100',
        ]);

        // Validasi mime type di server
        $allowedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $allowedVideos = ['video/mp4', 'video/quicktime', 'video/avi', 'video/webm'];

        $mime = $request->input('mime_type');
        $type = $request->input('type');

        if ($type === 'image' && ! in_array($mime, $allowedImages)) {
            return response()->json(['success' => false, 'message' => 'Tipe gambar tidak didukung'], 422);
        }
        if ($type === 'video' && ! in_array($mime, $allowedVideos)) {
            return response()->json(['success' => false, 'message' => 'Tipe video tidak didukung'], 422);
        }

        $ext    = strtolower($request->input('extension'));
        $bucket = $type === 'image' ? 'recipe-images' : 'recipe-videos';
        $path   = 'recipes/' . Str::uuid() . '.' . $ext;

        $supabaseUrl = config('services.supabase.url');
        $serviceKey  = config('services.supabase.service_key'); // pakai service_role key

        // Minta signed upload URL dari Supabase Storage API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $serviceKey,
            'apikey'        => $serviceKey,
        ])->post("{$supabaseUrl}/storage/v1/object/upload/sign/{$bucket}/{$path}");

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate upload URL: ' . $response->body(),
            ], 500);
        }

        $data = $response->json();

        return response()->json([
            'success'    => true,
            'upload_url' => $supabaseUrl . '/storage/v1' . $data['url'], // signed URL untuk PUT
            'token'      => $data['token'],
            'path'       => $path,
            'public_url' => $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $path,
        ]);
    }
}