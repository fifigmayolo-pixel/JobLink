<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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