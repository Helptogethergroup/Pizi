<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Clean any buffered output (BOM, stray characters, etc.)
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'pizi.auth' => \App\Http\Middleware\PiziAuthenticate::class,
            'tenant.journey' => \App\Http\Middleware\EnsureTenantJourneyComplete::class,
            'owner.paid' => \App\Http\Middleware\EnsureOwnerHasPaid::class,
            'feature' => \App\Http\Middleware\CheckFeature::class,
        ]);
        
        // CRITICAL: Clean output before any response
        $middleware->append(\App\Http\Middleware\CleanOutput::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = 500;
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $status = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422);
                }
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Server error',
                ], $status);
            }
            return null;
        });
    })->create();