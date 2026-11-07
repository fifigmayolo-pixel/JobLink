@extends('layouts.app')

@section('title', 'Company Accounts')

@section('content')
@role('job_coordinator')
    <a href="{{ route('coordinator.accounts.index') }}"
       class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mb-3">
        <i class="bi bi-arrow-left"></i>
        Back to Accounts
    </a>
@endrole

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">Company Accounts</h1>
        <p class="text-secondary mb-0">View and manage registered company accounts.</p>
    </div>
    <a href="{{ route('coordinator.accounts.company.create') }}" class="btn btn-jl-gold">Create Company Account</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('coordinator.accounts.company.index') }}"
       class="btn btn-sm {{ $tab === 'active' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">Active</a>
    <a href="{{ route('coordinator.accounts.company.index', ['tab' => 'archived']) }}"
       class="btn btn-sm {{ $tab === 'archived' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
        Archived
        <span class="badge rounded-pill ms-1" style="background: {{ $tab === 'archived' ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $archivedCount }}</span>
    </a>
</div>

<form method="GET" class="row g-2 mb-4">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by company name">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-jl-navy w-100">Filter</button>
    </div>
</form>

<hr class="jl-rule">

@forelse($companies as $company)
    <div class="jl-card p-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="jl-company-logo" style="width: 48px; height: 48px;">
                @if($company->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="{{ $company->company_name }} logo">
                @else
                    <div class="jl-company-logo-placeholder" style="font-size: 1.25rem;">
                        <i class="bi bi-building"></i>
                    </div>
                @endif
            </div>
            <div>
                <h2 class="h6 mb-1">{{ $company->company_name }}</h2>
                <p class="text-secondary small mb-0">
                    {{ $company->industry ?? '—' }} &middot; {{ $company->user?->email }}
                    @if($tab === 'archived' && $company->user?->deleted_at)
                        &middot; Archived {{ $company->user->deleted_at->format('M j, Y') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="d-flex gap-2">
            @if($tab === 'archived')
                <form method="POST" action="{{ route('coordinator.accounts.company.restore', $company) }}"
                      data-confirm="They will be able to log in again, and their job postings that haven't expired will show again."
                      data-confirm-title="Restore {{ $company->company_name }}?"
                      data-confirm-button="Restore"
                      data-confirm-icon="arrow-counterclockwise">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-jl-gold btn-sm">Restore</button>
                </form>
            @else
                <a href="{{ route('coordinator.accounts.company.show', $company) }}" class="btn btn-outline-secondary btn-sm">View</a>
                <a href="{{ route('coordinator.accounts.company.edit', $company) }}" class="btn btn-outline-secondary btn-sm">Edit</a>

                <form method="POST" action="{{ route('coordinator.accounts.company.destroy', $company) }}"
                      data-confirm="Their login will be disabled and their job postings will be hidden. Everything comes back if you restore the account."
                      data-confirm-title="Archive {{ $company->company_name }}?"
                      data-confirm-button="Archive"
                      data-confirm-variant="danger"
                      data-confirm-icon="exclamation-triangle">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Archive</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">
            {{ $tab === 'archived' ? 'No archived company accounts.' : 'No company accounts match your search.' }}
        </p>
    </div>
@endforelse

{{ $companies->links() }}
@endsection