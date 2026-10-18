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
            'logout', 'linky.chat',
            'privacy',
        ];

        if ($user->role === 'alumni') {
            // The tracer study gate itself lives in EnsureTracerStudyAccessed.
            $allowedRoutes[] = 'tracer-study.show';
            $allowedRoutes[] = 'tracer-study.access';
        }

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('profile.complete');
    }
}