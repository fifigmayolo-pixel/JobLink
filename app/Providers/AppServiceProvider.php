<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Configure application-wide services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Force HTTPS in production.
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Set the default password strength rules.
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Share unread notification count with the main layout.
        View::composer('layouts.app', function ($view) {
            $view->with(
                'unreadNotificationCount',
                auth()->check()
                    ? auth()->user()->notifications()->unread()->count()
                    : 0
            );
        });

        // Limit login attempts to 5 per minute per identifier + IP.
        RateLimiter::for('login', function (Request $request) {
            $id = Str::lower(
                (string) $request->input(
                    'login',
                    $request->input(
                        'email',
                        $request->input('student_id_number', '')
                    )
                )
            );

            return Limit::perMinute(5)
                ->by($id . '|' . $request->ip())
                ->response(function (
                    Request $request,
                    array $headers
                ) {
                    $seconds = (int) (
                        $headers['Retry-After'] ?? 60
                    );

                    return back()
                        ->withInput($request->except('password'))
                        ->withErrors([
                            'throttle' => "Too many login attempts. Please try again in {$seconds} seconds."
                        ]);
                });
        });
    }
}
