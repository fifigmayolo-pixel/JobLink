<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['student', 'alumni']);
    }

    public function rules(): array
    {
        // No fields: the CV comes from the applicant's profile, not from the form.
        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // A withdrawn application doesn't count, so the student can apply again.
            $alreadyApplied = JobApplication::where('job_posting_id', $this->route('jobPosting')->id)
                ->where('applicant_id', $this->user()->id)
                ->where('status', '!=', 'withdrawn')
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('application', 'You have already applied to this job.');
            }
        });
    }
}