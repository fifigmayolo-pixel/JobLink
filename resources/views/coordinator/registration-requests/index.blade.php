@extends('layouts.app')

@section('title', 'Registration Requests')

@section('content')
@php
    $filterParams = array_filter(['search' => $search, 'type' => $type]);
    $isFiltering = $search !== '' || $type;
@endphp

<h1 class="h2 mb-1">Registration Requests</h1>
<p class="text-secondary mb-4">Students and alumni asking for an account. Check each one against your records before approving.</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
        <a href="{{ route('coordinator.registration-requests.index', ['status' => $key] + $filterParams) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
            {{ $label }}
            <span class="badge rounded-pill ms-1" style="background: {{ $status === $key ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('coordinator.registration-requests.index') }}" class="row g-2 mb-3">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="col-md-5">
        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search by Student ID, name or email">
    </div>
    <div class="col-md-3">
        <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">All types</option>
            <option value="student" @selected($type === 'student')>Student</option>
            <option value="alumni" @selected($type === 'alumni')>Alumni</option>
        </select>
    </div>
    <div class="col-auto d-flex gap-2">
        <button type="submit" class="btn btn-jl-navy">Filter</button>
        @if($isFiltering)
            <a href="{{ route('coordinator.registration-requests.index', ['status' => $status]) }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="jl-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="text-secondary small text-uppercase">
                    <th class="ps-4">Student ID</th>
                    <th>Full name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>{{ $status === 'pending' ? 'Requested' : 'Reviewed' }}</th>
                    <th class="text-end pe-4">{{ $status === 'pending' ? 'Actions' : 'Details' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                    @php $f = $flags[$r->id] ?? null; @endphp
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $r->student_id_number }}</td>
                        <td>
                            {{ $r->full_name }}
                            @if($f && ($f['id_taken'] || $f['same_name_ids'] || $f['email_in_use']))
                                <div class="mt-1 d-flex flex-column align-items-start gap-1">
                                    @if($f['id_taken'])
                                        <span class="badge bg-danger-subtle text-danger-emphasis text-start fw-normal">This Student ID already has an account</span>
                                    @endif
                                    @if($f['same_name_ids'])
                                        <span class="badge bg-warning-subtle text-warning-emphasis text-start fw-normal">Same name as ID {{ implode(', ', $f['same_name_ids']) }}</span>
                                    @endif
                                    @if($f['email_in_use'])
                                        <span class="badge bg-warning-subtle text-warning-emphasis text-start fw-normal">Email already on an existing profile</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-break">{{ $r->email }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $r->applicant_type === 'alumni' ? 'jl-badge-interview' : 'jl-badge-pending' }} px-3 py-2">{{ ucfirst($r->applicant_type) }}</span>
                        </td>
                        <td class="text-secondary small">
                            @if($status === 'pending')
                                {{ $r->created_at->format('M j, Y g:i A') }}<br>{{ $r->created_at->diffForHumans() }}
                            @else
                                {{ $r->reviewed_at?->format('M j, Y g:i A') }}
                            @endif
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            @if($status === 'pending')
                                <form method="POST" action="{{ route('coordinator.registration-requests.approve', $r) }}" class="d-inline js-approve-form" data-confirm="Approve {{ $r->full_name }} and create their account?">
                                    @csrf
                                    <button type="submit" class="btn btn-jl-gold btn-sm" @disabled($f && $f['id_taken'])>Approve</button>
                                </form>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal"
                                        data-action="{{ route('coordinator.registration-requests.reject', $r) }}"
                                        data-name="{{ $r->full_name }}">Reject</button>
                            @else
                                <span class="small text-secondary">
                                    {{ ucfirst($r->status) }} by {{ $r->reviewer?->full_name ?? $r->reviewer?->email ?? 'a coordinator' }}
                                    @if($r->status === 'rejected' && $r->rejection_reason)
                                        <br>Reason: {{ $r->rejection_reason }}
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            {{ $isFiltering ? "No {$status} requests match your search." : "No {$status} requests." }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $requests->links() }}
</div>

{{-- One shared reject dialog; the Reject buttons above fill in its form action and name. --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="h5 modal-title">Reject request</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Reject the request from <strong id="rejectName"></strong>? They'll be notified by email.</p>
                <label class="form-label" for="rejectReason">Reason (optional)</label>
                <textarea name="reason" id="rejectReason" rows="3" maxlength="500" class="form-control" placeholder="e.g. We couldn't find this Student ID in our records."></textarea>
                <div class="form-text">Included in the email so they know what to fix.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-jl-navy">Reject request</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-approve-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    const modal = document.getElementById('rejectModal');
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('rejectForm').action = btn.dataset.action;
        document.getElementById('rejectName').textContent = btn.dataset.name;
        document.getElementById('rejectReason').value = '';
    });
})();
</script>
@endpush
@endsection