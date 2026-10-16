<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TracerStudyAccess extends Model
{
    protected $fillable = [
        'user_id',
        'tracer_study_link_id',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function tracerStudyLink()
    {
        return $this->belongsTo(TracerStudyLink::class);
    }
}