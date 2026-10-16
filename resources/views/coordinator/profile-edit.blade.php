@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
@php
    $currentEmail = auth()->user()->email;
    // After a validation error, keep the extra fields open if the email was being changed.
    $showEmailFields = old('email') !== null
        && strtolower(trim(old('email'))) !== strtolower($currentEmail);
@endphp

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h2 mb-1">Edit Profile</h1>
        <p class="text-secondary mb-4">Update your name, photo and login email.</p>
        <hr class="jl-rule">

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="d-flex align-items-center gap-3 mb-4">
                    @include('partials.profile-avatar', ['imagePath' => auth()->user()->profile_picture, 'label' => auth()->user()->full_name, 'size' => 88])
                    <div>
                        <label class="form-label mb-1 d-block">Profile picture</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        <div class="form-text">Leave blank to keep your current photo. JPG or PNG, up to 2MB.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', auth()->user()->full_name) }}" class="form-control" maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email (your login)</label>
                    <input type="email" name="email" id="emailInput" value="{{ old('email', $currentEmail) }}" class="form-control" maxlength="255" required>
                    <div class="form-text">Changing this changes how you log in and where password-reset codes are sent. Make sure you can open this inbox.</div>
                </div>

                <div id="emailChangeFields" class="{{ $showEmailFields ? '' : 'd-none' }}">
                    <div class="mb-3">
                        <label class="form-label">Confirm new email</label>
                        <div class="position-relative">
                            <input type="email" name="email_confirmation" id="emailConfirmInput" value="{{ old('email_confirmation') }}" class="form-control pe-5" maxlength="255" autocomplete="off">
                            <button type="button" class="jl-password-toggle" onclick="toggleField('emailConfirmInput', this, 'email')" aria-label="Hide email">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="currentPasswordInput" class="form-control pe-5" autocomplete="current-password">
                            <button type="button" class="jl-password-toggle" onclick="toggleField('currentPasswordInput', this, 'text')" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Required to change your email.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-jl-gold btn-lg mt-2">Save Changes</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Flips a field between masked (type="password") and readable (shownType).
// bi-eye = "click to show", bi-eye-slash = "click to hide", same as the login page.
function toggleField(fieldId, btn, shownType) {
    const field = document.getElementById(fieldId);
    const wasShowing = field.type === shownType;

    field.type = wasShowing ? 'password' : shownType;

    const icon = btn.querySelector('i');
    icon.classList.toggle('bi-eye', wasShowing);
    icon.classList.toggle('bi-eye-slash', !wasShowing);
    btn.setAttribute('aria-label', wasShowing ? 'Show' : 'Hide');
}

(function () {
    const original = @json(strtolower($currentEmail));
    const email = document.getElementById('emailInput');
    const box = document.getElementById('emailChangeFields');
    const confirmInput = document.getElementById('emailConfirmInput');
    const passwordInput = document.getElementById('currentPasswordInput');

    function sync() {
        const changed = email.value.trim().toLowerCase() !== original;
        box.classList.toggle('d-none', !changed);
        confirmInput.required = changed;
        passwordInput.required = changed;
    }

    email.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
@endsection