<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAE
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has division 2 and role 3 or 4
        if ($user->divisi_id == 2 && ( $user->role_id == 4||$user->role_id == 3)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden divisi'], 403);
    }
}
