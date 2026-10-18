<?php

namespace App\Http\Middleware;

use App\Models\TracerStudyLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTracerStudyAccessed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'alumni') {
            return $next($request);
        }

        $link = TracerStudyLink::current();

        // No active study = nothing to require (never lock everyone out).
        if (! $link || $link->accessedBy($user)) {
            return $next($request);
        }

        $allowedRoutes = [
            'tracer-study.show', 'tracer-study.access',
            'terms.show', 'terms.accept',
            'password.change', 'password.change.store',
            'logout', 'linky.chat',
            'privacy',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('tracer-study.show');
    }
}