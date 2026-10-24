<?php

namespace App\Support;

/**
 * Deterministic, auto-assigned accent colors for a company.
 *
 * The same company (by id, falling back to its name) always resolves to the
 * same palette entry — no manual color picking, no database column to
 * maintain. Used for the job card / job detail left-border accent, the
 * logo-letter badge when the company has no uploaded logo, the job-type
 * pill, section icon bubbles, and the "Apply" button on the detail page.
 *
 * Usage in Blade:
 *   @php $accent = \App\Support\CompanyAccent::for($job->company?->id, $job->company?->company_name); @endphp
 *   <div style="--accent: {{ $accent['base'] }}; --accent-soft: {{ $accent['soft'] }};
 *               --accent-text: {{ $accent['text'] }}; --accent-deep: {{ $accent['deep'] }};">
 */
class CompanyAccent
{
    /**
     * Curated jewel-tone palette — picked to read clearly on white and to
     * sit next to JobLink's navy/gold/cream theme without fighting it
     * (no near-navy blues, no near-gold yellows, nothing neon).
     *
     *   base  – the solid accent (border, badge background, buttons)
     *   soft  – pale tint (pill backgrounds, icon bubble backgrounds)
     *   text  – darker tone for text/icons sitting on `soft`
     *   deep  – darker tone for hover states on `base`-colored buttons
     */
    protected static array $palette = [
        ['base' => '#2F8F5B', 'soft' => '#E3F4EA', 'text' => '#1F6B40', 'deep' => '#256F47'], // forest green
        ['base' => '#B4423A', 'soft' => '#FBEAE8', 'text' => '#8C332C', 'deep' => '#96352E'], // crimson rose
        ['base' => '#1C7C8C', 'soft' => '#E1F2F4', 'text' => '#155E6A', 'deep' => '#146270'], // teal
        ['base' => '#6E4C9E', 'soft' => '#EFE8F8', 'text' => '#553A7D', 'deep' => '#5A3F85'], // violet
        ['base' => '#C2703D', 'soft' => '#FBEDE1', 'text' => '#96542C', 'deep' => '#A35F33'], // burnt orange
        ['base' => '#4A5FC1', 'soft' => '#E8EAFB', 'text' => '#38469B', 'deep' => '#3E4FA8'], // indigo
        ['base' => '#9C3F6B', 'soft' => '#F7E6EE', 'text' => '#7A3154', 'deep' => '#84375A'], // plum
        ['base' => '#3A6B6F', 'soft' => '#E4EEEF', 'text' => '#2B5255', 'deep' => '#2F5A5E'], // slate teal
    ];

    /**
     * @param int|null    $companyId   Preferred key — stable for the company's lifetime.
     * @param string|null $fallbackKey Used only when no id is available (e.g. company was deleted).
     * @return array{base: string, soft: string, text: string, deep: string}
     */
    public static function for(?int $companyId, ?string $fallbackKey = null): array
    {
        $key = $companyId !== null ? 'company-' . $companyId : ($fallbackKey ?: 'default');
        $index = crc32($key) % count(self::$palette);

        return self::$palette[$index];
    }

    /**
     * Single uppercase letter to show in the logo badge when the company
     * has no uploaded logo image.
     */
    public static function initial(?string $companyName): string
    {
        $trimmed = trim((string) $companyName);

        return $trimmed === '' ? '?' : mb_strtoupper(mb_substr($trimmed, 0, 1));
    }
}