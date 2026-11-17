@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $percent = (int) ($completeness['percent'] ?? 0);
    $missingSections = collect($completeness['sections'] ?? [])->where('done', false);

    $statusUi = [
        'pending' => ['label' => 'Pending', 'icon' => 'bi-hourglass-split', 'tab' => 'active', 'hint' => 'Waiting for the company'],
        'interview' => ['label' => 'Interview', 'icon' => 'bi-chat-left-text', 'tab' => 'active', 'hint' => 'Invited to interview'],
        'rejected' => ['label' => 'Rejected', 'icon' => 'bi-x-circle', 'tab' => 'past', 'hint' => 'Not moving forward'],
        'withdrawn' => ['label' => 'Withdrawn', 'icon' => 'bi-arrow-counterclockwise', 'tab' => 'past', 'hint' => 'You withdrew'],
    ];

    // Same icons as the Notifications page.
    $notificationIcon = fn ($type) => match ($type) {
        'announcement' => 'bi-megaphone',
        'custom_company_message' => 'bi-chat-dots',
        'tracer_study' => 'bi-clipboard2-check',
        'bulk_conversion_complete' => 'bi-people',
        'new_job_posting' => 'bi-briefcase-fill',
        'job_posting_approved' => 'bi-check-circle-fill',
        'job_posting_rejected' => 'bi-x-circle-fill',
        'job_posting_tagged' => 'bi-send-check-fill',
        default => 'bi-building',
    };
@endphp

<div class="mb-4">
    <h1 class="h2 mb-1">Dashboard</h1>
    <p class="text-secondary mb-0">Welcome back, {{ $profile?->full_name ?? $user->email }}.</p>
</div>

{{-- ============ Profile completeness ============ --}}
<section class="jl-card p-4 mb-4" aria-labelledby="dash-completeness">
    <div class="row g-3 align-items-center">
        <div class="col-md">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h2 class="h5 mb-0" id="dash-completeness">Profile completeness</h2>
                <span class="fw-bold" style="color: var(--jl-navy);">{{ $percent }}%</span>
            </div>

            <div class="progress mb-3" style="height: 10px; background: rgba(15,42,92,0.08);"
                 role="progressbar" aria-label="Profile completeness" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $percent }}%; background: var(--jl-gold);"></div>
            </div>

            @if(! $basicProfileDone)
                <p class="mb-0 text-secondary">Complete your basic profile first, then build your CV. You need a CV before you can apply to jobs.</p>
            @elseif(! $cvReady)
                <p class="mb-2 text-secondary">Your CV is not ready to apply with yet. Still needed:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($completeness['missing_required'] as $label)
                        <span class="jl-pill jl-pill-urgent">{{ $label }}</span>
                    @endforeach
                </div>
            @elseif($missingSections->isNotEmpty())
                <p class="mb-2 text-secondary">Your CV is ready to apply with. Add more to stand out:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($missingSections as $section)
                        <span class="jl-pill jl-pill-neutral">{{ $section['label'] }}</span>
                    @endforeach
                </div>
            @else
                <p class="mb-0 text-secondary"><i class="bi bi-check-circle-fill" style="color: var(--jl-gold);" aria-hidden="true"></i> Your CV is complete.</p>
            @endif
        </div>

        <div class="col-md-auto">
            @if(! $basicProfileDone)
                <a href="{{ route('profile.complete') }}" class="btn btn-jl-gold w-100">Complete my profile</a>
            @elseif(! $cvReady)
                <a href="{{ route('cv.edit') }}" class="btn btn-jl-gold w-100">Finish my CV</a>
            @elseif($missingSections->isNotEmpty())
                <a href="{{ route('cv.edit') }}" class="btn btn-jl-navy w-100">Improve my CV</a>
            @else
                <a href="{{ route('cv.edit') }}" class="btn btn-outline-secondary w-100">View my CV</a>
            @endif
        </div>
    </div>
</section>

{{-- ============ Applications by status ============ --}}
<section class="mb-4" aria-labelledby="dash-counts">
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <h2 class="h5 mb-0" id="dash-counts">Your applications</h2>
        <span class="text-secondary small">{{ $totalApplications }} {{ \Illuminate\Support\Str::plural('application', $totalApplications) }}</span>
    </div>
    <div class="row g-3">
        @foreach($statusUi as $status => $ui)
            <div class="col-6 col-lg-3">
                <a href="{{ route('applications.index', ['tab' => $ui['tab']]) }}" class="text-decoration-none d-block h-100">
                    <div class="jl-card jl-card-hover p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <span class="jl-section-icon jl-badge-{{ $status }}" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi {{ $ui['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <div style="min-width: 0;">
                                <div class="display-font" style="font-size: 1.6rem; line-height: 1.1; color: var(--jl-navy);">{{ $statusCounts[$status] }}</div>
                                <div class="small fw-semibold" style="color: var(--jl-ink);">{{ $ui['label'] }}</div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">{{ $ui['hint'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>

