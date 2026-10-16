@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;">
            <h1 class="h3 mb-1">Forgot Your Password?</h1>
            <p class="text-secondary">Enter your Student ID or email and we'll send a verification code to your registered email.</p>
        </div>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('password.forgot.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Student ID or email</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" class="form-control" required autofocus>
                </div>
                <button type="submit" class="btn btn-jl-gold w-100 btn-lg">Send Verification Code</button>
            </form>
        </div>

        <p class="text-center mt-3">
            <a href="{{ route('login') }}" class="small">&larr; Back to log in</a>
        </p>
    </div>
</div>
@endsection