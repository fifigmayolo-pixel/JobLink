@extends('layouts.app')

@section('title', 'Terms & Conditions')

@section('content')
<div class="d-flex flex-column align-items-center" style="max-width: 640px; margin: 2rem auto 0;">
    <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;" >
    <h1 class="display-font text-center mb-2" style="font-size: 1.85rem;">Terms &amp; Conditions</h1>
    <p class="text-secondary text-center mb-4">Please review and accept before continuing.</p>

    <div class="jl-card p-4 w-100">
        <div style="max-height: 320px; overflow-y: auto;" class="mb-4 pe-2 text-secondary small">
            @include('partials.terms-content')
        </div>

        <form method="POST" action="{{ route('terms.accept') }}">
            @csrf
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="acceptTerms" name="accepted" value="1">
                <label class="form-check-label" for="acceptTerms">
                    I have read and agree to the Terms and Conditions
                </label>
            </div>
            <button type="submit" class="btn btn-jl-gold btn-lg w-100" id="continueBtn" disabled>Continue</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('acceptTerms');
    const button = document.getElementById('continueBtn');

    checkbox.addEventListener('change', function () {
        button.disabled = !checkbox.checked;
    });
});
</script>
@endpush

@endsection