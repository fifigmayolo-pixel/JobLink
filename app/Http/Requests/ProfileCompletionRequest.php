<?php

namespace App\Http\Requests;

use App\Models\StudentAlumniProfile;
use App\Support\PhilippineLocations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['student', 'alumni'])
            && ! $this->user()->profile?->profile_completed_at;
    }

    public function rules(): array
    {
        $isStudent = $this->user()->role === 'student';

        return [
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            // full_name intentionally not here — set at registration
            // (users.full_name), copied in by the controller, not re-typed.
            'date_of_birth' => ['required', 'date', 'before:today'],
            'age' => ['required', 'integer', 'min:15', 'max:100'],
            'gender' => ['required', 'string'],
            'civil_status' => ['required', 'string'],
            'nationality' => ['required', Rule::in(StudentAlumniProfile::NATIONALITIES)],
            'course_program' => ['required', Rule::in(StudentAlumniProfile::PROGRAMS)],
            'year_level' => [Rule::requiredIf($isStudent), 'nullable', Rule::in(StudentAlumniProfile::YEAR_LEVELS)],
            'section' => [Rule::requiredIf($isStudent), 'nullable', Rule::in(StudentAlumniProfile::SECTIONS)],
            'year_graduated' => [Rule::requiredIf(! $isStudent), 'nullable', Rule::in(StudentAlumniProfile::academicYearRanges())],
            'personal_email' => ['required', 'email'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'house_street' => ['required', 'string'],
            'province' => ['required', Rule::in(PhilippineLocations::provinces())],
            'municipality_city' => ['required', Rule::in(PhilippineLocations::citiesForProvince($this->input('province')))],
            'barangay' => ['required', Rule::in(PhilippineLocations::barangaysForCity($this->input('province'), $this->input('municipality_city')))],
            'postal_code' => ['required', 'string', 'max:10'],
        ];
    }
}