@extends('layouts.app')

@section('title', 'Edit Job')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 mb-1">Edit Job</h1>
        <p class="text-secondary mb-4">{{ $jobPosting->company->company_name }}</p>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('jobs.update', $jobPosting) }}">
            @csrf
            @method('PATCH')

                        <div class="mb-3">
                <label class="form-label">Job title</label>
                <input type="text" name="title" value="{{ old('title', $jobPosting->title) }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Job type</label>
                <select name="job_type" class="form-select" required>
                    <option value="">Select job type&hellip;</option>
                    <option value="full_time" @selected(old('job_type', $jobPosting->job_type) === 'full_time')>Full-time</option>
                    <option value="part_time" @selected(old('job_type', $jobPosting->job_type) === 'part_time')>Part-time</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="open" @selected(old('status', $jobPosting->status) === 'open')>Open</option>
                    <option value="closed" @selected(old('status', $jobPosting->status) === 'closed')>Closed</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" value="{{ old('location', $jobPosting->location) }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Application deadline</label>
                <input type="date" name="application_deadline" value="{{ old('application_deadline', $jobPosting->application_deadline->format('Y-m-d')) }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-control" required>{{ old('description', $jobPosting->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Qualifications</label>
                <textarea name="qualifications" rows="3" class="form-control" required>{{ old('qualifications', $jobPosting->qualifications) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" rows="3" class="form-control" required>{{ old('requirements', $jobPosting->requirements) }}</textarea>
            </div>

                        <div class="d-flex gap-2">
                <button type="submit" class="btn btn-jl-gold btn-lg">Save Changes</button>
                <button type="button" class="btn btn-outline-danger btn-lg" onclick="deleteJobPosting('{{ route('jobs.destroy', $jobPosting) }}')">Remove</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function deleteJobPosting(url) {
    if (!confirm('Remove this job posting?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
@endsection