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

        if (! $user || ! in_array($user->role, ['student', 'alumni']) || $user->terms_accepted_at) {
            return $next($request);
        }

        if (! in_array($request->route()?->getName(), ['terms.show', 'terms.accept', 'logout'])) {
            return redirect()->route('terms.show');
        }

        return $next($request);
    }
}