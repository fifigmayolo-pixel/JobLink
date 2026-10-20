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
    $middleware->trustProxies(at: '*');
    $middleware->web(append: [\App\Http\Middleware\EnforceIdleTimeout::class]); 

    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);

        $middleware->web(append: [
        \App\Http\Middleware\ForcePasswordChange::class,
        \App\Http\Middleware\EnsureTermsAccepted::class,
        \App\Http\Middleware\EnsureTracerStudyAccessed::class,
        \App\Http\Middleware\EnsureProfileComplete::class,
    ]);
})
    
->withExceptions(
    function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Symfony\Component\Mailer\Exception\TransportExceptionInterface $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withErrors([
                'mail' => 'The email could not be sent right now. Please try again in a few minutes.'
            ]);
        });
    }
)->create();
