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
        return [
            'resume' => [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:5120', // 5MB
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $alreadyApplied = JobApplication::where('job_posting_id', $this->route('jobPosting')->id)
                ->where('applicant_id', $this->user()->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('resume', 'You have already applied to this job.');
            }
        });
    }
}