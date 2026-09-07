@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            @include('partials.profile-avatar', ['profile' => $profile, 'size' => 88])
            <div>
                <h1 class="h2 mb-1">{{ $profile->full_name }}</h1>
                <p class="text-secondary mb-0">Update your contact details. Locked fields need your Job Coordinator to change.</p>
            </div>
        </div>
        <hr class="jl-rule">

        <div class="jl-card p-4 mb-4">
            <h2 class="h6 text-uppercase text-secondary mb-3">On File (Locked)</h2>
            <dl class="row mb-0">
                <dt class="col-sm-4">Full name</dt>
                <dd class="col-sm-8">{{ $profile->full_name }}</dd>
                <dt class="col-sm-4">Course / Program</dt>
                <dd class="col-sm-8">{{ $profile->course_program }}</dd>
                <dt class="col-sm-4">{{ $profile->status === 'student' ? 'Year Level & Section' : 'Year Graduated' }}</dt>
                <dd class="col-sm-8">
                    {{ $profile->status === 'student' ? $profile->year_level.' - '.$profile->section : $profile->year_graduated }}
                </dd>
            </dl>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="jl-card p-4">
                <h2 class="h6 text-uppercase text-secondary mb-3">Editable</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Profile picture</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        <div class="form-text">Shown above — leave this blank to keep your current photo.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Personal email</label>
                        <input type="email" name="personal_email" value="{{ old('personal_email', $profile->personal_email) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile number</label>
                        <input type="text" name="mobile_number" value="{{ old('mobile_number', $profile->mobile_number) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">House number &amp; street</label>
                        <input type="text" name="house_street" value="{{ old('house_street', $profile->house_street) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Barangay</label>
                        <input type="text" name="barangay" value="{{ old('barangay', $profile->barangay) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Municipality / City</label>
                        <input type="text" name="municipality_city" value="{{ old('municipality_city', $profile->municipality_city) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Province</label>
                        <input type="text" name="province" value="{{ old('province', $profile->province) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Postal code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $profile->postal_code) }}" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-jl-gold btn-lg mt-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection