<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class CreatePesoAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
        ];
    }
}