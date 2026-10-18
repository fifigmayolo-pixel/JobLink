@extends('layouts.app')

@section('title', 'Activate Your Account')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;">
            <h1 class="h3 mb-1">Activate Your Account</h1>
            <p class="text-secondary">For students and alumni logging in for the first time.</p>
        </div>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('activate.verify.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id_number" value="{{ old('student_id_number') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control" required>
                    <div class="form-text">Exactly as it's on file with your Job Coordinator.</div>
                </div>
                <button type="submit" class="btn btn-jl-gold w-100 btn-lg">Continue</button>
            </form>
        </div>

       <p class="text-center mt-4">
    <a href="{{ route('login') }}" class="text-decoration-none d-inline-flex align-items-center gap-2" style="font-size: 1.1rem;">
        <i class="bi bi-arrow-left"></i> Back to login
    </a>
</p>
    </div>
</div>
@endsection