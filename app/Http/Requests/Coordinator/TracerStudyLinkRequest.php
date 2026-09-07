<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class TracerStudyLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'job_coordinator';
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:500'],
        ];
    }
}