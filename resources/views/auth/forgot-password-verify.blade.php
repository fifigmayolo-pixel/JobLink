@extends('layouts.app')

@section('title', 'Verify Code')

@section('content')
<div class="d-flex flex-column align-items-center" style="max-width: 420px; margin: 3rem auto 0;">
    <h1 class="display-font text-center mb-2" style="font-size: 1.85rem;">Enter Verification Code</h1>
    <p class="text-secondary text-center mb-4">Enter the verification code sent to your registered email.</p>

    <div class="jl-card p-4 w-100">
        <form method="POST" action="{{ route('password.forgot.verify.store') }}">
            @csrf
            <label for="code" class="form-label">Verification Code</label>
            <input type="text" name="code" id="code" inputmode="numeric" maxlength="6" class="form-control form-control-lg mb-3" autofocus>

            <button type="submit" class="btn btn-jl-gold btn-lg w-100 mb-3">Verify Code</button>
        </form>

        <form method="POST" action="{{ route('password.forgot.verify.resend') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-decoration-none">Resend Code</button>
        </form>
    </div>
</div>
@endsection