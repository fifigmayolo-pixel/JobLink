<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
                function ($attribute, $value, $fail) {
                    if (Hash::check($value, $this->user()->password)) {
                        $fail('Your new password must be different from your current password.');
                    }
                },
            ],
        ];
    }
}