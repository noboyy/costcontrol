<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenFromQuery
{
    /**
     * Jika request punya ?token= dan tidak ada Authorization header,
     * inject token ke header supaya Sanctum bisa autentikasi.
     * Dipakai untuk serve file (gambar/video/PDF) lewat <img src> / <video src>.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken() && $request->filled('token')) {
            $token = $request->query('token');
            $request->headers->set('Authorization', 'Bearer '.$token);
            $request->server->set('HTTP_AUTHORIZATION', 'Bearer '.$token);
        }

        return $next($request);
    }
}
