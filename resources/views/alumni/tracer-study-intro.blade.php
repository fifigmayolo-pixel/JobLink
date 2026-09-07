@extends('layouts.app')

@section('title', 'Tracer Study')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 text-center">
        <span class="jl-seal d-inline-flex mb-3" style="width: 52px; height: 52px; font-size: 1.1rem; border-color: var(--jl-navy); color: var(--jl-navy);" aria-hidden="true">JL</span>
        <h1 class="h2 mb-2">Welcome back, alumnus.</h1>
        <p class="text-secondary mb-4">
            Before you set up your profile, PSU Alaminos City invites you to complete the Tracer
            Study — it helps the university understand where its graduates land. It's optional,
            and hosted on an external site, so we can't confirm completion here.
        </p>

        <div class="jl-card p-4 p-md-5">
            <div class="d-grid gap-3 col-md-8 mx-auto">
                @if($link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener" class="btn btn-jl-gold btn-lg">
                        Open Tracer Study
                    </a>
                @endif
                <a href="{{ route('profile.complete') }}" class="btn btn-jl-navy btn-lg">
                    Proceed to Profile
                </a>
            </div>
        </div>
    </div>
</div>
@endsection