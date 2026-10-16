<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class CreateCoordinatorAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => preg_replace('/\s+/', ' ', trim((string) $this->input('full_name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            // unique:users also counts archived (soft-deleted) accounts, which still own their email.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'That email is already used by another account.',
        ];
    }
}