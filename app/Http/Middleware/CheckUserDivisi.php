<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserDivisi
{
    public function handle($request, Closure $next, ...$divisis)
    {
        $user = Auth::guard('api')->user();
        if ($user && in_array($user->divisi_id, $divisis)) {
            // Jika user memiliki divisi yang diperbolehkan, maka kita lanjutkan request
            return $next($request);
        }

        // Jika user tidak memiliki divisi yang diperbolehkan, maka kita kembalikan response 403 Forbidden
        return response()->json([
            'message' => 'Access denied divisi'
        ], 403);
    }
}
