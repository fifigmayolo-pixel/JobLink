@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h2 mb-1">Edit Profile</h1>
        <p class="text-secondary mb-4">Update your name. Your email stays fixed as your login.</p>
        <hr class="jl-rule">

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.profile.update') }}">
                @csrf
                @method('PATCH')
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