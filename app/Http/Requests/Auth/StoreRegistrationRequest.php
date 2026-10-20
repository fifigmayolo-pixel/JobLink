<?php

namespace App\Http\Requests\Auth;

use App\Models\RegistrationRequest;
use App\Models\User;
use App\Rules\StudentIdNumber;
use App\Support\StudentId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRegistrationRequest extends FormRequest
{
    /** After a rejection, the applicant must wait this long before asking again. */
    public const RETRY_AFTER_HOURS = 24;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id_number' => StudentId::normalize($this->text('student_id_number')),
            'full_name' => preg_replace('/\s+/', ' ', trim($this->text('full_name'))),
            'email' => strtolower(trim($this->text('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'string', new StudentIdNumber],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'applicant_type' => ['required', Rule::in(['student', 'alumni'])],
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_type.required' => 'Please choose whether you are a Student or an Alumni.',
            'applicant_type.in' => 'Please choose whether you are a Student or an Alumni.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $errors = $validator->errors();

                if (! $errors->has('student_id_number')) {
                    $this->checkStudentId($validator);
                }

                if (! $errors->has('email')) {
                    $this->checkEmail($validator);
                }
            },
        ];
    }

    private function checkStudentId(Validator $validator): void
    {
        $studentId = $this->input('student_id_number');

        // withTrashed(): a removed (soft-deleted) account still owns its Student ID.
        if (User::withTrashed()->where('student_id_number', $studentId)->exists()) {
            $validator->errors()->add(
                'student_id_number',
                'This Student ID already has an account. Use "Activate your account" on the login page, or contact the Job Coordinator.'
            );
            return;
        }

        if ($existing = $this->blockingRequest('student_id_number', $studentId)) {
            $validator->errors()->add(
                'student_id_number',
                $this->blockMessage($existing, 'for this Student ID')
            );
        }
    }

    private function checkEmail(Validator $validator): void
    {
        if ($existing = $this->blockingRequest('email', $this->input('email'))) {
            $validator->errors()->add(
                'email',
                $this->blockMessage($existing, 'using this email address')
            );
        }
    }

    /**
     * A new request is blocked while an earlier one is still pending, or was
     * rejected less than RETRY_AFTER_HOURS ago (counted from the rejection).
     * Approved requests already have an account, which is checked separately.
     */
    private function blockingRequest(string $column, string $value): ?RegistrationRequest
    {
        $since = now()->subHours(self::RETRY_AFTER_HOURS);

        return RegistrationRequest::where($column, $value)
            ->where(function ($q) use ($since) {
                $q->where('status', RegistrationRequest::STATUS_PENDING)
                    ->orWhere(function ($q) use ($since) {
                        $q->where('status', RegistrationRequest::STATUS_REJECTED)
                            ->where(function ($q) use ($since) {
                                $q->where('reviewed_at', '>', $since)
                                    ->orWhere(fn ($q) => $q->whereNull('reviewed_at')->where('created_at', '>', $since));
                            });
                    });
            })
            ->latest('id')
            ->first();
    }

    private function blockMessage(RegistrationRequest $existing, string $subject): string
    {
        if ($existing->status === RegistrationRequest::STATUS_PENDING) {
            return "A registration request {$subject} is already waiting for review.";
        }

        $retryAt = ($existing->reviewed_at ?? $existing->created_at)
            ->copy()
            ->addHours(self::RETRY_AFTER_HOURS);

        return "The last request {$subject} was not approved. You can submit a new one {$retryAt->diffForHumans()}.";
    }

    /** Request input as a plain string (arrays or junk become ''). */
    private function text(string $key): string
    {
        $value = $this->input($key);

        return is_string($value) ? $value : '';
    }
}