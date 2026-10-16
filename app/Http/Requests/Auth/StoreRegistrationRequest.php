<?php

namespace App\Http\Requests\Auth;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id_number' => trim((string) $this->input('student_id_number')),
            'full_name' => preg_replace('/\s+/', ' ', trim((string) $this->input('full_name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'applicant_type' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_type.required' => 'Please choose whether you are a Student or an Alumni.',
            'applicant_type.in' => 'Please choose whether you are a Student or an Alumni.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('student_id_number')) {
                    return;
                }

                $studentId = $this->input('student_id_number');

                // withTrashed(): a removed (soft-deleted) account still owns its Student ID.
                if (User::withTrashed()->where('student_id_number', $studentId)->exists()) {
                    $validator->errors()->add(
                        'student_id_number',
                        'This Student ID already has an account. Use "Activate your account" on the login page, or contact the Job Coordinator.'
                    );
                    return;
                }

                if (RegistrationRequest::pending()->where('student_id_number', $studentId)->exists()) {
                    $validator->errors()->add(
                        'student_id_number',
                        'A registration request for this Student ID is already waiting for review.'
                    );
                }
            },
        ];
    }
}