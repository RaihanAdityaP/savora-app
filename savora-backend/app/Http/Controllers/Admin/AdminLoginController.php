<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class AdminLoginController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function showLogin()
    {
        if (session('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email    = trim($request->input('email'));
        $password = $request->input('password');

        try {
            // Step 1: Verifikasi email + password ke Supabase Auth
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_KEY');

            $authResp = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
                'email'    => $email,
                'password' => $password,
            ]);

            if (! $authResp->successful()) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Email atau password salah.');
            }

            $userId = $authResp->json('user.id');

            if (! $userId) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Gagal mendapatkan data user.');
            }

            // Step 2: Cek role = 'admin' di tabel profiles (id = auth.users id)
            $profiles = $this->supabase->select(
                'profiles',
                ['id', 'username', 'full_name', 'role', 'is_banned', 'avatar_url'],
                ['id' => $userId]
            );

            if (empty($profiles)) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Profil user tidak ditemukan.');
            }

            $profile = $profiles[0];

            if (($profile['role'] ?? '') !== 'admin') {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Akses ditolak. Hanya admin yang diizinkan.');
            }

            if ($profile['is_banned'] ?? false) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Akun ini telah dinonaktifkan.');
            }

            // Step 3: Simpan ke session
            session()->regenerate();
            session([
                'admin_id'       => $profile['id'],
                'admin_username' => $profile['username'] ?? $profile['full_name'] ?? 'Admin',
                'admin_role'     => $profile['role'],
            ]);

            return redirect()->route('admin.dashboard');

        } catch (Exception $e) {
            return back()->withInput(['email' => $email])
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget(['admin_id', 'admin_username', 'admin_role']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'Berhasil logout.');
    }
}