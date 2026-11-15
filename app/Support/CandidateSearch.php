<?php

namespace App\Support;

use App\Models\StudentAlumniProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The company "Find Candidates" search.
 *
 * Every query starts from StudentAlumniProfile::searchable(), the one rule for who
 * may be found, so a student who has not opted in can never appear, whatever the
 * filters are.
 *
 * Text matching is case-insensitive on every database (lower(...) like ...) because
 * PostgreSQL's LIKE is case-sensitive while the local SQLite one is not. The search
 * words are escaped, so typing % or _ matches those characters and not "everything".
 * Contact details are never searched and never returned.
 */
class CandidateSearch
{
    public const PER_PAGE = 12;
    public const MAX_KEYWORDS = 5;
    public const MAX_SKILLS = 10;

    /**
     * @param array{q?:?string, skills?:?string, course?:?string, status?:?string} $filters
     */
    public static function query(array $filters): Builder
    {
        $query = StudentAlumniProfile::query()
            ->searchable()
            ->with(['skills' => fn ($q) => $q->orderBy('name')]);

        if (! empty($filters['course'])) {
            $query->where('student_alumni_profiles.course_program', $filters['course']);
        }

        if (! empty($filters['status'])) {
            $query->where('student_alumni_profiles.status', $filters['status']);
        }

        // Keywords: every word must appear somewhere in the CV (AND between words,
        // OR between the places it may appear).
        foreach (self::keywordTerms($filters['q'] ?? null) as $term) {
            $like = self::like($term);

            $query->where(function ($q) use ($like) {
                $q->whereRaw("lower(student_alumni_profiles.cv_summary) like ? escape '!'", [$like])
                    ->orWhereRaw("lower(student_alumni_profiles.course_program) like ? escape '!'", [$like])
                    ->orWhereHas('skills', fn ($s) => $s
                        ->whereRaw("lower(profile_skills.name) like ? escape '!'", [$like]))
                    ->orWhereHas('experiences', fn ($e) => $e->where(function ($w) use ($like) {
                        $w->whereRaw("lower(profile_experiences.position) like ? escape '!'", [$like])
                            ->orWhereRaw("lower(profile_experiences.organization) like ? escape '!'", [$like])
                            ->orWhereRaw("lower(coalesce(profile_experiences.description, '')) like ? escape '!'", [$like]);
                    }))
                    ->orWhereHas('educations', fn ($e) => $e->where(function ($w) use ($like) {
                        $w->whereRaw("lower(profile_education.school_name) like ? escape '!'", [$like])
                            ->orWhereRaw("lower(coalesce(profile_education.degree_program, '')) like ? escape '!'", [$like])
                            ->orWhereRaw("lower(coalesce(profile_education.details, '')) like ? escape '!'", [$like]);
                    }))
                    ->orWhereHas('certifications', fn ($c) => $c->where(function ($w) use ($like) {
                        $w->whereRaw("lower(profile_certifications.title) like ? escape '!'", [$like])
                            ->orWhereRaw("lower(coalesce(profile_certifications.issuer, '')) like ? escape '!'", [$like]);
                    }));
            });
        }

        // Skills: has ANY of the listed skills; the more of them, the higher it ranks.
        $skills = self::skillTerms($filters['skills'] ?? null);

        if ($skills) {
            $matching = function ($q) use ($skills) {
                $q->where(function ($w) use ($skills) {
                    foreach ($skills as $skill) {
                        $w->orWhereRaw("lower(profile_skills.name) like ? escape '!'", [self::like($skill)]);
                    }
                });
            };

            $query->whereHas('skills', $matching)
                ->withCount(['skills as matched_skills' => $matching])
                ->orderByDesc('matched_skills');
        }

        // Then the most recently updated CVs first, so the order doesn't favour A-names.
        return $query
            ->orderByDesc('student_alumni_profiles.updated_at')
            ->orderBy('student_alumni_profiles.id');
    }

    /**
     * What a result card is allowed to show. The list view receives only these arrays,
     * never the profile model, so a contact field can't be printed by mistake.
     */
    public static function card(StudentAlumniProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->full_name,
            'status' => $profile->status, // student or alumni
            'course' => $profile->course_program,
            'year' => $profile->status === 'alumni'
                ? trim('Graduated '.$profile->year_graduated)
                : (string) $profile->year_level,
            'place' => implode(', ', array_filter([$profile->municipality_city, $profile->province])),
            'excerpt' => Str::limit((string) $profile->cv_summary, 180),
            'skills' => $profile->skills->pluck('name')->all(),
            'matched_skills' => (int) ($profile->matched_skills ?? 0),
        ];
    }

    /**
     * "laravel developer" -> ['laravel', 'developer']
     *
     * @return array<int, string>
     */
    public static function keywordTerms(?string $text): array
    {
        return self::clean(preg_split('/\s+/u', (string) $text) ?: [], self::MAX_KEYWORDS);
    }

    /**
     * "PHP, Excel" -> ['php', 'excel']
     *
     * @return array<int, string>
     */
    public static function skillTerms(?string $text): array
    {
        return self::clean(explode(',', (string) $text), self::MAX_SKILLS);
    }

    /**
     * @param array<int, mixed> $parts
     * @return array<int, string>
     */
    private static function clean(array $parts, int $max): array
    {
        $terms = [];

        foreach ($parts as $part) {
            $part = mb_strtolower(trim((string) $part));

            if ($part === '' || in_array($part, $terms, true)) {
                continue;
            }

            $terms[] = $part;

            if (count($terms) >= $max) {
                break;
            }
        }

        return $terms;
    }

    /**
     * A LIKE pattern for "contains $term", with ! as the escape character so that
     * a typed % or _ is matched literally. Pairs with `escape '!'` in the queries.
     */
    private static function like(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
