@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <div class="jl-card p-4 p-md-5 mt-3">
            @php
                $company = $application->jobPosting->company;
                $accent = \App\Support\CompanyAccent::for($company?->id, $company?->company_name);
                $initial = \App\Support\CompanyAccent::initial($company?->company_name);
            @endphp
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-3">
                    {{-- Company logo; falls back to the company's colored initial if there is no logo or it fails to load. --}}
                    <div data-initial="{{ $initial }}"
                         style="width: 56px; height: 56px; border-radius: 12px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.4rem; background: {{ $accent['soft'] }}; color: {{ $accent['text'] }}; box-shadow: 0 0 0 3px {{ $accent['soft'] }};">
                        @if($company?->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="{{ $company->company_name }} logo"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.parentNode.textContent = this.parentNode.dataset.initial;">
                        @else
                            {{ $initial }}
                        @endif
                    </div>
                    <div>
                        <h1 class="h4 mb-1">{{ $application->jobPosting->title }}</h1>
                        <p class="text-secondary mb-0">{{ $company?->company_name ?? 'Company' }}</p>
                    </div>
                </div>
                <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
            </div>

            <p class="text-secondary small mb-4">Applied {{ $application->date_applied->format('M j, Y') }}</p>

            @if($application->isWithdrawn())
                {{-- The resume file is deleted on withdrawal, so there is nothing to view. --}}
                <div class="alert alert-light jl-card mb-0" style="border-left: 4px solid var(--jl-slate) !important;">
                    You withdrew this application on {{ $application->updated_at->format('M j, Y') }}. Your resume was removed. If the job is still open, you can apply again from its page.
                </div>
            @elseif($application->isRejected())
                {{-- The resume file is deleted when the company rejects, so there is nothing to view. --}}
                <div class="alert alert-light jl-card mb-0" style="border-left: 4px solid var(--jl-rose) !important;">
                    The company decided not to move forward with this application. Your resume file was deleted when it was rejected. Check your notifications for their message.
                </div>
            @else
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('applications.resume', $application) }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> View Submitted Resume
                    </a>

                    {{-- Only while the company hasn't acted on it yet. --}}
                    @if($application->status === 'pending')
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            Withdraw application
                        </button>
                    @endif
                </div>
            @endif

            @if($application->status === 'interview')
                <div class="alert alert-light jl-card mt-4" style="border-left: 4px solid var(--jl-gold) !important;">
                    You've been invited to interview. Check your notifications for details from the company.
                </div>
            @endif
        </div>
    </div>
</div>

@if($application->status === 'pending')
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content jl-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5" id="withdrawModalTitle">Withdraw this application?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Your application for <strong>{{ $application->jobPosting->title }}</strong> at
                        {{ $company?->company_name ?? 'the company' }} will be marked as withdrawn, and your submitted
                        resume will be deleted. You can apply again later if the job is still open.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep application</button>
                    <form method="POST" action="{{ route('applications.withdraw', $application) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger">Yes, withdraw</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection