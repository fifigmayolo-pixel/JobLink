<?php

namespace App\Models;

use App\Policies\ProfileClearancePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * One uploaded clearance (NBI or barangay) of one student or alumni profile.
 *
 * The file itself lives in a private disk and is only ever opened through
 * ClearanceFileController, which asks ProfileClearancePolicy first.
 */
#[UsePolicy(ProfileClearancePolicy::class)]
class ProfileClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'type',
        'file_path',
        'size_bytes',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(StudentAlumniProfile::class, 'profile_id');
    }

    /**
     * The clearances of one profile, keyed by type. An empty collection for no profile
     * (for example an application that was withdrawn, which no longer points to one).
     *
     * @return Collection<string, ProfileClearance>
     */
    public static function forProfile(?int $profileId): Collection
    {
        if ($profileId === null) {
            return collect();
        }

        return static::query()->where('profile_id', $profileId)->get()->keyBy('type');
    }

    /** "NBI clearance" or "Barangay clearance". */
    public function label(): string
    {
        return config("clearances.types.{$this->type}.label", ucfirst($this->type).' clearance');
    }

    /** The name the browser shows for the PDF. Fixed, so nothing the user typed ends up in a header. */
    public function downloadName(): string
    {
        return config("clearances.types.{$this->type}.download", 'Clearance.pdf');
    }

    /**
     * Valid through the date shown; Expired from the next day.
     */
    public function isExpired(): bool
    {
        return $this->valid_until->lt(today());
    }

    public function statusLabel(): string
    {
        return $this->isExpired() ? 'Expired' : 'Valid';
    }

    public function sizeLabel(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 1).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
