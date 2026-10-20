<?php

namespace App\Http\Middleware;

use App\Support\IdleTimeout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out users who have been inactive longer than their role allows.
 * Runs on every web request, after the session has started. The browser
 * script (public/js/idle-logout.js) is only the friendly warning; this is
 * the part that actually enforces the limit.
 */
class EnforceIdleTimeout
{
    public const SESSION_KEY = 'last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $minutes = IdleTimeout::minutesFor($user);
            $last = (int) $request->session()->get(self::SESSION_KEY, 0);

            if ($last > 0 && (time() - $last) > $minutes * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Logged out due to inactivity.'], 401);
                }

                return redirect()->route('login')->with(
                    'status',
                    "You were logged out after {$minutes} minutes of inactivity. Please log in again."
                );
            }

            $request->session()->put(self::SESSION_KEY, time());
        }

        return $next($request);
    }
}