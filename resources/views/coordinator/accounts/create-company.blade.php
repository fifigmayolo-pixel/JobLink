@extends('layouts.app')

@section('title', 'Create Company Account')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h2 mb-1">Create Company Account</h1>
        <p class="text-secondary mb-4">A temporary password is generated — hand it over so they can log in and set their own.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.accounts.company.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Company name</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email (their login)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Industry</label>
                    <input type="text" name="industry" value="{{ old('industry') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Create Company Account</button>
            </form>

            @if(session('tempPassword'))
                <div class="alert alert-light jl-card mt-4" style="border-left: 4px solid var(--jl-gold) !important;">
                    Temporary password — share this with them directly: <strong>{{ session('tempPassword') }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection