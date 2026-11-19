<?php

namespace App\Http\Requests;

use App\Models\JobPosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', JobPosting::class);
    }

    /**
     * company_id is only a validated field for job_coordinator — for a
     * company posting its own job, it's never in $request->validated(), so
     * the controller sets it server-side from the logged-in company instead.
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'in:full_time,part_time'],
            'description' => ['required', 'string'],
            'qualifications' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'application_deadline' => ['required', 'date', 'after:today'],
            'slots' => ['required', 'integer', 'min:1', 'max:'.JobPosting::MAX_SLOTS],
        ];

        if ($this->user()->role === 'job_coordinator') {
            $rules['company_id'] = ['required', Rule::exists('companies', 'id')];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['slots' => 'number of slots'];
    }

    public function messages(): array
    {
        return [
            'slots.required' => 'Enter the number of slots needed, for example 5.',
            'slots.integer' => 'The number of slots must be a whole number, for example 5.',
            'slots.min' => 'The number of slots must be at least 1.',
            'slots.max' => 'The number of slots cannot be more than '.JobPosting::MAX_SLOTS.'.',
        ];
    }
}
