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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'operator' => \App\Http\Middleware\OperatorAuth::class,
            'operator.role' => \App\Http\Middleware\EnsureOperatorRole::class,
            'operator.permission' => \App\Http\Middleware\EnsureOperatorPermission::class,
            'module.enabled' => \App\Http\Middleware\EnsureModuleEnabled::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
