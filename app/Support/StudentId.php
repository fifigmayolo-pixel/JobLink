<?php

namespace App\Support;

/**
 * Single source of truth for the Student ID format, e.g. 23-AC-2344
 *   2 digits - campus code - 4 digits
 *
 * If the format ever changes, change it here (and the mask in
 * public/js/student-id-input.js).
 */
final class StudentId
{
    public const CAMPUS_CODE = 'AC';
    public const NUMBER_DIGITS = 4;
    public const EXAMPLE = '23-AC-2344';

    /** Strict pattern for the canonical, already-normalized form. */
    public static function regex(): string
    {
        return '/^\d{2}-'.self::CAMPUS_CODE.'-\d{'.self::NUMBER_DIGITS.'}$/';
    }

    /**
     * Clean up whatever was typed/pasted/imported:
     *  - trims and removes all whitespace (including non-breaking spaces)
     *  - uppercases
     *  - turns fancy dashes (–, —, −) into a plain hyphen
     *  - if it is the right shape with dashes missing ("23ac2344"), adds them
     *
     * Anything that still isn't the right shape is returned cleaned-up but
     * otherwise unchanged, so validation can reject it (and old-format IDs
     * already in the database can still be looked up at login).
     */
    public static function normalize(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', $value) ?? $value;
        $value = preg_replace('/[\s\x{00A0}\x{200B}]+/u', '', $value) ?? $value;
        $value = strtoupper($value);

        $shape = '/^(\d{2})-?'.self::CAMPUS_CODE.'-?(\d{'.self::NUMBER_DIGITS.'})$/';

        if (preg_match($shape, $value, $m)) {
            return $m[1].'-'.self::CAMPUS_CODE.'-'.$m[2];
        }

        return $value;
    }

    /** True only for the exact canonical format. Call normalize() first. */
    public static function isValid(?string $value): bool
    {
        return (bool) preg_match(self::regex(), (string) $value);
    }
}