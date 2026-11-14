<?php

namespace App\Support;

use App\Models\JobPosting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Small display helpers shared by the job cards (Open Positions list, the landing
 * page rotator, and anything else that shows a posting summary), so the wording
 * is the same everywhere. Pure formatting: nothing here decides who may see or
 * apply to a posting.
 */
class JobCardData
{
    public const TYPE_LABELS = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'internship' => 'Internship',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? Str::headline($type ?? '');
    }

    /**
     * The first couple of description lines as one short paragraph.
     */
    public static function excerpt(JobPosting $job, int $limit = 170): string
    {
        $lines = array_slice($job->descriptionLines(), 0, 2);

        return Str::limit(implode(' ', $lines), $limit);
    }

    /**
     * "Posted today", "Posted yesterday", "Posted 5 days ago", or a date once it is a month old.
     */
    public static function postedLabel(Carbon|string|null $date): ?string
    {
        if (! $date) {
            return null;
        }

        $posted = Carbon::parse($date)->startOfDay();
        $days = max(0, (int) $posted->diffInDays(now()->startOfDay(), false));

        return match (true) {
            $days === 0 => 'Posted today',
            $days === 1 => 'Posted yesterday',
            $days <= 30 => "Posted {$days} days ago",
            default => 'Posted '.$posted->format('M j'),
        };
    }

    /**
     * Posted within the last 7 days.
     */
    public static function isNew(Carbon|string|null $date): bool
    {
        return $date
            && Carbon::parse($date)->startOfDay()->gte(now()->subDays(7)->startOfDay());
    }

    /**
     * Whole days until the deadline: 0 = closes today, negative = already passed.
     */
    public static function daysLeft(Carbon|string|null $deadline): ?int
    {
        if (! $deadline) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($deadline)->startOfDay(), false);
    }
}
