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
        ];

        if ($this->user()->role === 'job_coordinator') {
            $rules['company_id'] = ['required', Rule::exists('companies', 'id')];
        }

        return $rules;
    }
}