<div class="row g-4">
    {{-- ============ Suggested jobs ============ --}}
    <div class="col-lg-7">
        <section class="jl-card p-4 h-100" aria-labelledby="dash-suggested">
            <h2 class="h5 mb-1" id="dash-suggested">Suggested jobs based on your profile</h2>
            <p class="text-secondary small mb-3">Open jobs that mention words from your skills, course and education.</p>

            @if(! $cvReady)
                <div class="text-center py-4">
                    <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                    </span>
                    <h3 class="h6 mb-1">Finish your CV to see suggested jobs</h3>
                    <p class="text-secondary small mb-3">Suggestions come from your skills, course and education, so they appear once your CV has a summary and at least one skill.</p>
                    <a href="{{ $basicProfileDone ? route('cv.edit') : route('profile.complete') }}" class="btn btn-jl-gold btn-sm">
                        {{ $basicProfileDone ? 'Finish my CV' : 'Complete my profile' }}
                    </a>
                </div>
            @elseif($suggestions->isEmpty())
                <div class="text-center py-4">
                    <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <h3 class="h6 mb-1">No matching jobs right now</h3>
                    <p class="text-secondary small mb-3">Nothing open matches your skills and course yet. Adding more skills to your CV helps, or browse every open job.</p>
                    <a href="{{ route('jobs.index') }}" class="btn btn-jl-navy btn-sm">Browse all jobs</a>
                </div>
            @else
                @foreach($suggestions as $match)
                    @php
                        $job = $match['job'];
                        $company = $job->company;
                    @endphp
                    <div class="d-flex gap-3 py-3 {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important;">
                        @include('partials.profile-avatar', ['imagePath' => $company?->logo, 'label' => null, 'size' => 44, 'icon' => 'bi-building'])
                        <div class="flex-grow-1" style="min-width: 0;">
                            <a href="{{ route('jobs.show', $job) }}" class="fw-semibold text-decoration-none d-block text-truncate" style="color: var(--jl-navy);">{{ $job->title }}</a>
                            <div class="text-secondary small">
                                {{ $company?->company_name ?? 'Company' }}
                                &middot; {{ $job->location }}
                                @if($job->job_type)
                                    &middot; {{ \App\Support\JobCardData::typeLabel($job->job_type) }}
                                @endif
                                &middot; Apply by {{ $job->application_deadline->format('M j, Y') }}
                            </div>
                            @if(! empty($match['matched']))
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <span class="text-secondary small me-1">Matches:</span>
                                    @foreach(array_slice($match['matched'], 0, 3) as $word)
                                        <span class="jl-pill jl-pill-neutral" style="padding: 0.15rem 0.6rem; font-size: 0.72rem;">{{ $word }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary btn-sm align-self-center flex-shrink-0">View</a>
                    </div>
                @endforeach
            @endif
        </section>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-4">
        {{-- ============ Latest applications ============ --}}
        <section class="jl-card p-4" aria-labelledby="dash-latest">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h2 class="h5 mb-0" id="dash-latest">Latest applications</h2>
                <a href="{{ route('applications.index') }}" class="small">View all</a>
            </div>

            @forelse($latestApplications as $application)
                @php
                    $applicationJob = $application->jobPosting;
                    $applicationCompany = $applicationJob?->company;
                @endphp
                <a href="{{ route('applications.show', $application) }}" class="d-flex align-items-center gap-3 py-3 text-decoration-none {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important; color: inherit;">
                    @include('partials.profile-avatar', ['imagePath' => $applicationCompany?->logo, 'label' => null, 'size' => 40, 'icon' => 'bi-building'])
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-semibold text-truncate" style="color: var(--jl-navy);">{{ $applicationJob?->title ?? 'Job removed' }}</div>
                        <div class="text-secondary small text-truncate">{{ $applicationCompany?->company_name ?? 'Company' }} &middot; Applied {{ $application->date_applied->format('M j, Y') }}</div>
                    </div>
                    <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2 flex-shrink-0">{{ ucfirst($application->status) }}</span>
                </a>
            @empty
                <p class="text-secondary mb-0 py-2">You have not applied to anything yet. <a href="{{ route('jobs.index') }}">Browse open positions</a> to get started.</p>
            @endforelse
        </section>

        {{-- ============ Recent notifications ============ --}}
        <section class="jl-card p-4" aria-labelledby="dash-notifications">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                <h2 class="h5 mb-0" id="dash-notifications">
                    Recent notifications
                    @if($unreadCount > 0)
                        <span class="badge rounded-pill ms-1 align-middle" style="background: var(--jl-gold); color: var(--jl-navy-deep); font-size: 0.7rem;">{{ $unreadCount }} new</span>
                    @endif
                </h2>
                <a href="{{ route('notifications.index') }}" class="small flex-shrink-0">View all</a>
            </div>

            @forelse($notifications as $notification)
                @php
                    $senderImagePath = in_array($notification->type, ['job_posting_approved', 'job_posting_rejected'])
                        ? null
                        : ($notification->application?->jobPosting?->company?->logo
                            ?? $notification->jobPosting?->company?->logo);
                    $linkedJob = $notification->jobPosting;
                    $unread = $notification->read_at === null;
                @endphp
                <div class="d-flex gap-3 py-3 {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important;">
                    @include('partials.profile-avatar', ['imagePath' => $senderImagePath, 'label' => null, 'size' => 40, 'icon' => $notificationIcon($notification->type)])
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="{{ $unread ? 'fw-bold' : 'fw-semibold' }}" style="color: var(--jl-navy);">{{ $notification->title }}</div>
                            @if($unread)
                                <span class="flex-shrink-0 mt-1" style="width: 9px; height: 9px; border-radius: 50%; background: var(--jl-gold);" title="Unread" aria-label="Unread"></span>
                            @endif
                        </div>
                        <div class="small">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</div>
                        <div class="text-secondary small">
                            {{ $notification->created_at->diffForHumans() }}
                            @if($linkedJob && ! $linkedJob->trashed())
                                &middot; <a href="{{ route('jobs.show', $linkedJob) }}">View job</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-secondary mb-0 py-2">No notifications yet.</p>
            @endforelse

            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="mt-2">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
