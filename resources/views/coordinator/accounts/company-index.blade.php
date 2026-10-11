@extends('layouts.app')

@section('title', 'Company Accounts')

@section('content')
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

<form method="GET" class="row g-2 mb-4">
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
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo) }}" alt="{{ $company->company_name }} logo">
                @else
                    <div class="jl-company-logo-placeholder" style="font-size: 1.25rem;">
                        <i class="bi bi-building"></i>
                    </div>
                @endif
            </div>
            <div>
                <h2 class="h6 mb-1">{{ $company->company_name }}</h2>
                <p class="text-secondary small mb-0">{{ $company->industry ?? '—' }} &middot; {{ $company->user?->email }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('coordinator.accounts.company.show', $company) }}" class="btn btn-outline-secondary btn-sm">View</a>
            <a href="{{ route('coordinator.accounts.company.edit', $company) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCompanyAccount('{{ route('coordinator.accounts.company.destroy', $company) }}')">Delete</button>
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">No company accounts match your search.</p>
    </div>
@endforelse

{{ $companies->links() }}

@push('scripts')
<script>
function deleteCompanyAccount(url) {
    if (!confirm('Remove this company account? This can be restored later if needed.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
@endsection