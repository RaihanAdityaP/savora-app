<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class LoginController extends Controller
{
    public function __construct(
        private SupabaseAuthService $supabaseAuth,
        private SupabaseService $supabase,
    ) {}

    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('app.home');
        }
        return view('app.login');
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

            // Cek profile di Supabase
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

            if ($profile['is_banned'] ?? false) {
                return back()->withInput(['email' => $email])
                    ->with('error', 'Akun ini telah dinonaktifkan.');
            }

            // Redirect admin ke admin panel
            if (($profile['role'] ?? '') === 'admin') {
                return redirect()->route('admin.dashboard')
                    ->with('status', 'Login sebagai admin.');
            }

            session()->regenerate();
            session([
                'user_id'       => $profile['id'],
                'user_username' => $profile['username'] ?? $profile['full_name'] ?? 'User',
                'user_role'     => $profile['role'] ?? 'user',
                'user_avatar'   => $profile['avatar_url'] ?? null,
            ]);

            return redirect()->route('app.home');

        } catch (Exception $e) {
            return back()->withInput(['email' => $email])
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget(['user_id', 'user_username', 'user_role', 'user_avatar']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('app.login')
            ->with('status', 'Berhasil logout.');
    }

    public function showRegister()
    {
        if (session('user_id')) {
            return redirect()->route('app.home');
        }
        return view('app.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50',
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email',
            'password'  => 'required|string|min:6',
        ]);

        try {
            $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
            $anonKey     = env('SUPABASE_KEY');

            $signupResp = Http::withHeaders([
                'apikey'       => $anonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/signup", [
                'email'    => trim($request->input('email')),
                'password' => $request->input('password'),
                'data'     => [
                    'username'  => trim($request->input('username')),
                    'full_name' => trim($request->input('full_name')),
                ],
            ]);

            if (! $signupResp->successful()) {
                $msg = $signupResp->json('msg') ?? $signupResp->json('message') ?? 'Registrasi gagal.';
                return back()->withInput()->with('error', $msg);
            }

            return redirect()->route('app.login')
                ->with('status', 'Registrasi berhasil! Cek email untuk verifikasi.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}