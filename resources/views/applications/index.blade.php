@extends('layouts.app')

@section('title', 'My Applications')

@section('content')
<h1 class="h2 mb-1">My Applications</h1>
<p class="text-secondary mb-4">Track the status of everything you've applied to.</p>
<hr class="jl-rule">

<div class="row g-3">
    @forelse($applications as $application)
        @php
            // The controller already loads jobPosting.company, so this adds no extra queries.
            $company = $application->jobPosting->company;
            $accent = \App\Support\CompanyAccent::for($company?->id, $company?->company_name);
            $initial = \App\Support\CompanyAccent::initial($company?->company_name);
        @endphp
        <div class="col-sm-6 col-lg-4">
            <div class="jl-card jl-card-hover h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        {{-- Company logo; falls back to the company's colored initial if there is no logo or it fails to load. --}}
                        <div data-initial="{{ $initial }}"
                             style="width: 44px; height: 44px; border-radius: 10px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; background: {{ $accent['soft'] }}; color: {{ $accent['text'] }}; box-shadow: 0 0 0 2px {{ $accent['soft'] }};">
                            @if($company?->logo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="{{ $company->company_name }} logo"
                                     style="width: 100%; height: 100%; object-fit: cover;"
                                     onerror="this.parentNode.textContent = this.parentNode.dataset.initial;">
                            @else
                                {{ $initial }}
                            @endif
                        </div>
                        <div>
                            <h2 class="h6 mb-0">{{ $application->jobPosting->title }}</h2>
                            <div class="text-secondary small">{{ $company?->company_name ?? 'Company' }}</div>
                        </div>
                    </div>
                    <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2 flex-shrink-0">{{ ucfirst($application->status) }}</span>
                </div>
                <p class="text-secondary small mb-3 flex-grow-1">Applied {{ $application->date_applied->format('M j, Y') }}</p>
                <a href="{{ route('applications.show', $application) }}" class="btn btn-jl-navy btn-sm w-100">View Application</a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                <p class="mb-0 text-secondary">You haven't applied to anything yet. Browse <a href="{{ route('jobs.index') }}">open positions</a> to get started.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $applications->links() }}
</div>
@endsection