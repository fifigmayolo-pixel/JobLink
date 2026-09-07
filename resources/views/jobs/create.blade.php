@extends('layouts.app')

@section('title', 'Post a Job')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 mb-1">Post a Job</h1>
        <p class="text-secondary mb-4">Fill in the details below. Once posted, students and alumni can browse and apply.</p>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('jobs.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Job title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control" required>
            </div>

            @if($companies)
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select" required>
                        <option value="">Select a company&hellip;</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->company_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" value="{{ old('location') }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Application deadline</label>
                <input type="date" name="application_deadline" value="{{ old('application_deadline') }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-control" required>{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Qualifications</label>
                <textarea name="qualifications" rows="3" class="form-control" required>{{ old('qualifications') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" rows="3" class="form-control" required>{{ old('requirements') }}</textarea>
            </div>

            <button type="submit" class="btn btn-jl-gold btn-lg">Post Job</button>
        </form>
    </div>
</div>
@endsection