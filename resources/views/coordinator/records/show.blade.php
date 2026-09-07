@extends('layouts.app')

@section('title', $profile->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="{{ route('coordinator.records.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to records
        </a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <div class="d-flex align-items-center gap-3">
                    @include('partials.profile-avatar', ['profile' => $profile, 'size' => 72])
                    <div>
                        <h1 class="h4 mb-1">{{ $profile->full_name }}</h1>
                        <p class="text-secondary mb-0">{{ $profile->user->student_id_number }} &middot; {{ ucfirst($profile->status) }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('coordinator.accounts.temp-password', $profile->user) }}" onsubmit="return confirm('Generate a new temporary password?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Temp Password</button>
                </form>
            </div>

            <dl class="row mb-4">
                <dt class="col-sm-4">Course / Program</dt>
                <dd class="col-sm-8">{{ $profile->course_program }}</dd>
                <dt class="col-sm-4">{{ $profile->status === 'student' ? 'Year Level & Section' : 'Year Graduated' }}</dt>
                <dd class="col-sm-8">{{ $profile->status === 'student' ? $profile->year_level.' - '.$profile->section : $profile->year_graduated }}</dd>
                <dt class="col-sm-4">Personal Email</dt>
                <dd class="col-sm-8">{{ $profile->personal_email }}</dd>
                <dt class="col-sm-4">Mobile Number</dt>
                <dd class="col-sm-8">{{ $profile->mobile_number }}</dd>
            </dl>

            <hr class="jl-rule">

            <h2 id="edit-record" class="h6 text-uppercase text-secondary mb-3">Correct a Locked Field</h2>
            <form method="POST" action="{{ route('coordinator.records.override', $profile) }}" class="row g-2 mb-5">
                @csrf
                @method('PATCH')
                <div class="col-md-3">
                    <select name="field_name" id="fieldName" class="form-select" required>
                        <option value="">Field&hellip;</option>
                        <option value="full_name">Full name</option>
                        <option value="course_program">Course/Program</option>
                        <option value="year_level">Year level</option>
                        @if($profile->status === 'student')
                            <option value="section">Section</option>
                        @endif
                        <option value="year_graduated">Year graduated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="new_value" id="newValueText" class="form-control" placeholder="New value" required>
                    <select name="new_value" id="newValueSelect" class="form-select d-none" disabled>
                        <option value="">Select&hellip;</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="reason" class="form-control" placeholder="Reason for correction" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-jl-gold w-100">Save</button>
                </div>
            </form>

            <form method="POST" action="{{ route('coordinator.records.destroy', $profile) }}" onsubmit="return confirm('Remove this record? This can be restored later if needed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Remove Record</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldSelect = document.getElementById('fieldName');
    const textInput = document.getElementById('newValueText');
    const valueSelect = document.getElementById('newValueSelect');

    const choices = {
        course_program: @json(\App\Models\StudentAlumniProfile::PROGRAMS),
        year_level: @json(\App\Models\StudentAlumniProfile::YEAR_LEVELS),
        year_graduated: @json(\App\Models\StudentAlumniProfile::academicYearRanges()),
        section: @json(\App\Models\StudentAlumniProfile::SECTIONS),
    };

    fieldSelect.addEventListener('change', function () {
        const options = choices[this.value];

        textInput.value = '';
        valueSelect.innerHTML = '<option value="">Select&hellip;</option>';

        if (options) {
            options.forEach(function (opt) {
                const el = document.createElement('option');
                el.value = opt;
                el.textContent = opt;
                valueSelect.appendChild(el);
            });
            textInput.classList.add('d-none');
            textInput.disabled = true;
            valueSelect.classList.remove('d-none');
            valueSelect.disabled = false;
        } else {
            valueSelect.classList.add('d-none');
            valueSelect.disabled = true;
            textInput.classList.remove('d-none');
            textInput.disabled = false;
        }
    });
});
</script>
@endpush
@endsection