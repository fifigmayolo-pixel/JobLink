<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['student', 'alumni']);
    }

    /**
     * Deliberately excludes every field in StudentAlumniProfile::LOCKED_FIELDS.
     * $request->validated() only returns keys that have rules below, so even
     * if a locked field is submitted in the payload it's silently dropped —
     * it never reaches the controller's update() call.
     */
    public function rules(): array
    {
        return [
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'personal_email' => ['required', 'email'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'house_street' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'municipality_city' => ['required', 'string'],
            'province' => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:10'],
        ];
    }
}