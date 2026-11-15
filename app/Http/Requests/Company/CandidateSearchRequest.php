<?php

namespace App\Http\Requests\Company;

use App\Models\StudentAlumniProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'company' && $user->company !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'string', 'max:200'],
            'course' => ['nullable', Rule::in(StudentAlumniProfile::PROGRAMS)],
            'status' => ['nullable', Rule::in(['student', 'alumni'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'keywords',
            'skills' => 'skills',
            'course' => 'course',
            'status' => 'student or alumni filter',
        ];
    }
}
