<?php

namespace App\Support;

use App\Models\JobPosting;
use App\Models\StudentAlumniProfile;
use Illuminate\Support\Collection;

/**
 * "Suggested jobs based on your profile" on the student and alumni dashboard.
 *
 * This is a plain keyword match, nothing more: words from the student's CV skills,
 * their course and the degree programs of their other education are looked for in
 * the title, requirements and description of jobs that students can already see
 * (approved, open, from an active company). No model, no scoring service, and no
 * data leaves the app, so it is easy to explain and to test.
 *
 * A keyword counts once per job, at the strongest place it appears:
 *   title = 3, requirements or qualifications = 2, description = 1.
 * A job needs MIN_SCORE to be suggested, so a single passing mention in a long
 * description is not enough. Highest score first, newest first on a tie.
 */
class JobSuggestions
{
    public const LIMIT = 5;

    /** How many of the newest listed jobs are scored. Keeps the page fast as jobs pile up. */
    public const POOL = 300;

    public const MIN_SCORE = 2;

    public const WEIGHT_TITLE = 3;
    public const WEIGHT_REQUIREMENTS = 2;
    public const WEIGHT_DESCRIPTION = 1;

    /**
     * Words that say nothing about a person's field: filler, and the wording of
     * degree names ("Bachelor of Science in ..."). Written in normalised form
     * (lower case, a trailing plural "s" removed), see normalize().
     */
    private const STOP_WORDS = [
        'a', 'an', 'and', 'or', 'the', 'of', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'from', 'as',
        'is', 'it', 'be', 'are', 'was', 'we', 'you', 'your', 'our', 'their', 'this', 'that', 'these', 'those',
        'will', 'can', 'may', 'must', 'should', 'all', 'any', 'not', 'no', 'new', 'more', 'other', 'such',
        'also', 'etc', 'have', 'has', 'had', 'who', 'which', 'what', 'good', 'basic', 'general',
        'skill', 'knowledge', 'ability', 'experience', 'proficient', 'familiar', 'work', 'working',
        'bachelor', 'master', 'doctor', 'degree', 'program', 'programme', 'course', 'major', 'minor',
        'science', 'study', 'education', 'diploma', 'certificate', 'associate', 'level',
        'bs', 'ba', 'bsc', 'ab', 'ma', 'ms', 'phd', 'senior', 'high', 'school', 'college', 'university',
    ];

    /**
     * Keywords from the profile, as [normalised word => the word as the student wrote it].
     * The second part is only for showing "matches: ..." next to a suggestion.
     *
     * @return array<string, string>
     */
    public static function keywordsFor(StudentAlumniProfile $profile): array
    {
        $profile->loadMissing(['skills', 'educations']);

        $texts = [$profile->course_program];

        foreach ($profile->skills as $skill) {
            $texts[] = $skill->name;
        }

        foreach ($profile->educations as $education) {
            $texts[] = $education->degree_program;
        }

        $keywords = [];

        foreach ($texts as $text) {
            foreach (static::tokens($text) as $token) {
                $key = static::normalize($token);

                if (static::isUsable($key) && ! isset($keywords[$key])) {
                    $keywords[$key] = $token;
                }
            }
        }

        return $keywords;
    }

    /**
     * The best matches for a profile, best first, at most $limit.
     *
     * @param  array<int, int>  $excludeJobIds  jobs to leave out, e.g. ones already applied to
     * @return Collection<int, array{job: JobPosting, score: int, matched: array<int, string>}>
     */
    public static function suggest(StudentAlumniProfile $profile, array $excludeJobIds = [], int $limit = self::LIMIT): Collection
    {
        $keywords = static::keywordsFor($profile);

        if ($keywords === []) {
            return collect();
        }

        $jobs = JobPosting::query()
            ->listed()
            ->with('company')
            ->when($excludeJobIds !== [], fn ($q) => $q->whereNotIn('job_postings.id', $excludeJobIds))
            ->latest('date_posted')
            ->latest('id')
            ->limit(self::POOL)
            ->get();

        return $jobs
            ->map(fn (JobPosting $job) => static::score($keywords, $job))
            ->filter(fn (array $match) => $match['score'] >= self::MIN_SCORE)
            // $jobs is already newest first and sort() is stable, so a tie stays newest first.
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Score one job against the keywords.
     *
     * @param  array<string, string>  $keywords
     * @return array{job: JobPosting, score: int, matched: array<int, string>}
     */
    public static function score(array $keywords, JobPosting $job): array
    {
        $title = static::tokenSet($job->title);
        $requirements = static::tokenSet($job->requirements.' '.$job->qualifications);
        $description = static::tokenSet($job->description);

        $score = 0;
        $found = [];

        foreach ($keywords as $key => $display) {
            $weight = match (true) {
                isset($title[$key]) => self::WEIGHT_TITLE,
                isset($requirements[$key]) => self::WEIGHT_REQUIREMENTS,
                isset($description[$key]) => self::WEIGHT_DESCRIPTION,
                default => 0,
            };

            if ($weight > 0) {
                $score += $weight;
                $found[$display] = $weight;
            }
        }

        // The strongest matches are the ones worth showing first.
        arsort($found);

        return [
            'job' => $job,
            'score' => $score,
            'matched' => array_map('strval', array_keys($found)),
        ];
    }

    /**
     * Whole words from a text, in the order written. "C++" and "C#" stay whole;
     * "node.js" becomes "node" and "js". Never throws: bad text gives no words.
     *
     * @return array<int, string>
     */
    public static function tokens(?string $text): array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return [];
        }

        if (preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}]*[+#]*/u', $text, $matches) === false) {
            return [];
        }

        return $matches[0];
    }

    /**
     * Lower case, and a plural "s" taken off long words so "skills" meets "skill"
     * and "analysts" meets "analyst". Words ending in "ss" ("business") are kept.
     */
    public static function normalize(string $token): string
    {
        $word = mb_strtolower($token);

        if (mb_strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
            $word = mb_substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * @return array<string, true> normalised word => true, for fast lookups
     */
    private static function tokenSet(?string $text): array
    {
        $set = [];

        foreach (static::tokens($text) as $token) {
            $set[static::normalize($token)] = true;
        }

        return $set;
    }

    /**
     * A usable keyword has a letter in it (so a year or a number never counts),
     * is at least two characters long, and is not filler.
     */
    private static function isUsable(string $key): bool
    {
        return mb_strlen($key) >= 2
            && preg_match('/\p{L}/u', $key) === 1
            && ! in_array($key, self::STOP_WORDS, true);
    }
}
