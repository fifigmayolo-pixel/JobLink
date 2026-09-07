@extends('layouts.app')

@section('title', 'Company Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 mb-1">Company Profile</h1>
        <p class="text-secondary mb-4">Keep your company details up to date for job seekers.</p>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('company.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="jl-card p-4 mb-4">
                <h2 class="h6 text-uppercase text-secondary mb-3">Company Details</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Logo</label>
                        @if($company->logo)
                            <div class="mb-2">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo) }}" alt="Company logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.5rem;">
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
                </div>

                <button type="submit" class="btn btn-jl-gold btn-lg mt-4">Save Changes</button>
            </div>
        </form>

        <div class="jl-card p-4">
            <h2 class="h6 text-uppercase text-secondary mb-3">Password</h2>
            <p class="mb-3">Need to update your password?</p>
            <a href="{{ route('password.edit') }}" class="btn btn-jl-navy">Change Password</a>
        </div>
    </div>
</div>
@endsection