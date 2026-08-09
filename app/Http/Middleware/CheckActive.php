<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect('/');
        }

        $user = auth()->user();

        if ($user->is_active !== '1') {
            auth()->logout();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda tidak aktif.'], 403);
            }

            return redirect('/')->with('error', 'Akun Anda tidak aktif. Silakan hubungi administrator.');
        }

        return $next($request);
    }
}
