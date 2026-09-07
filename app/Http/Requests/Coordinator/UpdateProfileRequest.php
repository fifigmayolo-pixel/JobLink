<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['peso', 'job_coordinator']);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
        ];
    }
}