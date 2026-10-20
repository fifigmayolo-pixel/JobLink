<?php

namespace App\Http\Requests\Auth;

use App\Rules\StudentIdNumber;
use App\Support\StudentId;
use Illuminate\Foundation\Http\FormRequest;

class VerifyIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'student_id_number' => ['required', 'string', new StudentIdNumber],
            'full_name' => ['required', 'string'],
        ];
    }
}