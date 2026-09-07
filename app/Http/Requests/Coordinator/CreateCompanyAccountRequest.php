<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['peso', 'job_coordinator']);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'industry' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
        ];
    }
}