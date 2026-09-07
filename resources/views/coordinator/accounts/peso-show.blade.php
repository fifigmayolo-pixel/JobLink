@extends('layouts.app')

@section('title', $user->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <a href="{{ route('coordinator.accounts.peso.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to PESO accounts</a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h4 mb-1">{{ $user->full_name }}</h1>
                    <p class="text-secondary mb-0">{{ $user->email }}</p>
                </div>
                <form method="POST" action="{{ route('coordinator.accounts.temp-password', $user) }}" onsubmit="return confirm('Generate a new temporary password?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Temp Password</button>
                </form>
            </div>

            @if(session('tempPassword'))
                <div class="alert alert-light jl-card mb-4" style="border-left: 4px solid var(--jl-gold) !important;">
                    Temporary password — share this with them directly: <strong>{{ session('tempPassword') }}</strong>
                </div>
            @endif

            <dl class="row mb-0">
                <dt class="col-sm-4">Role</dt>
                <dd class="col-sm-8">PESO</dd>
                <dt class="col-sm-4">Activated</dt>
                <dd class="col-sm-8">{{ $user->activated_at?->format('M j, Y') ?? 'Not yet activated' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection