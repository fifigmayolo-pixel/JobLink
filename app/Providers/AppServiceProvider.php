<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Password::defaults() is used everywhere a password gets set (login
     * change, self-activation) — configuring it here once means every one
     * of those inherits the same strength rule automatically.
     */
    public function boot(): void
    {


    if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
        
        Password::defaults(function () {
            return Password::min(8)->mixedCase()->numbers()->symbols();
        });

        View::composer('layouts.app', function ($view) {
            $view->with('unreadNotificationCount', auth()->check()
                ? auth()->user()->notifications()->unread()->count()
                : 0
            );
        });
    }
}