@extends('layouts.app')

@section('title', 'Log In')

@push('styles')
<style>
    .jl-login-breakout { margin: -1.75rem; }
    .jl-login-split { display: flex; flex-wrap: wrap; min-height: 100vh; }

    .jl-login-left {
        flex: 0 0 28%;
        min-width: 0;
        background-image: linear-gradient(160deg, rgba(15,42,92,0.92) 0%, rgba(8,27,61,0.94) 100%), url('{{ asset("images/campus-photo.jpg") }}');
        background-size: cover, cover;
        background-position: center, center;
        background-repeat: no-repeat, no-repeat;
        color: #fff;
        padding: 2.5rem 2rem;
        display: flex;
        flex-direction: column;
    }
    .jl-login-brand { display: flex; align-items: center; gap: 0.6rem; font-family: 'Fraunces', serif; font-weight: 600; font-size: 1.25rem; }
    .jl-login-headline { font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.85rem; line-height: 1.28; color: #fff; margin: 2rem 0 0.85rem; }
    .jl-login-headline em { font-style: normal; color: var(--jl-gold-soft); }
    .jl-login-sub { color: rgba(255,255,255,0.75); font-size: 1.02rem; margin-bottom: 2rem; }
    .jl-login-features { display: flex; flex-direction: column; gap: 1.2rem; margin-top: auto; }
    .jl-login-feature { display: flex; gap: 0.75rem; align-items: flex-start; }
    .jl-login-feature-icon {
        width: 38px; height: 38px; border-radius: 0.5rem; flex-shrink: 0;
        background: rgba(201,162,39,0.15); color: var(--jl-gold-soft);
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .jl-login-feature h3 { color: #fff; font-size: 0.95rem; font-weight: 600; margin-bottom: 0.15rem; font-family: 'Inter', sans-serif; }
    .jl-login-feature p { color: rgba(255,255,255,0.65); font-size: 0.85rem; margin: 0; }
    .jl-login-copyright { color: rgba(255,255,255,0.4); font-size: 0.78rem; margin-top: 2rem; margin-bottom: 0; }
    .jl-login-copyright a { color: rgba(255,255,255,0.65); text-decoration: underline; white-space: nowrap; }
    .jl-login-copyright a:hover { color: #fff; }

    .jl-login-right {
        flex: 1 1 0;
        min-width: 0;
        background: linear-gradient(180deg, rgba(201,162,39,0.10) 0%, #fff 55%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 2rem;
    }
    .jl-login-icon-badge {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        border: 2px solid var(--jl-navy);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2.4rem;
        color: var(--jl-navy);
    }
    .jl-login-form-wrap { width: 100%; max-width: 440px; }
    .jl-login-form-wrap .form-label { font-size: 1rem; }
    .jl-login-form-wrap .form-control-lg { font-size: 1.05rem; }

    @media (max-width: 991.98px) {
        .jl-login-split { min-height: auto; }
        .jl-login-left { flex: 1 1 100%; padding: 2.5rem 1.75rem; }
        .jl-login-right { flex: 1 1 100%; }
        .jl-login-headline { font-size: 1.6rem; margin: 1.25rem 0 0; }
        .jl-login-sub { margin-bottom: 0; max-width: none; }
    }
</style>
@endpush

@section('content')
<div class="jl-login-breakout">
    <div class="jl-login-split">
        <div class="jl-login-left">
            <div class="jl-login-brand">
                <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo" style="width: 56px; height: 56px;"> JobLink
            </div>

            <h1 class="jl-login-headline">Find the right job.<br>Apply with confidence.<br>Get <em>hired</em> faster.</h1>
            <p class="jl-login-sub">Connecting PSU Alaminos City students, alumni, and partner employers in one place.</p>

            <div class="jl-login-features d-none d-lg-flex">
                <div class="jl-login-feature">
                    <span class="jl-login-feature-icon"><i class="bi bi-briefcase"></i></span>
                    <div>
                        <h3>Curated Job Postings</h3>
                        <p>Opportunities reviewed by your Job Coordinator.</p>
                    </div>
                </div>
                <div class="jl-login-feature">
                    <span class="jl-login-feature-icon"><i class="bi bi-file-earmark-check"></i></span>
                    <div>
                        <h3>Application Tracking</h3>
                        <p>Follow your status from applied to interview.</p>
                    </div>
                </div>
                <div class="jl-login-feature">
                    <span class="jl-login-feature-icon"><i class="bi bi-chat-dots"></i></span>
                    <div>
                        <h3>Direct Employer Messages</h3>
                        <p>Hear back from companies right inside JobLink.</p>
                    </div>
                </div>
            </div>

           <p class="jl-login-copyright d-none d-lg-block">&copy; {{ date('Y') }} JobLink &middot; PSU Alaminos City Campus &middot; <a href="{{ route('privacy') }}">Privacy Policy</a></p>
        </div>

        <div class="jl-login-right">
            <div class="jl-login-form-wrap">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 140px; height: 140px;">
                    <h1 class="h2 mb-2">Welcome to JobLink</h1>
                    <p class="text-secondary fs-5 mb-0">PSU Alaminos City career placement portal</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Student ID or email</label>
                        <input type="text" name="login" value="{{ old('login') }}" class="form-control form-control-lg" required autofocus>
                        <div class="form-text">Students and alumni sign in with their Student ID. Companies and the Job Coordinator use email.</div>
                    </div>
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">Password</label>
                            <a href="{{ route('password.forgot') }}" class="small">Forgot password?</a>
                        </div>
                        <div class="position-relative">
                            <input type="password" name="password" id="loginPassword" class="form-control form-control-lg pe-5" required>
                            <button type="button" class="jl-password-toggle" onclick="togglePassword('loginPassword', this)" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-jl-navy w-100 btn-lg">Log In</button>
                </form>

                 <div class="text-center mt-4 d-flex flex-column gap-2">
                    <a href="{{ route('activate.verify') }}" class="small">First time logging in? Activate your account</a>
                        <a href="{{ route('register.show') }}" class="small">No account yet? Request to register</a>
                    <a href="{{ route('privacy') }}" class="small text-secondary d-lg-none">Privacy Policy</a>
                </div>
            </div>
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