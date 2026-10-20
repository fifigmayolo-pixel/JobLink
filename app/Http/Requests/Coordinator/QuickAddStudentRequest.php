<?php

namespace App\Http\Requests\Coordinator;

use App\Rules\StudentIdNumber;
use App\Support\StudentId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickAddStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('student_id_number');

        $this->merge([
            'student_id_number' => StudentId::normalize(is_string($id) ? $id : ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'string', new StudentIdNumber, 'unique:users,student_id_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id_number.unique' => 'This Student ID is already in the system.',
        ];
    }
}