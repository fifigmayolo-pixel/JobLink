<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSearchabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && in_array($user->role, ['student', 'alumni'])
            && $user->profile !== null;
    }

    public function rules(): array
    {
        // The form sends a hidden 0 and, when the switch is on, a 1 after it.
        return [
            'searchable' => ['required', 'boolean'],
        ];
    }
}
