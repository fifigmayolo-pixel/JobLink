<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'roster' => ['required', 'file', 'mimes:xlsx,xls', 'max:4096'],
            'role' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }
}