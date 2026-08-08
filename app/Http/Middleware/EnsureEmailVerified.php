<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only self-registered USER accounts need email verification.
        if (! $user || $user->isAdmin() || ! Features::enabled(Features::emailVerification())) {
            return $next($request);
        }

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&
            ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email belum diverifikasi.'], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}