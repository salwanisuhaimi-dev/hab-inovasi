<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileIsComplete::class,
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: [
            'back_to_pitch', // Beritahu Laravel jangan encrypt cookie ini
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
