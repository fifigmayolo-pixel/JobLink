<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['student', 'alumni'])) {
            return $next($request);
        }

        // Terms are agreed to inside Complete Profile (checkbox + popup), so
        // anyone who hasn't finished their profile is handled there.
        if (! $user->profile?->profile_completed_at) {
            return $next($request);
        }

        if ($user->terms_accepted_at) {
            return $next($request);
        }

        // Fallback: profile completed under the old flow, terms never accepted.
        $allowedRoutes = [
            'terms.show', 'terms.accept',
            'password.change', 'password.change.store',
            'logout','linky.chat',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('terms.show');
    }
}