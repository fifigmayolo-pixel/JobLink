@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h3 mb-1">Set a New Password</h1>
        <p class="text-secondary mb-4">You're using a temporary password. Choose a new one to continue.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('password.change.store') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <div class="position-relative">
                        <input type="password" name="password" id="newPassword" class="form-control pe-5" required autofocus>
                        <button type="button" class="jl-password-toggle" onclick="togglePassword('newPassword', this)" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm new password</label>
                    <div class="position-relative">
                        <input type="password" name="password_confirmation" id="confirmPassword" class="form-control pe-5" required>
                        <button type="button" class="jl-password-toggle" onclick="togglePassword('confirmPassword', this)" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-jl-gold w-100 btn-lg">Update Password</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').classList.toggle('bi-eye');
    btn.querySelector('i').classList.toggle('bi-eye-slash');
}
</script>
@endpush
@endsection