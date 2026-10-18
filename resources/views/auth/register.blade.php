@extends('layouts.app')

@section('title', 'Request an Account')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;">
            <h1 class="h3 mb-1">Request an Account</h1>
            <p class="text-secondary">For PSU Alaminos City students and alumni. The Job Coordinator will review your request, and you'll get an email once it's approved.</p>
        </div>

        @if(session('submitted_email'))
            <div class="jl-card p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill" style="font-size: 2.5rem; color: var(--jl-gold);"></i>
                </div>
                <h2 class="h5 mb-2">Request submitted</h2>
                <p class="text-secondary mb-3">We'll email <strong>{{ session('submitted_email') }}</strong> once the Job Coordinator has reviewed your request.</p>
                <a href="{{ route('login') }}" class="btn btn-jl-navy">Back to log in</a>
            </div>
        @else
            <div class="jl-card p-4">
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id_number" value="{{ old('student_id_number') }}" class="form-control @error('student_id_number') is-invalid @enderror" maxlength="50" required autofocus>
                        @error('student_id_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" maxlength="255" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" required>
                        <div class="form-text">Use an email you can open. Your approval notice will be sent here.</div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Applicant type</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="applicant_type" id="typeStudent" value="student" @checked(old('applicant_type', 'student') === 'student')>
                            <label class="form-check-label" for="typeStudent">Student</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="applicant_type" id="typeAlumni" value="alumni" @checked(old('applicant_type') === 'alumni')>
                            <label class="form-check-label" for="typeAlumni">Alumni</label>
                        </div>
                        @error('applicant_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                             </div>

                    <p class="form-text mb-3">
                        We use this information only to review your request and set up your JobLink account.
                        See our <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                    </p>

                    <button type="submit" class="btn btn-jl-gold w-100 btn-lg">Submit Request</button>
                </form>
            </div>

            <p class="text-center mt-3">
                <a href="{{ route('login') }}" class="small">&larr; Back to log in</a>
            </p>
        @endif
    </div>
</div>
@endsection