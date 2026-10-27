<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->reportable(function (Throwable $e): void {
            // #region agent log
            @file_put_contents('/home/ammar/Projects/pt-sanmal/.cursor/debug-ba0568.log', json_encode([
                'sessionId' => 'ba0568',
                'hypothesisId' => 'A',
                'location' => 'bootstrap/app.php:reportable',
                'message' => 'uncaught exception',
                'data' => [
                    'class' => $e::class,
                    'msg' => $e->getMessage(),
                    'path' => request()->path(),
                    'route' => request()->route()?->getName(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]).PHP_EOL, FILE_APPEND);
            // #endregion
        });
    })->create();
