@extends('layouts.app')

@section('title', 'My Applications')

@section('content')
<h1 class="h2 mb-1">My Applications</h1>
<p class="text-secondary mb-4">Track the status of everything you've applied to.</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('applications.index') }}"
       class="btn btn-sm {{ $tab === 'active' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
        Active
        <span class="badge rounded-pill ms-1" style="background: {{ $tab === 'active' ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $activeCount }}</span>
    </a>
    <a href="{{ route('applications.index', ['tab' => 'past']) }}"
       class="btn btn-sm {{ $tab === 'past' ? 'btn-jl-navy' : 'btn-outline-secondary' }}">
        Past
        <span class="badge rounded-pill ms-1" style="background: {{ $tab === 'past' ? 'rgba(255,255,255,0.2)' : 'rgba(107,114,128,0.15)' }}; color: inherit;">{{ $pastCount }}</span>
    </a>
</div>

<hr class="jl-rule">

@if($tab === 'past' && $applications->isNotEmpty())
    <p class="text-secondary small mb-3">Rejected and withdrawn applications. Remove the ones you no longer want in your list; the company keeps its own record.</p>
@endif

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

                <p class="text-secondary small mb-3 flex-grow-1">
                    Applied {{ $application->date_applied->format('M j, Y') }}
                    @if($application->isRejected())
                        &middot; Resume removed
                    @endif
                </p>

                <div class="d-flex gap-2">
                    <a href="{{ route('applications.show', $application) }}" class="btn btn-jl-navy btn-sm flex-grow-1">View Application</a>

                    {{-- Past tab only: rejected/withdrawn applications can be cleared from the list. --}}
                    @if($tab === 'past')
                        <form method="POST" action="{{ route('applications.hide', $application) }}"
                              data-confirm="It will be cleared from your list. The company keeps its own record of the application."
                              data-confirm-title="Remove from your list?"
                              data-confirm-button="Remove"
                              data-confirm-icon="eye-slash">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-secondary btn-sm" aria-label="Remove from my list">
                                <i class="bi bi-x-lg"></i> Remove
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="jl-card p-5 text-center">
                @if($tab === 'past')
                    <p class="mb-0 text-secondary">No past applications. Rejected and withdrawn applications show up here.</p>
                @else
                    <p class="mb-0 text-secondary">You have no active applications. Browse <a href="{{ route('jobs.index') }}">open positions</a> to get started.</p>
                @endif
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $applications->links() }}
</div>
@endsection