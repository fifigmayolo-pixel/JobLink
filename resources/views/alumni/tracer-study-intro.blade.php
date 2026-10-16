@extends('layouts.app')

@section('title', 'Tracer Study')

@section('content')
@php
    $continueUrl = $profileComplete ? route('dashboard') : route('profile.complete');
    $continueLabel = $profileComplete ? 'Continue to Dashboard' : 'Continue to Complete Profile';
    $canContinue = ! $link || $accessed;
@endphp

<div class="row justify-content-center">
    <div class="col-lg-7 text-center">
                <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;">
        <h1 class="h2 mb-2">Welcome back, alumnus.</h1>

        @if($link)
            <p class="text-secondary mb-4">
                PSU Alaminos City asks its alumni to take part in the Tracer Study. It helps the
                university understand where its graduates land. Open it with the button below,
                answer the questions on the external site, then come back here to continue.
            </p>
        @else
            <p class="text-secondary mb-4">There is no Tracer Study open right now. You can continue.</p>
        @endif

        <div class="jl-card p-4 p-md-5">
            @if($link && $accessed)
                <div class="alert alert-light jl-card mb-4 text-start" style="border-left: 4px solid var(--jl-gold) !important;" role="status">
                    You've opened the current Tracer Study. You can continue.
                </div>
            @endif

            <div class="d-grid gap-3 col-md-8 mx-auto">
                @if($link)
                    <form method="POST" action="{{ route('tracer-study.access') }}" target="_blank" id="tracerForm">
                        @csrf
                        <button type="submit" class="btn btn-jl-gold btn-lg w-100">Go to Tracer Study</button>
                    </form>
                @endif

                <a href="{{ $continueUrl }}" id="continueBtn"
                   class="btn btn-jl-navy btn-lg {{ $canContinue ? '' : 'disabled' }}"
                   @unless($canContinue) aria-disabled="true" tabindex="-1" @endunless>
                    {{ $continueLabel }}
                </a>
            </div>

            @unless($canContinue)
                <p id="continueHint" class="text-secondary small mt-3 mb-0">
                    Open the Tracer Study first. It opens in a new tab, and Continue unlocks once you do.
                </p>
            @endunless

            @if($link)
                <p class="text-secondary small mt-3 mb-0">
                    The Tracer Study is hosted on an external site, so JobLink can't confirm that you
                    submitted it. It only records that you opened it.
                </p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('tracerForm');
    const cont = document.getElementById('continueBtn');
    const hint = document.getElementById('continueHint');

    if (!form || !cont) return;

    // The new tab does the recording; give it a moment, then unlock Continue here.
    form.addEventListener('submit', function () {
        setTimeout(function () {
            cont.classList.remove('disabled');
            cont.removeAttribute('aria-disabled');
            cont.removeAttribute('tabindex');
            if (hint) hint.classList.add('d-none');
        }, 600);
    });
});
</script>
@endpush
@endsection