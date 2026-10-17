@extends('layouts.app')

@section('title', 'Job Approvals')

@section('content')
<h1 class="h2 mb-1">Job Approvals</h1>
<p class="text-secondary mb-4">Review job postings from companies before they appear on the Jobs screen.</p>

<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
        <a href="{{ route('coordinator.job-approvals.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
            {{ $label }} ({{ $counts[$key] ?? 0 }})
        </a>
    @endforeach
</div>

<hr class="jl-rule">

@forelse($jobPostings as $job)
    <div class="jl-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="jl-company-logo" style="width: 48px; height: 48px;">
                    @if($job->company?->logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($job->company->logo) }}" alt="{{ $job->company->company_name }} logo">
                    @else
                        <div class="jl-company-logo-placeholder" style="font-size: 1.3rem;">
                            <i class="bi bi-building"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <h2 class="h6 mb-1">
                        <a href="{{ route('jobs.show', $job) }}" class="text-decoration-none">{{ $job->title }}</a>
                        @if($job->job_type)
                            <span class="badge rounded-pill ms-1" style="background: rgba(15,42,92,0.08); color: var(--jl-navy); font-weight: 600;">
                                {{ $job->job_type === 'full_time' ? 'Full-time' : 'Part-time' }}
                            </span>
                        @endif
                    </h2>
                    <p class="text-secondary small mb-2">
                        {{ $job->company?->company_name ?? 'Company' }} &middot; {{ $job->location }}
                        &middot; Deadline {{ $job->application_deadline->format('M j, Y') }}
                        &middot; Submitted {{ $job->created_at->diffForHumans() }}
                    </p>
                    <p class="mb-0">{{ \Illuminate\Support\Str::limit($job->description, 160) }}</p>

                    @if($job->approval_status === 'approved')
                        <p class="text-secondary small mb-0 mt-2">
                            Approved by {{ $job->reviewer?->full_name ?? $job->reviewer?->email ?? 'a Coordinator' }}
                            @if($job->reviewed_at) on {{ $job->reviewed_at->format('M j, Y') }} @endif
                        </p>
                    @elseif($job->approval_status === 'rejected')
                        <p class="small mb-0 mt-2" style="color: var(--jl-rose);">
                            Rejected by {{ $job->reviewer?->full_name ?? $job->reviewer?->email ?? 'a Coordinator' }}
                            @if($job->reviewed_at) on {{ $job->reviewed_at->format('M j, Y') }} @endif
                            &mdash; {{ $job->rejection_reason }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary btn-sm">View details</a>
                @if($job->approval_status === 'pending')
                                    <form method="POST" action="{{ route('coordinator.job-approvals.approve', $job) }}"
                    data-confirm="Students and alumni will be notified that this job is now live."
                    data-confirm-title="Approve this posting?"
                    data-confirm-button="Approve"
                    data-confirm-icon="patch-check">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-jl-gold btn-sm">Approve</button>
              </form> 
                    <form method="POST" action="{{ route('coordinator.job-approvals.reject', $job) }}"
      data-reason="The company will see this reason, so make it clear and specific."
      data-reason-title="Reject this posting?"
      data-reason-label="Reason for rejection"
      data-reason-button="Reject posting"
      data-reason-variant="danger"
      data-reason-icon="x-circle">
    @csrf
    @method('PATCH')
    <input type="hidden" name="reason">
    <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
</form>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">
            @if($status === 'pending')
                No postings are waiting for approval.
            @else
                No {{ $status }} postings.
            @endif
        </p>
    </div>
@endforelse

{{ $jobPostings->links() }}

@push('scripts')
<script>
const jlCsrfToken = '{{ csrf_token() }}';

function rejectPosting(url) {
    const reason = prompt('Reason for rejecting this posting (required — the company will see it):');
    if (reason === null) {
        return;
    }
    if (reason.trim() === '') {
        alert('A reason is required to reject a posting.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="${jlCsrfToken}">
        <input type="hidden" name="_method" value="PATCH">
    `;

  @endsection