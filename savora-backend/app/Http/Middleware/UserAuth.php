<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('user_id')) {
            // redirect()->guest() menyimpan intended URL ke session
            // sehingga setelah login bisa redirect()->intended() ke sini lagi
            return redirect()->guest(route('app.login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}