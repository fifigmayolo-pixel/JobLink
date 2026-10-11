<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

        protected $fillable = [
        'title',
        'job_type',
        'description',
        'qualifications',
        'requirements',
        'location',
        'date_posted',
        'application_deadline',
        'status',
        'posted_by',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'date_posted' => 'date',
            'application_deadline' => 'date',
        ];
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedBy()
    {
        return $this->hasMany(SavedJob::class);
    }

    /**
     * "Open" means status is open AND the deadline hasn't passed. Cheaper than
     * a scheduled command flipping status daily — revisit if Phase 10 needs
     * closed-but-not-expired as a distinct state.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where('application_deadline', '>=', now()->toDateString());
    }

    /**
     * Description/Qualifications/Requirements are stored as free text with
     * the user's own line breaks — these turn each non-empty line into a
     * displayable bullet without needing a schema change.
     */
    public function descriptionLines(): array
    {
        return $this->linesOf($this->description);
    }

    public function qualificationsLines(): array
    {
        return $this->linesOf($this->qualifications);
    }

    public function requirementsLines(): array
    {
        return $this->linesOf($this->requirements);
    }

    private function linesOf(?string $text): array
    {
        if (! $text) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}