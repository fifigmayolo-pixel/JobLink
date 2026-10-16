@extends('layouts.app')

@section('title', 'Create Coordinator Account')

@section('content')
<a href="{{ route('coordinator.accounts.index') }}"
   class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mb-3">
    <i class="bi bi-arrow-left"></i>
    Back to Accounts
</a>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h2 mb-1">Create Coordinator Account</h1>
        <p class="text-secondary mb-4">Another Job Coordinator gets full access to JobLink. A temporary password is emailed to them, and they choose their own when they log in.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.accounts.coordinator.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control" maxlength="255" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Email (their login)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" maxlength="255" required>
                    <div class="form-text">Double-check this address. The temporary password is sent here, and password-reset codes will go here.</div>
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Create Coordinator Account</button>
            </form>

            @if(session('tempPassword'))
                <div class="alert alert-light jl-card mt-4" style="border-left: 4px solid var(--jl-gold) !important;">
                    Temporary password (also emailed, shown here in case it doesn't arrive): <strong>{{ session('tempPassword') }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection