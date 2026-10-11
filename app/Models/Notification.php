<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_id',
        'type',
        'title',
        'message',
        'related_application_id',
        'related_job_posting_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'related_application_id');
    }

    /**
     * withTrashed(): a removed posting still resolves, so the notification can
     * keep showing the company logo. The view checks ->trashed() before
     * offering a "View job" link.
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'related_job_posting_id')->withTrashed();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}