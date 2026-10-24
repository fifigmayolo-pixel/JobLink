{{--
    resources/views/partials/job-card.blade.php

    Reusable job card. Border, logo badge and the job-type pill are all
    colored from App\Support\CompanyAccent, keyed to the company so the
    same company always gets the same color everywhere.

    Expects: $job (a JobPosting with its `company` relation loaded).
    Optional:
      - $savedMode (bool, default false) — pass true on the Saved Jobs page.
        Treats the job as saved unconditionally (no need to also pass
        $savedJobIds there — it's already known to be saved).
      - $savedJobIds (array, default []) — pass the current user's saved
        job_posting_id's on the main listing page, computed ONCE per page
        load (see jobs/index.blade.php) rather than queried per card. When
        $job->id is in this list, the button shows as already-saved too.
    Either way, the button always toggles correctly: outline bookmark +
    "Save" when not saved, filled bookmark + "Remove" when it is — it
    never hides behind the urgency pill, since that's exactly when someone
    is most likely to want to save a job.
--}}
@php
    $savedJobIds = $savedJobIds ?? [];
    $isSaved = ($savedMode ?? false) || in_array($job->id, $savedJobIds);
    $accent = \App\Support\CompanyAccent::for($job->company?->id, $job->company?->company_name);

    $deadline = $job->application_deadline->copy()->startOfDay();
    $daysLeft = now()->startOfDay()->diffInDays($deadline, false); // positive = days remaining
    $isUrgent = $daysLeft >= 0 && $daysLeft <= 14;

    $jobTypeLabels = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'internship' => 'Internship',
    ];
    $jobTypeLabel = $jobTypeLabels[$job->job_type] ?? \Illuminate\Support\Str::headline($job->job_type ?? '');
@endphp

{{-- No h-100 here: it's a literal `height:100%`, which doesn't qualify for
     flexbox's "stretch to the tallest card in the row" behavior (only
     height:auto does). Leaving height unset lets that happen automatically
     now that this card is a direct child of the flex-wrap row. --}}
<div class="jl-card jl-card-hover jl-job-card p-3 d-flex flex-column"
     style="--accent: {{ $accent['base'] }}; --accent-soft: {{ $accent['soft'] }}; --accent-text: {{ $accent['text'] }}; --accent-deep: {{ $accent['deep'] }};">

    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="jl-logo-badge {{ $job->company?->logo ? '' : 'jl-logo-badge-fallback' }}" data-fallback-initial="{{ \App\Support\CompanyAccent::initial($job->company?->company_name) }}">
                @if($job->company?->logo)
                    {{-- If the stored logo path 404s (e.g. missing on this deploy's
                         storage disk), swap to the colored initial badge instead of
                         showing the browser's broken-image icon + alt text. --}}
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($job->company->logo) }}" alt="{{ $job->company->company_name }} logo"
                         onerror="this.closest('.jl-logo-badge').classList.add('jl-logo-badge-fallback'); this.closest('.jl-logo-badge').textContent = this.closest('.jl-logo-badge').dataset.fallbackInitial;">
                @else
                    {{ \App\Support\CompanyAccent::initial($job->company?->company_name) }}
                @endif
            </div>
            <div>
                <div class="fw-semibold small" style="color: var(--jl-navy);">{{ $job->company?->company_name ?? 'Company' }}</div>
                <div class="text-secondary" style="font-size: 0.78rem;">Posted {{ $job->date_posted->format('M j, Y') }}</div>
            </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
            @if($isUrgent)
                <span class="jl-pill jl-pill-urgent">{{ $daysLeft }}{{ \Illuminate\Support\Str::plural('d', $daysLeft) }} left</span>
            @endif
            @if($jobTypeLabel)
                <span class="jl-pill jl-pill-neutral">{{ $jobTypeLabel }}</span>
            @endif
        </div>
    </div>

    <h2 class="h5 mb-1">{{ $job->title }}</h2>
    <p class="text-secondary small mb-3">
        <i class="bi bi-geo-alt"></i> {{ $job->location }}
    </p>

    <p class="mb-2 flex-grow-1">{{ \Illuminate\Support\Str::limit($job->description, 90) }}</p>

    <hr class="jl-rule my-2">

    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <div class="text-secondary" style="font-size: 0.72rem;">Deadline</div>
            <div class="fw-semibold" style="color: var(--jl-navy);">{{ $job->application_deadline->format('M j, Y') }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @auth
                @role('student|alumni')
                    @if($isSaved)
                        <form method="POST" action="{{ route('saved-jobs.destroy', $job) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary btn-sm jl-icon-btn jl-icon-btn-saved" aria-label="Remove from saved jobs" title="Saved — click to remove">
                                <i class="bi bi-bookmark-check-fill"></i>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('saved-jobs.store', $job) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm jl-icon-btn" aria-label="Save job" title="Save job">
                                <i class="bi bi-bookmark"></i>
                            </button>
                        </form>
                    @endif
                @endrole
            @endauth
            <a href="{{ route('jobs.show', $job) }}" class="btn btn-jl-navy btn-sm">View</a>
        </div>
    </div>
</div>