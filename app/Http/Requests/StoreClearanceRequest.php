<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Uploading or replacing one clearance. The document type comes from the URL
 * (the route only allows the types in config/clearances.php).
 *
 * The file is checked on the server three ways, because a file name or a browser
 * "accept" attribute proves nothing: the extension, the real content type, and the
 * "%PDF-" signature at the start of the file.
 */
class StoreClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && in_array($user->role, ['student', 'alumni'], true)
            && $user->profile !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.(int) config('clearances.max_kb', 4096)],
            'valid_until' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.$this->latestValidUntil(),
            ],
            // Only asked until the person has agreed once.
            'consent' => $this->needsConsent() ? ['accepted'] : ['nullable'],
        ];
    }

    public function messages(): array
    {
        $megabytes = rtrim(rtrim(number_format((int) config('clearances.max_kb', 4096) / 1024, 1), '0'), '.');

        return [
            'file.required' => 'Choose the PDF file to upload.',
            'file.file' => 'Choose the PDF file to upload.',
            'file.uploaded' => 'The file could not be uploaded. Check that it is a PDF of '.$megabytes.' MB or less, then try again.',
            'file.mimes' => 'Only PDF files are accepted.',
            'file.mimetypes' => 'Only PDF files are accepted.',
            'file.max' => 'The file is too large. The limit is '.$megabytes.' MB.',
            'valid_until.required' => 'Enter the date this clearance is valid until.',
            'valid_until.date_format' => 'Enter the validity date as year-month-day, for example 2027-03-15.',
            'valid_until.after_or_equal' => 'That date has already passed, so the clearance has expired. Upload a current one.',
            'valid_until.before_or_equal' => 'That date is too far ahead. Check the validity date printed on the clearance.',
            'consent.accepted' => 'Tick the box to confirm that companies you apply to may open this file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'file',
            'valid_until' => 'validity date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // The size or type check already failed: say that, not a second error.
            if ($validator->errors()->has('file')) {
                return;
            }

            $file = $this->file('file');

            if (! $file || ! $this->hasPdfSignature($file->getRealPath())) {
                $validator->errors()->add('file', 'Only PDF files are accepted. This file does not look like a PDF.');
            }
        });
    }

    private function needsConsent(): bool
    {
        return $this->user()?->profile?->clearance_consent_at === null;
    }

    private function latestValidUntil(): string
    {
        return now()->addYears((int) config('clearances.max_validity_years', 2))->toDateString();
    }

    /**
     * Every PDF starts with "%PDF-" (a few writers put a little padding before it).
     */
    private function hasPdfSignature(string|false $path): bool
    {
        if ($path === false || ! is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 1024);
        fclose($handle);

        return str_contains($head, '%PDF-');
    }
}
