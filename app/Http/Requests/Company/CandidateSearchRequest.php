<?php

namespace App\Http\Requests\Company;

use App\Models\StudentAlumniProfile;
use App\Support\ContactMask;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateSearchRequest extends FormRequest
{
    /**
     * A failed search goes back to the clean search page, not to wherever the
     * company came from (which is "/" when the URL was typed or bookmarked).
     */
    protected $redirectRoute = 'company.candidates.index';

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'company' && $user->company !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'string', 'max:200'],
            'course' => ['nullable', Rule::in(StudentAlumniProfile::PROGRAMS)],
            'status' => ['nullable', Rule::in(['student', 'alumni'])],
        ];
    }

    /**
     * Searching for an email, phone number or web address would let a company
     * confirm that a hidden detail appears in someone's CV text, so those
     * searches are refused. Skills and job words are what this page is for.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (['q', 'skills'] as $field) {
                if (ContactMask::looksLikeContact($this->input($field))) {
                    $validator->errors()->add(
                        $field,
                        'Search by skills or job words, not by an email address, phone number or web address.'
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'q' => 'keywords',
            'skills' => 'skills',
            'course' => 'course',
            'status' => 'student or alumni filter',
        ];
    }
}
