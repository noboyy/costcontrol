<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTrial
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // SUPER ADMIN and ADMIN bypass trial enforcement
        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        if ($user->isTrialExpired()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Masa trial Anda telah berakhir. Silakan hubungi administrator untuk perpanjang.',
                ], 403);
            }

            auth()->logout();

            return redirect('/login')
                ->with('error', 'Masa trial Anda telah berakhir. Silakan hubungi administrator untuk perpanjang.');
        }

        return $next($request);
    }
}