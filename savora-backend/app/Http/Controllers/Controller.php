<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RuntimeException;

abstract class Controller
{
    protected function getSupabaseUserIdFromRequest(Request $request): string
    {
        $supabaseUserId = $request->user()?->supabase_user_id;

        if (!$supabaseUserId) {
            throw new RuntimeException('Supabase user mapping not found for authenticated account');
        }

        return $supabaseUserId;
    }
}