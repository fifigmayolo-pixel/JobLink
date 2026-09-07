<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TracerStudyLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * "Replace" (Phase 1) creates a new row and deactivates the old one,
     * rather than overwriting url in place — keeps a history of past links.
     */
    public static function current(): ?self
    {
        return static::active()->latest()->first();
    }
}