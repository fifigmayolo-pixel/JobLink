<?php

namespace App\Rules;

use App\Support\StudentId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Usage in a FormRequest:
 *
 *   protected function prepareForValidation(): void
 *   {
 *       $this->merge([
 *           'student_id_number' => StudentId::normalize($this->input('student_id_number')),
 *       ]);
 *   }
 *
 *   'student_id_number' => ['required', 'string', new StudentIdNumber],
 *
 * The rule is strict on purpose: it expects the value to be normalized first,
 * so what gets validated is exactly what gets stored.
 */
class StudentIdNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! StudentId::isValid($value)) {
            $fail('Student ID must follow the format '.StudentId::EXAMPLE
                .' (2 digits, '.StudentId::CAMPUS_CODE.', then '.StudentId::NUMBER_DIGITS.' digits).');
        }
    }
}