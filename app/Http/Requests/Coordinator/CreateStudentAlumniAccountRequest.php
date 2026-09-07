<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStudentAlumniAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'string', 'unique:users,student_id_number'],
            'role' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }
}