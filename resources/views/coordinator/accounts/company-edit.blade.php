@extends('layouts.app')

@section('title', 'Edit ' . $company->company_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="{{ route('coordinator.accounts.company.show', $company) }}" class="btn btn-outline-secondary btn-sm">&larr; Back to {{ $company->company_name }}</a>

        <h1 class="h2 mb-1 mt-3">Edit Company Account</h1>
        <p class="text-secondary mb-4">Update this company's details.</p>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('coordinator.accounts.company.update', $company) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="jl-card p-4 mb-4">
                <h2 class="h6 text-uppercase text-secondary mb-3">Company Details</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Logo</label>
                        @if($company->logo)
                            <div class="mb-2">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="Company logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.5rem;">
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $company->company_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Industry</label>
                        <input type="text" name="industry" value="{{ old('industry', $company->industry) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $company->contact_person) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact number</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number', $company->contact_number) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $company->address) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Registered email (login)</label>
                        <input type="email" name="email" value="{{ old('email', $company->user?->email) }}" class="form-control" required>
                        <div class="form-text">This is the email the company logs in with, and where password-reset codes are sent. Changing it takes effect immediately.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-jl-gold btn-lg mt-4">Save Changes</button>
            </div>
        </form>

        <div class="jl-card p-4" style="border: 1px solid rgba(180,85,63,0.3);">
            <h2 class="h6 text-uppercase text-secondary mb-3">Archive Account</h2>
            <p class="mb-3">Archiving this account disables their login and hides their job postings from students and alumni. Application history is kept. Restoring the account brings back any postings that haven't expired.</p>

            <form method="POST" action="{{ route('coordinator.accounts.company.destroy', $company) }}"
                  data-confirm="Their login will be disabled and their job postings will be hidden. Everything comes back if you restore the account."
                  data-confirm-title="Archive this company account?"
                  data-confirm-button="Archive"
                  data-confirm-variant="danger"
                  data-confirm-icon="exclamation-triangle">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Archive Company Account</button>
            </form>
        </div>
    </div>
</div>
@endsection