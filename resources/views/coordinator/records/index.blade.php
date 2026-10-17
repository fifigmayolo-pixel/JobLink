@extends('layouts.app')

@section('title', 'Student & Alumni Records')

@section('content')
<h1 class="h2 mb-1">Student &amp; Alumni Records</h1>
<p class="text-secondary mb-4">View records, correct locked fields, and manage accounts.</p>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or Student ID">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All</option>
            <option value="student" @selected(request('status') === 'student')>Students</option>
            <option value="alumni" @selected(request('status') === 'alumni')>Alumni</option>
        </select>
    </div>
    <div class="col-md-3">
        <select name="program" class="form-select">
            <option value="">All Programs</option>
            @foreach(\App\Models\StudentAlumniProfile::PROGRAMS as $program)
                <option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="year_graduated" class="form-select">
            <option value="">All Grad Years</option>
            @foreach(\App\Models\StudentAlumniProfile::academicYearRanges() as $range)
                <option value="{{ $range }}" @selected(request('year_graduated') === $range)>{{ $range }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-jl-navy w-100">Filter</button>
    </div>
</form>

<hr class="jl-rule">

@php
    $hasStudents = $profiles->where('status', 'student')->isNotEmpty();
    $hasAlumni = $profiles->where('status', 'alumni')->isNotEmpty();
@endphp

<form method="POST" action="{{ route('coordinator.bulk-update.store') }}" id="bulkUpdateForm">
    @csrf

    @if($hasStudents || $hasAlumni)
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="selectAll">
            <label class="form-check-label small text-secondary" for="selectAll">Select all records on this page</label>
        </div>
    @endif

    @forelse($profiles as $profile)
        <div class="jl-card p-4 mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input bulk-checkbox" type="checkbox" name="profile_ids[]" value="{{ $profile->id }}" data-status="{{ $profile->status }}">
                </div>
                @include('partials.profile-avatar', ['imagePath' => $profile->profile_picture, 'label' => $profile->full_name, 'size' => 48])
                <div>
                    <h2 class="h6 mb-1">
                        <a href="{{ route('coordinator.records.show', $profile) }}" class="text-decoration-none">{{ $profile->full_name }}</a>
                    </h2>
                    <p class="text-secondary small mb-0">
                        {{ $profile->user->student_id_number }} &middot; {{ $profile->course_program }} &middot; {{ ucfirst($profile->status) }}
                    </p>
                </div>
            </div>
            <div class="text-end">
                <div class="d-flex gap-2 mb-2 justify-content-end flex-wrap">
                    <a href="{{ route('coordinator.records.show', $profile) }}" class="btn btn-outline-secondary btn-sm">View</a>
                    <a href="{{ route('coordinator.records.show', $profile) }}#edit-record" class="btn btn-outline-secondary btn-sm">Edit</a>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRecord('{{ route('coordinator.records.destroy', $profile) }}')">Delete</button>
                </div>
                @if($profile->status === 'alumni')
                    <span class="badge rounded-pill" style="background: rgba(15,42,92,0.08); color: var(--jl-navy);">
                        {{ $profile->year_graduated ?? '—' }}
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="jl-card p-5 text-center">
            <p class="mb-0 text-secondary">No records match your search.</p>
        </div>
    @endforelse

    @if($hasStudents)
        <div class="jl-card p-4 mt-4" style="border: 2px solid var(--jl-gold);">
            <h2 class="h6 mb-3">Convert Selected to Alumni</h2>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small">Year graduated</label>
                    <select name="year_graduated" class="form-select" style="width: 180px;">
                        <option value="" disabled selected>Select year graduated…</option>
                        @foreach(\App\Models\StudentAlumniProfile::academicYearRanges() as $range)
                            <option value="{{ $range }}">{{ $range }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" name="direction" value="to_alumni" class="btn btn-jl-gold" id="convertToAlumniBtn" form="bulkUpdateForm" disabled>
                        Convert Selected (<span id="toAlumniCount">0</span>)
                    </button>
                </div>
            </div>
            <p class="text-secondary small mb-0 mt-2">
                Only checked students on this page are included — for a large batch, narrow the search above first. Conversion runs in the background.
            </p>
        </div>
    @endif

    @if($hasAlumni)
        <div class="jl-card p-4 mt-4" style="border: 2px solid var(--jl-navy);">
            <h2 class="h6 mb-1">Convert Selected to Student</h2>
            <p class="text-secondary small mb-3">Use this to undo an accidental conversion to Alumni.</p>
            <button type="submit" name="direction" value="to_student" class="btn btn-jl-navy" id="convertToStudentBtn" form="bulkUpdateForm" disabled>
                Convert Selected (<span id="toStudentCount">0</span>)
            </button>
            <p class="text-secondary small mb-0 mt-2">
                Only checked alumni on this page are included. Section isn't restored automatically — correct it on the record's page afterward if needed. Conversion runs in the background.
            </p>
        </div>
    @endif
</form>

<div class="mt-3">{{ $profiles->onEachSide(1)->links() }}</div>

@push('scripts')
<script>
const jlCsrfToken = '{{ csrf_token() }}';

function deleteRecord(url) {
    const reason = prompt('Reason for removing this record (required):');
    if (reason === null) {
        return;
    }
    if (reason.trim() === '') {
        alert('A reason is required to remove this record.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="${jlCsrfToken}">
        <input type="hidden" name="_method" value="DELETE">
    `;

    const reasonInput = document.createElement('input');
    reasonInput.type = 'hidden';
    reasonInput.name = 'reason';
    reasonInput.value = reason;
    form.appendChild(reasonInput);

    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const toAlumniBtn = document.getElementById('convertToAlumniBtn');
    const toAlumniCount = document.getElementById('toAlumniCount');
    const toStudentBtn = document.getElementById('convertToStudentBtn');
    const toStudentCount = document.getElementById('toStudentCount');

    function updateCounts() {
        const studentChecked = document.querySelectorAll('.bulk-checkbox[data-status="student"]:checked').length;
        const alumniChecked = document.querySelectorAll('.bulk-checkbox[data-status="alumni"]:checked').length;

        if (toAlumniCount) toAlumniCount.textContent = studentChecked;
        if (toAlumniBtn) toAlumniBtn.disabled = studentChecked === 0;

        if (toStudentCount) toStudentCount.textContent = alumniChecked;
        if (toStudentBtn) toStudentBtn.disabled = alumniChecked === 0;
    }

    document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.addEventListener('change', updateCounts));

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = selectAll.checked);
            updateCounts();
        });
    }

    updateCounts();
});
</script>
@endpush
@endsection