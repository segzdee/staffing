<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases for OvertimeStaff
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'worker' => \App\Http\Middleware\WorkerMiddleware::class,
            'business' => \App\Http\Middleware\BusinessMiddleware::class,
            'agency' => \App\Http\Middleware\AgencyMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            // 'api.agent' => \App\Http\Middleware\ApiAgentMiddleware::class,
        ]);

        // Web middleware group is automatically registered
        // API middleware group is automatically registered
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
