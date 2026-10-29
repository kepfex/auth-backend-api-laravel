<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'La cuenta está deshabilitada.'], 403);
        }

        return $next($request);
    }
}
