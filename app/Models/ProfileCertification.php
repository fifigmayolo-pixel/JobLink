<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileCertification extends Model
{
    public const KINDS = [
        'certification' => 'Certification',
        'seminar' => 'Seminar / Training',
    ];

    protected $fillable = [
        'kind',
        'title',
        'issuer',
        'date_received',
    ];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }
}