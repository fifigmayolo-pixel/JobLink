<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePesoAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
        ];
    }
}