<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('welcome');
        }
    
        if ($request->header('Authorization') === null) {
            throw new AuthenticationException();
        }
    
        try {
            $this->authenticate($request, $this->guards());
        } catch (AuthenticationException $e) {
            throw new UnauthorizedHttpException('Unauthorized', $e->getMessage(), $e, 401);
        }
    
        return null;
    }
    
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }
    
        return redirect()->guest($this->redirectTo($request));
    } 
}
