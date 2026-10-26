@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')

<h1 class="h2 mb-1">Audit Log</h1>
<p class="text-secondary mb-4">
    Account creations, roster imports, field corrections, record deletions, and job posting changes.
</p>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="action" class="form-select">
            <option value="" @selected(request('action', '') === '')>All Actions</option>
            @foreach(collect($actionLabels)->except('peso_account_created') as $value => $label)
                <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="date_range" id="dateRangeSelect" class="form-select">
            <option value="" @selected(request('date_range', '') === '')>All Dates</option>
            <option value="today" @selected(request('date_range') === 'today')>Today</option>
            <option value="last_7_days" @selected(request('date_range') === 'last_7_days')>Last 7 Days</option>
            <option value="last_30_days" @selected(request('date_range') === 'last_30_days')>Last 30 Days</option>
            <option value="last_100_days" @selected(request('date_range') === 'last_100_days')>Last 100 Days</option>
            <option value="this_month" @selected(request('date_range') === 'this_month')>This Month</option>
            <option value="custom" @selected(request('date_range') === 'custom')>Custom Range</option>
        </select>
    </div>
    <div class="col-md-2 {{ request('date_range') === 'custom' ? '' : 'd-none' }}" id="dateFromWrapper">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="From">
    </div>
    <div class="col-md-2 {{ request('date_range') === 'custom' ? '' : 'd-none' }}" id="dateToWrapper">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="To">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-jl-navy w-100">Filter</button>
    </div>
</form>

@can('exportAuditLog', \App\Models\User::class)
    <div class="mb-4">
        <a href="{{ route('coordinator.audit-log.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download"></i> Export
        </a>
    </div>
@endcan

<hr class="jl-rule">

@forelse($logs as $log)
    <div class="jl-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <span class="badge rounded-pill" style="background: rgba(15,42,92,0.08); color: var(--jl-navy);">
                {{ $log->actionLabel() }}
            </span>
            <span class="text-secondary small">{{ $log->created_at->format('M j, Y g:i A') }}</span>
        </div>

        @if($log->concernsJobPosting())
            <p class="mb-1"><strong>Job posting:</strong> {{ $log->jobPosting?->title ?? 'Deleted posting' }}</p>
        @elseif($log->action !== 'roster_import')
            <p class="mb-1">
                <strong>{{ in_array($log->action, ['company_account_created', 'peso_account_created']) ? 'Account' : 'Record' }}:</strong>
                {{ $log->user?->full_name ?? $log->user?->email ?? 'Deleted user' }}
                @if($log->user?->student_id_number) ({{ $log->user->student_id_number }}) @endif
            </p>
        @endif

        @if($log->field_name)
            <p class="mb-1"><strong>Field:</strong> {{ $log->field_name }}</p>
            {{-- text-break: a long unbroken value (a URL, typically) otherwise
                 never wraps and pushes the card — and the whole page — wider,
                 forcing a horizontal scrollbar instead of staying inside the card. --}}
            <p class="mb-1 text-break"><strong>Old value:</strong> {{ $log->old_value ?? '—' }}</p>
            <p class="mb-1 text-break"><strong>New value:</strong> {{ $log->new_value ?? '—' }}</p>
        @endif

        @if($log->reason)
            <p class="mb-1 text-break"><strong>Reason:</strong> {{ $log->reason }}</p>
        @endif

        @if($log->details)
            <p class="mb-1 text-break"><strong>Details:</strong> {{ $log->details }}</p>
        @endif

        <p class="text-secondary small mb-0 mt-2">By {{ $log->editor?->full_name ?? $log->editor?->email ?? 'Unknown' }}</p>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">No audit log entries yet.</p>
    </div>
@endforelse

{{ $logs->links() }}

@push('scripts')
<script>
document.getElementById('dateRangeSelect').addEventListener('change', function () {
    const isCustom = this.value === 'custom';
    document.getElementById('dateFromWrapper').classList.toggle('d-none', !isCustom);
    document.getElementById('dateToWrapper').classList.toggle('d-none', !isCustom);
});
</script>
@endpush

@endsection