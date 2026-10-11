@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h2 mb-1">Edit Profile</h1>
        <p class="text-secondary mb-4">Update your name. Your email stays fixed as your login.</p>
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
                    <input type="text" name="full_name" value="{{ old('full_name', auth()->user()->full_name) }}" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" value="{{ auth()->user()->email }}" class="form-control" disabled>
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Save Changes</button>
            </form>
        </div>
    </div>
</div>
@endsection