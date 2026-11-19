<?php

namespace App\Http\Requests;

use App\Models\JobPosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership/role check happens via Gate::authorize in the controller
    }

    /**
     * The posting being edited (route model binding), or null if the route has none.
     */
    private function posting(): ?JobPosting
    {
        $posting = $this->route('jobPosting');

        return $posting instanceof JobPosting ? $posting : null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'in:full_time,part_time'],
            'description' => ['required', 'string'],
            'qualifications' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'application_deadline' => ['required', 'date'],
            'status' => ['required', 'in:open,closed'],
            // The edit form always sends it. A request without the field keeps the current
            // number; one with an empty or invalid value is refused.
            'slots' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.JobPosting::MAX_SLOTS],
        ];
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

    /**
     * Two rules that need the posting's current invitations:
     *  - the number cannot drop below the people already invited to interview;
     *  - a closed posting cannot be set to Open while every slot is still filled.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $posting = $this->posting();

            if (! $posting || $validator->errors()->has('slots')) {
                return;
            }

            $invited = $posting->interviewCount();
            $slots = $this->has('slots') ? (int) $this->input('slots') : $posting->slotsTotal();

            if ($slots < $invited) {
                $validator->errors()->add(
                    'slots',
                    "The number of slots cannot be lower than the {$invited} ".($invited === 1 ? 'applicant' : 'applicants')
                    .' already invited to interview.'
                );

                return;
            }

            // Opening a posting that is closed while every slot is still filled. (An open posting
            // whose count is lowered to the invited number is not an error: it simply closes.)
            if ($posting->status === 'closed' && $this->input('status') === 'open' && $invited >= $slots) {
                $validator->errors()->add(
                    'slots',
                    'Every interview slot is filled, so the posting cannot be open. Raise the number of slots to reopen it.'
                );
            }
        });
    }
}
