<?php

namespace App\Imports;

use App\Models\User;
use App\Rules\StudentIdNumber;
use App\Support\StudentId;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentRosterImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use SkipsFailures;

    public function __construct(private string $role) {}

    /**
     * Runs on each row before the rules below. "23ac2344", "23-ac-2344" or an
     * ID with stray spaces becomes "23-AC-2344", so a sloppy spreadsheet still
     * imports cleanly. Anything that still doesn't fit the format fails the
     * StudentIdNumber rule and the row is skipped (reported through failures()).
     */
    public function prepareForValidation($data, $index)
    {
        $data['student_id'] = StudentId::normalize($this->cell($data['student_id'] ?? null));

        return $data;
    }

    /**
     * WithHeadingRow lowercases + underscores the header row, so a sheet
     * with "Student ID" and "Full Name" columns maps to these keys.
     */
    public function model(array $row)
    {
        return new User([
            'role' => $this->role,
            // normalize() is idempotent, so this is safe even if the row was already prepared.
            'student_id_number' => StudentId::normalize($this->cell($row['student_id'] ?? null)),
            'full_name' => trim($this->cell($row['full_name'] ?? null)),
            'password' => Str::password(32),
            'must_change_password' => true,
            'activated_at' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', new StudentIdNumber, 'unique:users,student_id_number'],
            'full_name' => ['required'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'student_id.unique' => 'This Student ID is already in the system.',
        ];
    }

    public function customValidationAttributes()
    {
        return [
            'student_id' => 'Student ID',
            'full_name' => 'Full name',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    /** Excel cells can be numbers, dates or empty; always work with a plain string. */
    private function cell(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}