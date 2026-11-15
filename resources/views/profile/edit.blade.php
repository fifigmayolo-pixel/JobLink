@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
@php
    // The "searchable by partner companies" card below.
    $searchOn = (bool) $profile->searchable_by_companies;
    $visibleNow = $searchOn && $profile->isSearchable();
    $cvMissing = $profile->cvCompleteness()['missing_required'];
@endphp
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            @include('partials.profile-avatar', ['imagePath' => $profile->profile_picture, 'label' => $profile->full_name, 'size' => 88])
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

        {{-- Opt-in to company search. Off by default; its own form so it is a deliberate choice. --}}
        <div class="jl-card p-4 mt-4" id="searchable">
            <h2 class="h6 text-uppercase text-secondary mb-3">Searchable by partner companies</h2>

            <form method="POST" action="{{ route('profile.searchable.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="searchable" value="0">

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="searchableSwitch" name="searchable" value="1" @checked($searchOn)>
                    <label class="form-check-label fw-semibold" for="searchableSwitch">Make my profile searchable by partner companies</label>
                </div>

                <p class="text-secondary small">
                    By default only a company you apply to can see your CV. If you turn this on, partner companies can also find
                    your CV when they search, even if you have not applied to them. It is your choice, and joining JobLink does not turn it on.
                </p>

                <div class="row g-3 small mb-3">
                    <div class="col-md-6">
                        <div class="fw-semibold mb-1">Companies can see</div>
                        <ul class="mb-0 ps-3">
                            <li>Your name, course and year</li>
                            <li>Your city and province</li>
                            <li>Your CV: summary, skills, experience, education, certifications and links</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold mb-1">Companies cannot see</div>
                        <ul class="mb-0 ps-3">
                            <li>Your email and mobile number, unless you apply to their job or agree to share them</li>
                            <li>Your street address, date of birth, gender, civil status and profile picture</li>
                        </ul>
                    </div>
                </div>

                @if($visibleNow)
                    <p class="mb-3"><span class="jl-pill jl-pill-neutral"><i class="bi bi-eye me-1" aria-hidden="true"></i> Visible to partner companies</span></p>
                @elseif($searchOn)
                    <div class="alert alert-light jl-card py-2 small" style="border-left: 4px solid var(--jl-gold) !important;" role="status">
                        The switch is on, but you will appear only when your CV is ready. Still needed: {{ implode(' and ', $cvMissing) }}.
                        <a href="{{ route('cv.edit') }}">Open My CV</a>
                    </div>
                @else
                    <p class="text-secondary small mb-3">Not searchable. Only companies you apply to can see your CV.</p>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-jl-gold">Save visibility</button>
                    <a href="{{ route('profile.searchable.preview') }}" class="btn btn-outline-secondary">Preview what companies see</a>
                </div>

                <p class="text-secondary small mt-3 mb-0">
                    You can turn this off at any time and you disappear from search straight away. Each time a company opens
                    your profile, JobLink records it. See the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                </p>
            </form>
        </div>
    </div>
</div>
@endsection
