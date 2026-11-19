<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'slots',
        'closed_by_slots',
        'posted_by',
        'company_id',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_posted' => 'date',
            'application_deadline' => 'date',
            'reviewed_at' => 'datetime',
            'slots' => 'integer',
            'closed_by_slots' => 'boolean',
        ];
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * withTrashed(): the reviewing Coordinator may have been removed since.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
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
     * Approval is separate from open/closed: a posting is only visible to
     * students and alumni when it is BOTH approved and open.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * One company's postings, every status. A null company matches NOTHING: a plain
     * where('company_id', null) would turn into "company_id IS NULL" and return
     * every posting that has no company tagged.
     */
    public function scopeOfCompany($query, ?int $companyId)
    {
        return $companyId === null
            ? $query->whereRaw('1 = 0')
            : $query->where('job_postings.company_id', $companyId);
    }

    /**
     * Filter for the company's own list: pending, rejected, open or closed.
     * "closed" covers a posting that was closed by hand and one whose deadline passed.
     * Any other value leaves the list as it is.
     */
    public function scopeInListingState($query, ?string $state)
    {
        return match ($state) {
            'pending' => $query->awaitingApproval(),
            'rejected' => $query->where('approval_status', 'rejected'),
            'open' => $query->approved()->open(),
            'closed' => $query->approved()->where(function ($q) {
                $q->where('status', '!=', 'open')
                    ->orWhere('application_deadline', '<', now()->toDateString());
            }),
            default => $query,
        };
    }

    public const LISTING_STATES = ['pending', 'rejected', 'open', 'closed'];

    /**
     * Where this posting stands, for the company that posted it. Same meaning as
     * the scopes above: pending, rejected, open (approved, open, deadline not passed)
     * or closed (approved but closed by hand or past its deadline).
     */
    public function listingState(): string
    {
        if ($this->isPending()) {
            return 'pending';
        }

        if ($this->isRejected()) {
            return 'rejected';
        }

        $stillOpen = $this->status === 'open'
            && $this->application_deadline !== null
            && $this->application_deadline->toDateString() >= now()->toDateString();

        return $stillOpen ? 'open' : 'closed';
    }
    
        /**
     * A posting only counts as live while its company's login is not archived.
     * Archiving soft-deletes the User, and whereHas respects that, so nothing
     * on the job rows changes: archive hides them, restore brings them back,
     * and the deadline check in scopeOpen still hides the expired ones.
     */
    public function scopeFromActiveCompanies($query)
    {
    return $query->where(function ($q) {
        $q->whereNull('company_id')
            ->orHas('company.user');
    });
     }

    /**
     * Everything a student/alumni is allowed to see in one call:
     * approved + open (status and deadline) + company not archived.
     */
    public function scopeListed($query)
    {
        return $query->approved()->open()->fromActiveCompanies();
    }

    /**
     * For single-job checks (show page, apply, save) where you already
     * have the model and just need a yes/no.
     */
    public function companyIsActive(): bool
    {
    return $this->company_id === null || $this->company?->user !== null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    // ---- Vacancy slots ------------------------------------------------------
    //
    // A slot is "filled" by an applicant whose status is Interview (JobLink does not
    // track hiring). When every slot is filled the posting closes by itself; when a
    // slot frees up again it reopens, but only if JobLink was the one that closed it.

    public const MAX_SLOTS = 1000;

    /**
     * Adds interview_count to each posting in one query, so a list of cards
     * does not run a count per card.
     */
    public function scopeWithInterviewCount($query)
    {
        return $query->withCount(['applications as interview_count' => fn ($q) => $q->where('status', 'interview')]);
    }

    public function slotsTotal(): int
    {
        return max(1, (int) $this->slots);
    }

    /**
     * People currently invited to interview. Uses interview_count when the posting
     * was loaded with scopeWithInterviewCount, and asks the database otherwise.
     */
    public function filledSlots(): int
    {
        if (array_key_exists('interview_count', $this->attributes)) {
            return (int) $this->attributes['interview_count'];
        }

        return $this->interviewCount();
    }

    /**
     * Always a fresh count, never the loaded one. Used where the answer decides something.
     */
    public function interviewCount(): int
    {
        return $this->applications()->where('status', 'interview')->count();
    }

    public function isFull(): bool
    {
        return $this->filledSlots() >= $this->slotsTotal();
    }

    /**
     * "3 of 5 interview slots filled". These are interview invitations, not hires.
     */
    public function slotsLabel(): string
    {
        $total = $this->slotsTotal();

        return $this->filledSlots().' of '.$total.' interview '.Str::plural('slot', $total).' filled';
    }

    /**
     * The badge for a posting that is not taking applications: "Full" when JobLink
     * closed it because every slot is taken, "Closed" when it was closed by hand.
     * Null for a posting that is still open.
     */
    public function closedLabel(): ?string
    {
        if ($this->status !== 'closed') {
            return null;
        }

        return $this->closed_by_slots ? 'Full' : 'Closed';
    }

    /**
     * Closes the posting when every slot is taken and reopens it when one is free again.
     * Returns 'closed', 'reopened' or null (nothing changed).
     *
     * Runs in a transaction with the row locked, so two status changes at the same moment
     * cannot both read an old count. Every change is written to the audit log.
     */
    public function syncSlots(int $changedBy): ?string
    {
        return DB::transaction(function () use ($changedBy) {
            $posting = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $posting) {
                return null; // removed since
            }

            $filled = $posting->interviewCount();
            $total = $posting->slotsTotal();
            $result = null;

            if ($posting->status === 'open' && $filled >= $total) {
                $posting->update(['status' => 'closed', 'closed_by_slots' => true]);
                $result = 'closed';

                FieldChangeLog::create([
                    'action' => 'job_posting_closed_full',
                    'changed_by' => $changedBy,
                    'job_posting_id' => $posting->id,
                    'details' => "Closed automatically: {$filled} of {$total} interview ".Str::plural('slot', $total).' filled.',
                ]);
            } elseif ($posting->status === 'closed' && $posting->closed_by_slots && $filled < $total) {
                $posting->update(['status' => 'open', 'closed_by_slots' => false]);
                $result = 'reopened';

                FieldChangeLog::create([
                    'action' => 'job_posting_reopened_slots',
                    'changed_by' => $changedBy,
                    'job_posting_id' => $posting->id,
                    'details' => "Reopened automatically: {$filled} of {$total} interview ".Str::plural('slot', $total).' filled.',
                ]);
            }

            // Keep the caller's copy in step with what was just saved.
            $this->setRawAttributes($posting->getAttributes(), true);

            return $result;
        });
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