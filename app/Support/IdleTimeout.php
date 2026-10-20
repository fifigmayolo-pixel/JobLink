<?php

namespace App\Support;

use App\Models\User;

/**
 * How long a logged-in user may be inactive before being logged out.
 * Used by the EnforceIdleTimeout middleware (server) and the idle warning
 * modal (browser), so both always agree.
 */
final class IdleTimeout
{
    public const DEFAULT_MINUTES = 30;      // students, alumni, companies
    public const COORDINATOR_MINUTES = 15;  // Job Coordinator handles personal data
    public const WARNING_SECONDS = 60;      // "Stay logged in?" shows this long before logout

    public static function minutesFor(?User $user): int
    {
        return $user?->role === 'job_coordinator'
            ? self::COORDINATOR_MINUTES
            : self::DEFAULT_MINUTES;
    }
}