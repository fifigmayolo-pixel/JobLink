@extends('layouts.app')

@section('title', 'What Companies See')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <a href="{{ route('profile.edit') }}#searchable" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Profile settings
        </a>

        <h1 class="h2 mt-3 mb-1">What companies see</h1>
        <p class="text-secondary mb-3">
            This is your CV exactly as a partner company sees it in search. Your email, mobile number, street address,
            date of birth, gender, civil status and profile picture are not part of it.
        </p>

        @if($visibleNow)
            <div class="alert alert-light jl-card small" style="border-left: 4px solid var(--jl-navy) !important;" role="status">
                <i class="bi bi-eye me-1" aria-hidden="true"></i> <strong>Your profile is searchable.</strong> Companies can find this.
            </div>
        @elseif($switchOn)
            <div class="alert alert-light jl-card small" style="border-left: 4px solid var(--jl-gold) !important;" role="status">
                <strong>The switch is on, but you are not visible yet.</strong>
                You will appear once your CV is ready. Still needed: {{ implode(' and ', $missing) }}.
                <a href="{{ route('cv.edit') }}">Open My CV</a>
            </div>
        @else
            <div class="alert alert-light jl-card small" style="border-left: 4px solid var(--jl-slate) !important;" role="status">
                <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> <strong>You are not searchable.</strong>
                Companies cannot find this unless you turn the switch on in Profile settings. A company you apply to gets a copy of your CV.
            </div>
        @endif

        <hr class="jl-rule">

        <div class="jl-card p-4 p-md-5">
            @include('cv._document', ['cv' => $cv])
        </div>
    </div>
</div>
@endsection
