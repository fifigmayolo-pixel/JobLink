@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 mb-1">Complete Your Profile</h1>
        <p class="text-secondary mb-4">A few details before you can browse and apply for jobs.</p>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('profile.complete.store') }}" enctype="multipart/form-data">
            @csrf

            <h2 class="h5 mb-3">Personal Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="profilePicture">Profile picture</label>
                    <input type="file" id="profilePicture" name="profile_picture" class="form-control" accept="image/*">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="fullName">Full name</label>
                    <input type="text" id="fullName" value="{{ auth()->user()->full_name }}" class="form-control" autocomplete="off" disabled>
                    <div class="form-text">On file from registration — your Job Coordinator can correct it if there's a typo.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="dateOfBirth">Date of birth</label>
                    <input type="date" id="dateOfBirth" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control" autocomplete="bday" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="age">Age</label>
                    <input type="number" id="age" name="age" value="{{ old('age') }}" class="form-control" min="15" max="100" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="gender">Gender</label>
                    <input type="text" id="gender" name="gender" value="{{ old('gender') }}" class="form-control" autocomplete="sex" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="civilStatus">Civil status</label>
                    <input type="text" id="civilStatus" name="civil_status" value="{{ old('civil_status') }}" class="form-control" autocomplete="off" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nationality">Nationality</label>
                    <select id="nationality" name="nationality" class="form-select" autocomplete="off" required>
                        <option value="" disabled selected>Select nationality…</option>
                        @foreach(\App\Models\StudentAlumniProfile::NATIONALITIES as $nationality)
                            <option value="{{ $nationality }}" @selected(old('nationality') === $nationality)>{{ $nationality }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="h5 mb-3">Academic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="courseProgram">Course / Program</label>
                    <select id="courseProgram" name="course_program" class="form-select" autocomplete="off" required>
                        <option value="" disabled selected>Select program…</option>
                        @foreach(\App\Models\StudentAlumniProfile::PROGRAMS as $program)
                            <option value="{{ $program }}" @selected(old('course_program') === $program)>{{ $program }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Locked after saving, same as full name above.</div>
                </div>
                @if($isStudent)
                    <div class="col-md-6">
                        <label class="form-label" for="yearLevel">Year level</label>
                        <select id="yearLevel" name="year_level" class="form-select" autocomplete="off" required>
                            <option value="" disabled selected>Select year level…</option>
                            @foreach(\App\Models\StudentAlumniProfile::YEAR_LEVELS as $level)
                                <option value="{{ $level }}" @selected(old('year_level') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="section">Section</label>
                        <select id="section" name="section" class="form-select" autocomplete="off" required>
                            <option value="" disabled selected>Select section…</option>
                            @foreach(\App\Models\StudentAlumniProfile::SECTIONS as $section)
                                <option value="{{ $section }}" @selected(old('section') === $section)>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-6">
                        <label class="form-label" for="yearGraduated">Year graduated</label>
                        <select id="yearGraduated" name="year_graduated" class="form-select" autocomplete="off" required>
                            <option value="" disabled selected>Select year graduated…</option>
                            @foreach(\App\Models\StudentAlumniProfile::academicYearRanges() as $range)
                                <option value="{{ $range }}" @selected(old('year_graduated') === $range)>{{ $range }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <h2 class="h5 mb-3">Contact &amp; Address</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="personalEmail">Personal email</label>
                    <input type="email" id="personalEmail" name="personal_email" value="{{ old('personal_email') }}" class="form-control" autocomplete="email" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mobileNumber">Mobile number</label>
                    <input type="text" id="mobileNumber" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" autocomplete="tel" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="houseStreet">House number &amp; street</label>
                    <input type="text" id="houseStreet" name="house_street" value="{{ old('house_street') }}" class="form-control" autocomplete="street-address" required>
                </div>
                {{-- Province / city / barangay are filled by the cascade script below, so browser autofill is turned off for them --}}
                <div class="col-md-4">
                    <label class="form-label" for="province">Province</label>
                    <select name="province" id="province" class="form-select" autocomplete="off" required>
                        <option value="" disabled @selected(! old('province'))>Select province…</option>
                        @foreach(\App\Support\PhilippineLocations::provinces() as $province)
                            <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="municipalityCity">Municipality / City</label>
                    <select name="municipality_city" id="municipalityCity" class="form-select" autocomplete="off" required>
                        <option value="" disabled selected>Select province first…</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="barangay">Barangay</label>
                    <select name="barangay" id="barangay" class="form-select" autocomplete="off" required>
                        <option value="" disabled selected>Select municipality/city first…</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="postalCode">Postal code</label>
                    <input type="text" id="postalCode" name="postal_code" value="{{ old('postal_code') }}" class="form-control" autocomplete="postal-code" required>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="acceptTerms" name="accept_terms" value="1" autocomplete="off" @checked(old('accept_terms'))>
                <label class="form-check-label" for="acceptTerms">
                    I have read and agree to the
                    <a href="#" class="fw-semibold" data-bs-toggle="modal" data-bs-target="#termsModal">Terms &amp; Conditions</a>.
                </label>
            </div>

            <button type="submit" class="btn btn-jl-gold btn-lg" id="saveProfileBtn">Save and Continue</button>
        </form>
    </div>
</div>

<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="h5 modal-title" id="termsModalLabel">Terms &amp; Conditions</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-secondary small">
                <div class="text-center mb-3">
                    <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo mb-3" style="width: 120px; height: 120px;">
                </div>
                @include('partials.terms-content')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-jl-navy" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('acceptTerms');
    const button = document.getElementById('saveProfileBtn');

    function sync() { button.disabled = !checkbox.checked; }
    checkbox.addEventListener('change', sync);
    sync();
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('municipalityCity');
    const barangaySelect = document.getElementById('barangay');

    const oldCity = @json(old('municipality_city'));
    const oldBarangay = @json(old('barangay'));

    function resetSelect(select, placeholder) {
        select.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.disabled = true;
        opt.selected = true;
        opt.textContent = placeholder;
        select.appendChild(opt);
    }

    function fillSelect(select, values, selectedValue) {
        values.forEach(function (value) {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            if (value === selectedValue) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function loadCities(province, selectedCity) {
        resetSelect(citySelect, 'Loading…');
        resetSelect(barangaySelect, 'Select municipality/city first…');

        if (! province) {
            resetSelect(citySelect, 'Select province first…');
            return;
        }

        fetch('{{ route('profile.complete.cities') }}?province=' + encodeURIComponent(province))
            .then(res => res.json())
            .then(function (cities) {
                resetSelect(citySelect, 'Select municipality/city…');
                fillSelect(citySelect, cities, selectedCity);

                if (selectedCity && cities.includes(selectedCity)) {
                    loadBarangays(province, selectedCity, oldBarangay);
                }
            });
    }

    function loadBarangays(province, city, selectedBarangay) {
        resetSelect(barangaySelect, 'Loading…');

        if (! province || ! city) {
            resetSelect(barangaySelect, 'Select municipality/city first…');
            return;
        }

        fetch('{{ route('profile.complete.barangays') }}?province=' + encodeURIComponent(province) + '&city=' + encodeURIComponent(city))
            .then(res => res.json())
            .then(function (barangays) {
                resetSelect(barangaySelect, 'Select barangay…');
                fillSelect(barangaySelect, barangays, selectedBarangay);
            });
    }

    provinceSelect.addEventListener('change', function () {
        loadCities(this.value, null);
    });

    citySelect.addEventListener('change', function () {
        loadBarangays(provinceSelect.value, this.value, null);
    });

    // Re-populate the cascade if the form is redisplayed after a validation error elsewhere
    if (provinceSelect.value) {
        loadCities(provinceSelect.value, oldCity);
    }
});
</script>
@endpush
@endsection