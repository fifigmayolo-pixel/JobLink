<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'company';
    }

    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
        ];
    }
}