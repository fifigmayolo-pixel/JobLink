@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
<h1 class="h2 mb-1">Accounts</h1>
<p class="text-secondary mb-4">Every way to get someone into JobLink.</p>

<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('coordinator.roster.quick-add') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-person-plus" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Add Student/Alumni</h2>
            </div>
            <p class="text-secondary small mb-0">One at a time, straight from the master list book.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.roster.import') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-file-earmark-spreadsheet" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Import Roster (Excel)</h2>
            </div>
            <p class="text-secondary small mb-0">Upload a whole batch at once.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.accounts.company.index') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-building" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Company Accounts</h2>
            </div>
            <p class="text-secondary small mb-0">View, edit, or remove registered companies.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.accounts.company.create') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-building-fill" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Create Company Account</h2>
            </div>
            <p class="text-secondary small mb-0">Employers who'll post jobs and review applicants.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.accounts.peso.index') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-people" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">PESO Accounts</h2>
            </div>
            <p class="text-secondary small mb-0">View, edit, or remove PESO staff.</p>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('coordinator.accounts.peso.create') }}" class="jl-card p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-briefcase" style="font-size: 1.75rem; color: var(--jl-navy);"></i>
                <h2 class="h6 mb-0">Create PESO Account</h2>
            </div>
            <p class="text-secondary small mb-0">Public Employment Service Office staff.</p>
        </a>
    </div>
</div>
@endsection