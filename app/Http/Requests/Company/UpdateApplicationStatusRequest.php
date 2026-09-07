<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'company';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['interview', 'rejected'])],
        ];
    }
}