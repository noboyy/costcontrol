<?php

use App\Http\Middleware\CheckActive;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureTenant;
use App\Http\Middleware\NoCache;
use App\Http\Middleware\NotSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
            'active' => CheckActive::class,
            'tenant' => EnsureTenant::class,
            'verified.user' => EnsureEmailVerified::class,
            'not-super-admin' => NotSuperAdmin::class,
        ]);

        $middleware->appendToGroup('web', NoCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
