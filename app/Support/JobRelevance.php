<?php

namespace App\Support;

use App\Models\JobPosting;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use Closure;

/**
 * "Related to your course": the order of the Open Positions page for students and alumni.
 *
 * A plain keyword match, nothing more, and no AI. The keywords are
 *   - the words of the student's course mapped to related words in config/course_keywords.php
 *     (BSIT -> software, web, network, developer, ...),
 *   - the words of the course itself, the CV skills and the other education (the same words
 *     the dashboard suggestions use, see JobSuggestions).
 * They are looked for in the job's title (3 points), requirements and qualifications (2) and
 * description (1), once per keyword. A job needs `min_score` points to count as related.
 *
 * Related jobs come first, best match first and then newest. Every other job follows, newest first.
 * Nothing is hidden. The order is applied inside the database query (see orderSql()), so the page
 * is still paginated on the server and page 2 continues exactly where page 1 stopped.
 *
 * To keep the page fast only the newest `pool` jobs (after the search and filters) are scored,
 * and only their text columns are read. With no course words and no skills, nothing is scored
 * at all and the page keeps its normal order.
 */
class JobRelevance
{
    /**
     * @param  array<int, array{id: int, score: int, matched: array<int, string>}>  $ranked  best first
     */
    private function __construct(private readonly array $ranked)
    {
    }

    /** No ordering: the normal newest-first page. */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * For the signed-in user. Only students and alumni with a profile get an ordering.
     *
     * @param  Closure  $scope  applies the page's search and filters to a JobPosting query, so the
     *                          scored jobs are exactly the jobs the page can show
     */
    public static function forUser(?User $user, Closure $scope): self
    {
        if (! $user || ! in_array($user->role, ['student', 'alumni'], true)) {
            return static::none();
        }

        $profile = $user->profile;

        return $profile ? static::forProfile($profile, $scope) : static::none();
    }

    public static function forProfile(StudentAlumniProfile $profile, Closure $scope): self
    {
        $keywords = static::keywordsFor($profile);

        if ($keywords['plain'] === [] && $keywords['exact'] === []) {
            return static::none();
        }

        $query = JobPosting::query()->listed();
        $query = $scope($query);

        // Only what scoring reads, newest first, and no more than the pool.
        $pool = $query
            ->select(['job_postings.id', 'job_postings.title', 'job_postings.description', 'job_postings.requirements', 'job_postings.qualifications'])
            ->latest('date_posted')
            ->latest('id')
            ->limit(max(1, (int) config('course_keywords.pool', 300)))
            ->get();

        $minimum = (int) config('course_keywords.min_score', 2);
        $ranked = [];

        foreach ($pool as $position => $job) {
            $result = static::score($keywords, $job);

            if ($result['score'] >= $minimum) {
                $ranked[] = ['id' => (int) $job->id, 'score' => $result['score'], 'matched' => $result['matched'], 'position' => $position];
            }
        }

        // Best score first; the pool is newest first, so a tie keeps the newest job first.
        usort($ranked, fn (array $a, array $b) => [$b['score'], $a['position']] <=> [$a['score'], $b['position']]);

        return new self($ranked);
    }

    /** Is any job on the list related? When not, the page keeps its normal order and shows no label. */
    public function active(): bool
    {
        return $this->ranked !== [];
    }

    /**
     * Ids of the related jobs, best first.
     *
     * @return array<int, int>
     */
    public function ids(): array
    {
        return array_column($this->ranked, 'id');
    }

    /**
     * An ORDER BY that puts the related jobs first in their ranked order and leaves every other job
     * after them (all with the same value, so the query's own newest-first ordering decides). Ids
     * and positions are whole numbers built here, never request text. Works on SQLite and PostgreSQL.
     */
    public function orderSql(): string
    {
        $cases = '';

        foreach ($this->ids() as $position => $id) {
            $cases .= ' WHEN '.(int) $id.' THEN '.(int) $position;
        }

        return 'CASE job_postings.id'.$cases.' ELSE '.count($this->ranked).' END';
    }

    /**
     * Keywords for a profile. "plain" words match in any letter case, as [normalised word => word];
     * "exact" words are short all-capital ones (IT, HR) that must be written in capitals in the job.
     *
     * @return array{plain: array<string, string>, exact: array<string, string>}
     */
    public static function keywordsFor(StudentAlumniProfile $profile): array
    {
        $plain = JobSuggestions::keywordsFor($profile);
        $exact = [];

        foreach (static::courseKeywords($profile->course_program) as $word) {
            foreach (JobSuggestions::tokens($word) as $token) {
                if (preg_match('/^\p{Lu}{2,3}$/u', $token) === 1) {
                    $exact[$token] = $token;
                } else {
                    $plain[JobSuggestions::normalize($token)] ??= $token;
                }
            }
        }

        return ['plain' => $plain, 'exact' => $exact];
    }

    /**
     * The related words the config lists for a course text. A course matches an entry when the
     * text contains one of the entry's aliases as whole words.
     *
     * @return array<int, string>
     */
    public static function courseKeywords(?string $course): array
    {
        $text = static::spaced((string) $course);

        if (trim($text) === '') {
            return [];
        }

        $words = [];

        foreach ((array) config('course_keywords.courses', []) as $entry) {
            foreach ((array) ($entry['aliases'] ?? []) as $alias) {
                $needle = static::spaced((string) $alias);

                if (trim($needle) !== '' && str_contains($text, $needle)) {
                    array_push($words, ...array_values((array) ($entry['keywords'] ?? [])));
                    break;
                }
            }
        }

        return array_values(array_unique($words));
    }

    /**
     * Points for one job: title 3, requirements or qualifications 2, description 1, once per keyword.
     *
     * @param  array{plain: array<string, string>, exact: array<string, string>}  $keywords
     * @return array{score: int, matched: array<int, string>}
     */
    public static function score(array $keywords, JobPosting $job): array
    {
        $result = JobSuggestions::score($keywords['plain'], $job);
        $score = $result['score'];
        $matched = $result['matched'];

        if ($keywords['exact'] !== []) {
            $title = array_flip(JobSuggestions::tokens($job->title));
            $requirements = array_flip(JobSuggestions::tokens($job->requirements.' '.$job->qualifications));
            $description = array_flip(JobSuggestions::tokens($job->description));

            foreach ($keywords['exact'] as $word) {
                $weight = match (true) {
                    isset($title[$word]) => JobSuggestions::WEIGHT_TITLE,
                    isset($requirements[$word]) => JobSuggestions::WEIGHT_REQUIREMENTS,
                    isset($description[$word]) => JobSuggestions::WEIGHT_DESCRIPTION,
                    default => 0,
                };

                if ($weight > 0) {
                    $score += $weight;
                    $matched[] = $word;
                }
            }
        }

        return ['score' => $score, 'matched' => $matched];
    }

    /** Lower case, everything but letters and digits turned into single spaces, one space at each end. */
    private static function spaced(string $text): string
    {
        return ' '.trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)) ?? '').' ';
    }
}
