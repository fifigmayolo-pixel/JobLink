<?php

namespace App\Http\Requests\Coordinator;

use App\Models\StudentAlumniProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverrideLockedFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        $profile = $this->route('profile');

        return [
            'field_name' => [
                'required',
                Rule::in(StudentAlumniProfile::LOCKED_FIELDS),
                function ($attribute, $value, $fail) use ($profile) {
                    if ($value === 'section' && $profile->status !== 'student') {
                        $fail('Section only applies to student records.');
                    }
                },
            ],
            'new_value' => $this->newValueRules(),
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Which choices (if any) are valid depends on which field is being
     * corrected — course_program/year_level/year_graduated/section are all
     * controlled lists; anything else (full_name) stays free text.
     */
    protected function newValueRules(): array
    {
        return match ($this->input('field_name')) {
            'course_program' => ['required', Rule::in(StudentAlumniProfile::PROGRAMS)],
            'year_level' => ['required', Rule::in(StudentAlumniProfile::YEAR_LEVELS)],
            'year_graduated' => ['required', Rule::in(StudentAlumniProfile::academicYearRanges())],
            'section' => ['required', Rule::in(StudentAlumniProfile::SECTIONS)],
            default => ['required', 'string', 'max:255'],
        };
    }
}