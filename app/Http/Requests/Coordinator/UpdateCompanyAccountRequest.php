<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string'],
            'contact_person' => ['required', 'string'],
            'contact_number' => ['required', 'string'],
            'address' => ['required', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}