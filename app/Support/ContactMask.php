<?php

namespace App\Support;

/**
 * Hides contact details that a student typed into the free text of their CV
 * (summary, experience, education details) so a company cannot get around the
 * "no contact until they apply" rule by reading them there.
 *
 * Used only for what companies see in candidate search and in the student's
 * "what companies see" preview. The copy of the CV that goes with an application
 * is NOT masked: that is where the student chooses to share contact details.
 *
 * What it hides: email addresses (also "name [at] site [dot] com"), web addresses,
 * social handles like @name, and phone-like numbers (9 or more digits, with the
 * usual spaces, dashes, dots and brackets between them).
 *
 * What it keeps: years and year ranges ("2021 2022 2023", "2024-2025"), short
 * numbers, and technology names that look like web addresses (ASP.NET, Node.js).
 *
 * It is a best-effort filter, not a guarantee: digits spelled out in words or
 * "jane at gmail dot com" get through. That is why the student is also told, on
 * the CV form and the privacy page, not to put contact details in their text.
 * If the pattern engine ever fails (for example on broken UTF-8), the text is
 * dropped rather than shown unmasked.
 */
class ContactMask
{
    public const PLACEHOLDER = '[contact hidden]';

    /** Names that look like a web address but are not one. */
    private const NOT_A_LINK = ['asp.net', 'vb.net', 'socket.io'];

    /** jane.doe@example.com, jane [at] example [dot] com, jane (at) example (dot) com */
    private const EMAIL_SPACED = '/[\p{L}\p{N}._%+\-]+\s*(?:@|\[\s*at\s*\]|\(\s*at\s*\))\s*[\p{L}\p{N}\-]+(?:\s*(?:\.|\[\s*dot\s*\]|\(\s*dot\s*\))\s*[\p{L}\p{N}\-]+)+/iu';

    /** jane@example (no dot, no spaces) */
    private const EMAIL_PLAIN = '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}\-]+/u';

    /** @name handles that are not part of an email */
    private const HANDLE = '/(?<![\p{L}\p{N}_@.])@[\p{L}\p{N}_.]{3,}/u';

    /** 9 or more digits, with at most two separator characters between digits */
    private const PHONE = '/(?<![\p{N}])\+?\(?\d(?:[\s.\-()]{0,2}\d){8,}(?![\p{N}])/u';

    /** Only years: "2021 2022 2023" */
    private const YEARS_ONLY = '/^(?:(?:19|20)\d{2}[\s.\-\/–]*)+$/u';

    /**
     * @param bool $links false for short fields (job title, school name, skill name)
     *                    where "Amazon.com" is a place of work, not a contact detail.
     *                    Emails, handles and phone numbers are always hidden.
     */
    public static function text(?string $text, bool $links = true): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $text = self::apply(self::EMAIL_SPACED, self::PLACEHOLDER, $text);
        $text = self::apply(self::EMAIL_PLAIN, self::PLACEHOLDER, $text);

        if ($links) {
            // A link ends at its last real character, so the full stop after
            // "see https://site.com/cv." stays in the sentence.
            $text = self::apply('/\b(?:https?|ftp):\/\/[^\s]*[^\s.,;:!?)\]"\']/iu', self::PLACEHOLDER, $text);
            $text = self::apply('/\bwww\.[^\s]*[^\s.,;:!?)\]"\']/iu', self::PLACEHOLDER, $text);

            // Social and messaging hosts, even without http:// or www.
            $text = self::apply(
                '/\b(?:facebook|fb|instagram|linkedin|twitter|x|tiktok|youtube|youtu|github|gitlab|behance|dribbble|messenger|telegram|viber|t|wa|m|bit|linktr)\.(?:com|me|be|ee|ly|ph)\b(?:\/(?:[^\s]*[^\s.,;:!?)\]"\'])?)?/iu',
                self::PLACEHOLDER,
                $text
            );

            // Any other bare address such as janedoe.com or my-site.org/cv.
            // Lower-case endings only, so "there.Co-op" is left alone.
            $text = self::apply(
                '/\b[\p{L}\p{N}\-]+(?:\.[\p{L}\p{N}\-]+)*\.(?:com|net|org|ph|io|me|ly|ee|be|dev|app|co|info|biz|edu|gov)(?![\p{L}\p{N}\-])(?:\/(?:[^\s]*[^\s.,;:!?)\]"\'])?)?/u',
                fn (array $m) => in_array(strtolower($m[0]), self::NOT_A_LINK, true) ? $m[0] : self::PLACEHOLDER,
                $text
            );
        }

        $text = self::apply(self::HANDLE, self::PLACEHOLDER, $text);

        return self::apply(self::PHONE, function (array $m) {
            if (strlen(preg_replace('/\D/', '', $m[0])) < 9) {
                return $m[0];
            }

            return preg_match(self::YEARS_ONLY, $m[0]) === 1 ? $m[0] : self::PLACEHOLDER;
        }, $text);
    }

    /**
     * True when a search word looks like someone's contact details. Used to refuse
     * searches such as an email address or phone number typed into the keyword box,
     * which would otherwise confirm that a hidden detail exists in a CV.
     */
    public static function looksLikeContact(?string $text): bool
    {
        $text = (string) $text;

        if ($text === '') {
            return false;
        }

        return str_contains($text, '@')
            || preg_match('/\d{6,}/', $text) === 1
            || self::text($text) !== $text;
    }

    /**
     * Runs one pattern. Fails closed: if the regex engine errors, return nothing
     * instead of the unmasked text.
     *
     * @param string|\Closure $replacement
     */
    private static function apply(string $pattern, $replacement, string $text): string
    {
        $result = $replacement instanceof \Closure
            ? preg_replace_callback($pattern, $replacement, $text)
            : preg_replace($pattern, $replacement, $text);

        return $result ?? '';
    }
}
