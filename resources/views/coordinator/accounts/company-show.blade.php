@extends('layouts.app')

@section('title', $company->company_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="{{ route('coordinator.accounts.company.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to company accounts</a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="jl-company-logo" style="width: 72px; height: 72px;">
                        @if($company->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo) }}" alt="{{ $company->company_name }} logo">
                        @else
                            <div class="jl-company-logo-placeholder" style="font-size: 1.75rem;">
                                <i class="bi bi-building"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h1 class="h4 mb-1">{{ $company->company_name }}</h1>
                        <p class="text-secondary mb-0">{{ $company->user?->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('coordinator.accounts.company.temp-password', $company) }}" onsubmit="return confirm('Generate a new temporary password and email it to the company?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Temp Password</button>
                </form>
            </div>

            @if(session('tempPassword'))
                <div class="alert alert-light jl-card mb-4" style="border-left: 4px solid var(--jl-gold) !important;">
                                        Temporary password (also shown here in case the email doesn't arrive): <strong>{{ session('tempPassword') }}</strong>
                </div>
            @endif

            <dl class="row mb-0">
                <dt class="col-sm-4">Industry</dt>
                <dd class="col-sm-8">{{ $company->industry ?? '—' }}</dd>
                <dt class="col-sm-4">Contact person</dt>
                <dd class="col-sm-8">{{ $company->contact_person ?? '—' }}</dd>
                <dt class="col-sm-4">Contact number</dt>
                <dd class="col-sm-8">{{ $company->contact_number ?? '—' }}</dd>
                <dt class="col-sm-4">Address</dt>
                <dd class="col-sm-8">{{ $company->address ?? '—' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection