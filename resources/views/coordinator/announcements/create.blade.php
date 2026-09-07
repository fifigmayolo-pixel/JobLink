@extends('layouts.app')

@section('title', 'Post Announcement')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h2 mb-1">Post Announcement</h1>
        <p class="text-secondary mb-4">Delivered as a notification to everyone in the audience you choose. Runs in the background for large audiences.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.announcements.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="4" class="form-control" required>{{ old('message') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Audience</label>
                    <select name="audience" class="form-select" required>
                        <option value="students_alumni" @selected(old('audience', 'students_alumni') === 'students_alumni')>Students &amp; Alumni</option>
                        <option value="all" @selected(old('audience') === 'all')>Everyone (all roles)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Send Announcement</button>
            </form>
        </div>
    </div>
</div>
@endsection