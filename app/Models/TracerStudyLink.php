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

    public function accesses()
    {
        return $this->hasMany(TracerStudyAccess::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * "Replace" (Phase 1) creates a new row and deactivates the old one,
     * rather than overwriting url in place — keeps a history of past links.
     * Because each year's link is its own row, access records are per study.
     */
    public static function current(): ?self
    {
        return static::active()->latest()->first();
    }

    public function accessedBy(User $user): bool
    {
        return $this->accesses()->where('user_id', $user->id)->exists();
    }

    /**
     * Eligible = alumni accounts that are activated and not archived.
     * (Soft-deleted users are excluded automatically.)
     */
    public static function eligibleAlumni()
    {
        return User::alumni()->whereNotNull('activated_at');
    }

    /**
     * Monitoring numbers for this study. "Accessed" only counts alumni who are
     * eligible now, so an archived alumnus with an old record can't push
     * "Not Yet Accessed" below zero. JobLink cannot see whether the external
     * form was submitted — this only measures who opened it.
     */
    public function participation(): array
    {
        $eligible = static::eligibleAlumni()->count();

        $accessed = static::eligibleAlumni()
            ->whereIn('id', TracerStudyAccess::query()
                ->where('tracer_study_link_id', $this->id)
                ->select('user_id'))
            ->count();

        $rate = $eligible > 0 ? (int) round($accessed / $eligible * 100) : 0;

        return [
            'eligible' => $eligible,
            'accessed' => $accessed,
            'not_accessed' => $eligible - $accessed,
            'rate' => $rate,
            'not_accessed_pct' => $eligible > 0 ? 100 - $rate : 0,
        ];
    }
}