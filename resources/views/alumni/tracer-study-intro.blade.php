@extends('layouts.app')

@section('title', 'Tracer Study')

@push('styles')
<style>
    .jl-step { display: flex; gap: 1rem; align-items: flex-start; }

    .jl-step-num {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Fraunces', serif;
        font-weight: 700;
        background: var(--jl-navy);
        color: #fff;
    }
    .jl-step-check { display: none; }
    .jl-step.is-done .jl-step-num { background: var(--jl-gold); color: var(--jl-navy-deep); }
    .jl-step.is-done .jl-step-check { display: inline; }
    .jl-step.is-done .jl-step-digit { display: none; }

    /* Locked step: clearly greyed out instead of washed-out white */
    .jl-step.is-locked .jl-step-num { background: #E5E8EE; color: #8A91A0; }
    .jl-step.is-locked h2 { color: var(--jl-slate); }
    .jl-step.is-locked .btn-jl-navy {
        background: #E5E8EE;
        border-color: #E5E8EE;
        color: #8A91A0;
        opacity: 1;
    }
</style>
@endpush

@section('content')
@php
    $continueUrl = $profileComplete ? route('dashboard') : route('profile.complete');
    $continueLabel = $profileComplete ? 'Continue to Dashboard' : 'Continue to Complete Profile';
    $canContinue = ! $link || $accessed;
@endphp

<div style="width: 100%; max-width: 640px; margin: 0 auto;">

    <div class="text-center mb-4">
        <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 96px; height: 96px;">
        <h1 class="h2 mb-2">Welcome back, alumnus.</h1>

        @if($link)
            <p class="text-secondary mb-0">
                PSU Alaminos City asks its alumni to take part in the Tracer Study.
                It helps the university understand where its graduates land.
            </p>
        @else
            <p class="text-secondary mb-0">There is no Tracer Study open right now. You can continue.</p>
        @endif
    </div>

    <div class="jl-card p-4 p-md-5">
        @if($link)
            {{-- Step 1 --}}
            <div class="jl-step {{ $accessed ? 'is-done' : '' }}" id="stepOne">
                <span class="jl-step-num">
                    <span class="jl-step-digit">1</span>
                    <i class="bi bi-check-lg jl-step-check"></i>
                </span>
                <div class="flex-grow-1">
                    <h2 class="h6 mb-1">Open the Tracer Study</h2>
                    <p class="text-secondary small mb-3">
                        It opens in a new tab. Answer the questions there, then come back to this page.
                    </p>
                    <p id="openedNote" class="small mb-3 {{ $accessed ? '' : 'd-none' }}" style="color: #8A6C16;" aria-live="polite">
                        <i class="bi bi-check-circle me-1"></i>You have opened the current Tracer Study.
                    </p>
                    <form method="POST" action="{{ route('tracer-study.access') }}" target="_blank" id="tracerForm" class="d-grid d-sm-block">
                        @csrf
                        <button type="submit" id="openBtn"
                                class="btn px-4 {{ $accessed ? 'btn-outline-secondary' : 'btn-jl-gold' }}">
                            {{ $accessed ? 'Open again' : 'Go to Tracer Study' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="jl-step mt-4 {{ $canContinue ? '' : 'is-locked' }}" id="stepTwo">
                <span class="jl-step-num"><span>2</span></span>
                <div class="flex-grow-1">
                    <h2 class="h6 mb-1">Continue to JobLink</h2>
                    <p id="continueHint" class="text-secondary small mb-3" aria-live="polite">
                        {{ $canContinue ? 'You can continue now.' : 'This unlocks once you have opened the Tracer Study.' }}
                    </p>
                    <div class="d-grid d-sm-block">
                        <a href="{{ $continueUrl }}" id="continueBtn"
                           class="btn btn-jl-navy px-4 {{ $canContinue ? '' : 'disabled' }}"
                           @unless($canContinue) aria-disabled="true" tabindex="-1" @endunless>
                            {{ $continueLabel }}
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- No Tracer Study open: nothing to do first --}}
            <div class="d-grid d-sm-block text-sm-center">
                <a href="{{ $continueUrl }}" id="continueBtn" class="btn btn-jl-navy btn-lg px-5">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>

    @if($link)
        <p class="text-secondary small text-center mt-3 mb-0">
            The Tracer Study is hosted on an external site, so JobLink can't confirm that you
            submitted it. It only records that you opened it.
        </p>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('tracerForm');
    const cont = document.getElementById('continueBtn');

    if (!form || !cont) return;

    const stepOne = document.getElementById('stepOne');
    const stepTwo = document.getElementById('stepTwo');
    const openBtn = document.getElementById('openBtn');
    const openedNote = document.getElementById('openedNote');
    const hint = document.getElementById('continueHint');

    // The new tab does the recording; give it a moment, then unlock Continue here.
    form.addEventListener('submit', function () {
        setTimeout(function () {
            stepOne.classList.add('is-done');
            stepTwo.classList.remove('is-locked');
            openedNote.classList.remove('d-none');

            openBtn.textContent = 'Open again';
            openBtn.classList.remove('btn-jl-gold');
            openBtn.classList.add('btn-outline-secondary');

            hint.textContent = 'You can continue now.';
            cont.classList.remove('disabled');
            cont.removeAttribute('aria-disabled');
            cont.removeAttribute('tabindex');
        }, 600);
    });
});
</script>
@endpush
@endsection