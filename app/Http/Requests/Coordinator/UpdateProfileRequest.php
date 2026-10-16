<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', $this->user()->email))),
            'email_confirmation' => strtolower(trim((string) $this->input('email_confirmation'))),
        ]);
    }

    private function emailChanged(): bool
    {
        return $this->input('email') !== strtolower((string) $this->user()->email);
    }

    public function rules(): array
    {
        $changed = $this->emailChanged();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            // unique counts archived accounts too, since they still own their email.
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
                ...($changed ? ['confirmed'] : []),
            ],
            // Only needed when the login email is actually being changed.
            'current_password' => $changed ? ['required', 'current_password'] : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'That email is already used by another account.',
            'email.confirmed' => 'The two email entries do not match.',
            'current_password.required' => 'Enter your current password to change your email.',
            'current_password.current_password' => 'That is not your current password.',
        ];
    }
}