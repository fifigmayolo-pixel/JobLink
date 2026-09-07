@extends('layouts.app')

@section('title', 'Edit ' . $user->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <a href="{{ route('coordinator.accounts.peso.show', $user) }}" class="btn btn-outline-secondary btn-sm">&larr; Back to {{ $user->full_name }}</a>

        <h1 class="h2 mb-1 mt-3">Edit PESO Account</h1>
        <hr class="jl-rule">

        <form method="POST" action="{{ route('coordinator.accounts.peso.update', $user) }}">
            @csrf
            @method('PATCH')

            <div class="jl-card p-4 mb-4">
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" value="{{ $user->email }}" class="form-control" disabled>
                    <small class="text-secondary">Email can't be changed here.</small>
                </div>

                <button type="submit" class="btn btn-jl-gold btn-lg">Save Changes</button>
            </div>
        </form>

        <div class="jl-card p-4" style="border: 1px solid rgba(180,85,63,0.3);">
            <h2 class="h6 text-uppercase text-secondary mb-3">Danger Zone</h2>
            <p class="mb-3">Removing this account disables their login.</p>
            <button type="button" class="btn btn-outline-danger" onclick="deletePesoAccount('{{ route('coordinator.accounts.peso.destroy', $user) }}')">Delete PESO Account</button>
        </div>
    </div>
</div>

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