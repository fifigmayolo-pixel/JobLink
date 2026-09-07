<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickAddStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'string', 'unique:users,student_id_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }
}