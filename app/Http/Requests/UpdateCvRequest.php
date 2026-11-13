<?php

namespace App\Http\Requests;

use App\Models\ProfileCertification;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCvRequest extends FormRequest
{
    public const MAX_SKILLS = 30;
    public const MAX_EXPERIENCES = 10;
    public const MAX_EDUCATIONS = 8;
    public const MAX_CERTIFICATIONS = 15;

    /**
     * Fields that hold what the student actually typed. A row where all of these
     * are empty is a row they added and never filled in, so it is dropped.
     * (kind, level and the "ongoing" checkbox have defaults and don't count.)
     */
    private const EXPERIENCE_CONTENT = ['position', 'organization', 'start_date', 'end_date', 'description'];
    private const EDUCATION_CONTENT = ['school_name', 'degree_program', 'start_year', 'end_year', 'details'];
    private const CERTIFICATION_CONTENT = ['title', 'issuer', 'date_received'];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && in_array($user->role, ['student', 'alumni'])
            && $user->profile !== null;
    }

    /**
     * Tidy the input before validating: trim text, drop empty rows, remove duplicate
     * skills ignoring case, and re-number the rows 0, 1, 2... so error messages
     * ("Experience 2") match the order on screen.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cv_summary' => $this->cleanText($this->input('cv_summary')),
            'linkedin_url' => $this->cleanText($this->input('linkedin_url')),
            'portfolio_url' => $this->cleanText($this->input('portfolio_url')),
            'skills' => $this->cleanSkills($this->input('skills')),
            'experiences' => $this->cleanExperiences($this->input('experiences')),
            'educations' => $this->cleanRows($this->input('educations'), self::EDUCATION_CONTENT),
            'certifications' => $this->cleanRows($this->input('certifications'), self::CERTIFICATION_CONTENT),
        ]);
    }

    public function rules(): array
    {
        $thisYear = (int) now()->year;

        return [
            'cv_summary' => ['nullable', 'string', 'max:1000'],

            // Links are shown to companies and printed, so only real https addresses.
            'linkedin_url' => [
                'bail', 'nullable', 'string', 'max:255', 'url:https',
                function (string $attribute, mixed $value, Closure $fail) {
                    $host = strtolower((string) parse_url($value, PHP_URL_HOST));

                    if ($host !== 'linkedin.com' && ! str_ends_with($host, '.linkedin.com')) {
                        $fail('The LinkedIn link must be a linkedin.com address.');
                    }
                },
            ],
            'portfolio_url' => ['nullable', 'string', 'max:255', 'url:https'],

            'skills' => ['nullable', 'array', 'max:'.self::MAX_SKILLS],
            'skills.*' => ['string', 'max:60'],

            // "tomorrow" instead of "today": the server clock may be a day behind the Philippines.
            'experiences' => ['nullable', 'array', 'max:'.self::MAX_EXPERIENCES],
            'experiences.*.kind' => ['required', Rule::in(array_keys(ProfileExperience::KINDS))],
            'experiences.*.position' => ['required', 'string', 'max:120'],
            'experiences.*.organization' => ['required', 'string', 'max:150'],
            'experiences.*.start_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.end_date' => [
                'nullable', 'date_format:Y-m-d',
                'after_or_equal:experiences.*.start_date', 'before_or_equal:tomorrow',
            ],
            'experiences.*.description' => ['nullable', 'string', 'max:1000'],

            'educations' => ['nullable', 'array', 'max:'.self::MAX_EDUCATIONS],
            'educations.*.level' => ['required', Rule::in(ProfileEducation::LEVELS)],
            'educations.*.school_name' => ['required', 'string', 'max:150'],
            'educations.*.degree_program' => ['nullable', 'string', 'max:150'],
            'educations.*.start_year' => ['required', 'integer', 'between:1980,'.$thisYear],
            'educations.*.end_year' => [
                'nullable', 'integer',
                'gte:educations.*.start_year', 'max:'.($thisYear + 6),
            ],
            'educations.*.details' => ['nullable', 'string', 'max:500'],

            'certifications' => ['nullable', 'array', 'max:'.self::MAX_CERTIFICATIONS],
            'certifications.*.kind' => ['required', Rule::in(array_keys(ProfileCertification::KINDS))],
            'certifications.*.title' => ['required', 'string', 'max:150'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:150'],
            'certifications.*.date_received' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // No end date is only OK when the student ticked "ongoing".
            foreach ((array) $this->input('experiences', []) as $i => $row) {
                if (is_array($row) && empty($row['is_current']) && empty($row['end_date'])) {
                    $validator->errors()->add(
                        "experiences.$i.end_date",
                        'Experience '.($i + 1).': add an end date, or tick "Currently ongoing".'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'experiences.*.end_date.after_or_equal' => 'The :attribute cannot be earlier than the start date.',
            'educations.*.end_year.gte' => 'The :attribute cannot be earlier than the start year.',
            'educations.*.start_year.between' => 'The :attribute must be between 1980 and this year.',
            'skills.max' => 'You can add up to '.self::MAX_SKILLS.' skills.',
            'experiences.max' => 'You can add up to '.self::MAX_EXPERIENCES.' experience entries.',
            'educations.max' => 'You can add up to '.self::MAX_EDUCATIONS.' education entries.',
            'certifications.max' => 'You can add up to '.self::MAX_CERTIFICATIONS.' certifications.',
        ];
    }

    /**
     * Friendly names, so the error banner says "experience 2 end date"
     * and not "experiences.1.end_date".
     */
    public function attributes(): array
    {
        $names = [
            'cv_summary' => 'personal summary',
            'linkedin_url' => 'LinkedIn link',
            'portfolio_url' => 'portfolio link',
        ];

        foreach ((array) $this->input('skills', []) as $i => $skill) {
            $names["skills.$i"] = 'skill '.($i + 1);
        }

        $sections = [
            'experiences' => [
                'label' => 'experience',
                'fields' => [
                    'kind' => 'type', 'position' => 'position', 'organization' => 'organization',
                    'start_date' => 'start date', 'end_date' => 'end date', 'description' => 'description',
                ],
            ],
            'educations' => [
                'label' => 'education',
                'fields' => [
                    'level' => 'level', 'school_name' => 'school name', 'degree_program' => 'course or strand',
                    'start_year' => 'start year', 'end_year' => 'end year', 'details' => 'details',
                ],
            ],
            'certifications' => [
                'label' => 'certification',
                'fields' => [
                    'kind' => 'type', 'title' => 'title', 'issuer' => 'issuer', 'date_received' => 'date received',
                ],
            ],
        ];

        foreach ($sections as $key => $section) {
            foreach ((array) $this->input($key, []) as $i => $row) {
                foreach ($section['fields'] as $field => $label) {
                    $names["$key.$i.$field"] = $section['label'].' '.($i + 1).' '.$label;
                }
            }
        }

        return $names;
    }

    // ---- input cleaning ---------------------------------------------------

    private function cleanText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        // Browsers send line breaks as CRLF; store and count them as a single LF.
        $value = trim(str_replace(["\r\n", "\r"], "\n", $value));

        return $value === '' ? null : $value;
    }

    private function cleanSkills(mixed $skills): mixed
    {
        if ($skills === null) {
            return [];
        }

        if (! is_array($skills)) {
            return $skills;
        }

        $seen = [];
        $clean = [];

        foreach ($skills as $skill) {
            if ($skill === null) {
                continue; // an empty box that was turned into null
            }

            if (! is_string($skill)) {
                $clean[] = $skill; // let the "string" rule reject it
                continue;
            }

            $name = trim((string) preg_replace('/\s+/u', ' ', $skill));

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $clean[] = $name;
        }

        return $clean;
    }

    private function cleanExperiences(mixed $rows): mixed
    {
        $rows = $this->cleanRows($rows, self::EXPERIENCE_CONTENT);

        if (! is_array($rows)) {
            return $rows;
        }

        return array_map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }

            $row['is_current'] = filter_var($row['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // An ongoing entry has no end date, whatever was left in the field.
            if ($row['is_current']) {
                $row['end_date'] = null;
            }

            return $row;
        }, $rows);
    }

    /**
     * Trim every text value, drop rows where none of the $contentKeys has a value,
     * and re-number the rows from 0.
     */
    private function cleanRows(mixed $rows, array $contentKeys): mixed
    {
        if ($rows === null) {
            return [];
        }

        if (! is_array($rows)) {
            return $rows;
        }

        $clean = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $clean[] = $row; // let the validation rules reject it
                continue;
            }

            $row = array_map(fn ($value) => $this->cleanText($value), $row);

            $hasContent = false;
            foreach ($contentKeys as $key) {
                if (isset($row[$key]) && $row[$key] !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if (! $hasContent) {
                continue;
            }

            $clean[] = $row;
        }

        return $clean;
    }
}
