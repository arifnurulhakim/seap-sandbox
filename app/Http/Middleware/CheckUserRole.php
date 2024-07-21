<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::guard('api')->user();
        if ($user && in_array($user->role_id, $roles)) {
            // Jika user memiliki role yang diperbolehkan, maka kita lanjutkan request
            return $next($request);
        }

        // Jika user tidak memiliki role yang diperbolehkan, maka kita kembalikan response 403 Forbidden
        return response()->json([
            'message' => 'Access denied by role'
        ], 403);
    }
}
