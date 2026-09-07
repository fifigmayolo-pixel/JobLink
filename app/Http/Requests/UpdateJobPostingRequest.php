<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership/role check happens via Gate::authorize in the controller
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'qualifications' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'application_deadline' => ['required', 'date'],
            'status' => ['required', 'in:open,closed'],
        ];
    }
}