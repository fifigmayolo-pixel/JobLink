<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['student', 'alumni'])) {
            return $next($request);
        }

        if ($user->profile?->profile_completed_at) {
            return $next($request);
        }

        $allowedRoutes = [
            'terms.show', 'terms.accept',
            'profile.complete', 'profile.complete.store',
            'profile.complete.cities', 'profile.complete.barangays',
            'password.change', 'password.change.store',
            'logout',
        ];

        if ($user->role === 'alumni') {
            $allowedRoutes[] = 'tracer-study.show';
        }

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // Alumni see the tracer-study intro first; students go straight to the wizard
        return $user->role === 'alumni'
            ? redirect()->route('tracer-study.show')
            : redirect()->route('profile.complete');
    }
}