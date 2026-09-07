<?php

namespace App\Imports;

use App\Models\User;
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
     * WithHeadingRow lowercases + underscores the header row, so a sheet
     * with "Student ID" and "Full Name" columns maps to these keys.
     */
    public function model(array $row)
    {
        return new User([
            'role' => $this->role,
            'student_id_number' => trim((string) $row['student_id']),
            'full_name' => trim((string) $row['full_name']),
            'password' => Str::password(32),
            'must_change_password' => true,
            'activated_at' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'unique:users,student_id_number'],
            'full_name' => ['required'],
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}