@extends('layouts.app')

@section('title', 'PESO Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h2 mb-1">PESO Accounts</h1>
        <p class="text-secondary mb-0">View and manage PESO staff accounts.</p>
    </div>
    <a href="{{ route('coordinator.accounts.peso.create') }}" class="btn btn-jl-gold">Create PESO Account</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-jl-navy w-100">Filter</button>
    </div>
</form>

<hr class="jl-rule">

@forelse($pesoUsers as $pesoUser)
    <div class="jl-card p-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="h6 mb-1">{{ $pesoUser->full_name }}</h2>
            <p class="text-secondary small mb-0">{{ $pesoUser->email }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('coordinator.accounts.peso.show', $pesoUser) }}" class="btn btn-outline-secondary btn-sm">View</a>
            <a href="{{ route('coordinator.accounts.peso.edit', $pesoUser) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePesoAccount('{{ route('coordinator.accounts.peso.destroy', $pesoUser) }}')">Delete</button>
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">No PESO accounts match your search.</p>
    </div>
@endforelse

{{ $pesoUsers->links() }}

@push('scripts')
<script>
function deletePesoAccount(url) {
    if (!confirm('Remove this PESO account? This can be restored later if needed.')) {
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