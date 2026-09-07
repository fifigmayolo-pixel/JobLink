@extends('layouts.app')

@section('title', 'Add Student/Alumni')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h2 mb-1">Add Student/Alumni</h1>
        <p class="text-secondary mb-4">Straight from the master list — Student ID and name only. They'll fill in the rest when they activate.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.roster.quick-add.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id_number" value="{{ old('student_id_number') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Type</label>
                    <select name="role" class="form-select" required>
                        <option value="student" @selected(old('role', 'student') === 'student')>Student</option>
                        <option value="alumni" @selected(old('role') === 'alumni')>Alumni</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Add and Continue</button>
            </form>
        </div>

        <p class="text-center mt-3">
            <a href="{{ route('coordinator.roster.import') }}" class="small">Importing a whole batch instead? Upload an Excel roster &rarr;</a>
        </p>
    </div>
</div>
@endsection