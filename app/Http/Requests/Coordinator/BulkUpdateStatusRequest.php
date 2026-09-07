<?php

namespace App\Http\Requests\Coordinator;

use App\Models\StudentAlumniProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['to_alumni', 'to_student'])],
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['exists:student_alumni_profiles,id'],
            'year_graduated' => [Rule::requiredIf($this->input('direction') === 'to_alumni'), Rule::in(StudentAlumniProfile::academicYearRanges())],
            'year_level' => [Rule::requiredIf($this->input('direction') === 'to_student'), Rule::in(StudentAlumniProfile::YEAR_LEVELS)],
        ];
    }
}