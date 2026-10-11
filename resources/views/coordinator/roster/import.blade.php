@extends('layouts.app')

@section('title', 'Import Roster')

@section('content')
        <a href="{{ route('coordinator.accounts.index') }}"
        class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-arrow-left"></i>
            Back to Accounts
        </a>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h1 class="h2 mb-1">Import Roster</h1>
        <p class="text-secondary mb-4">Upload an Excel file (.xlsx) with two columns: <strong>Student ID</strong> and <strong>Full Name</strong>. First row should be the headers.</p>

        <div class="jl-card p-4">
            <form method="POST" action="{{ route('coordinator.roster.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Excel file</label>
                    <input type="file" name="roster" accept=".xlsx,.xls" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">These are all:</label>
                    <select name="role" class="form-select" required>
                        <option value="student">Students</option>
                        <option value="alumni">Alumni</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-jl-gold btn-lg">Upload and Import</button>
            </form>
        </div>

        <p class="text-secondary small mt-3">
            Large files run in the background — you'll get a notification here once it's done, with a count of anything that got skipped (like a duplicate Student ID) and why.
        </p>

        <p class="text-center mt-3">
            <a href="{{ route('coordinator.roster.quick-add') }}" class="small">&larr; Just adding one? Use the quick form instead</a>
        </p>
    </div>
</div>
@endsection