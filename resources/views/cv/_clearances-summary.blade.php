{{--
    The clearances card on the My CV page. Pass $profile.
    It sits outside the CV form on purpose: the upload forms cannot be nested inside it.
--}}
@php $clearanceStates = $profile->clearanceStates(); @endphp

<div class="jl-card p-4 mb-4">
    <div class="d-flex align-items-start gap-2 mb-3">
        <span class="jl-section-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></span>
        <div>
            <h2 class="h6 text-uppercase text-secondary mb-0">Clearances</h2>
            <p class="small text-secondary mb-0 mt-1">Your NBI clearance and barangay clearance, as PDF files with their validity dates.</p>
        </div>
        <span class="jl-pill jl-pill-urgent ms-auto d-none d-sm-inline-flex">Required to apply</span>
    </div>

    <ul class="list-unstyled mb-3">
        @foreach($clearanceStates as $state)
            <li class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-1 small">
                <span class="fw-semibold">{{ $state['label'] }}</span>
                @include('partials.clearance-badge', ['clearance' => $state['clearance']])
            </li>
        @endforeach
    </ul>

    <a href="{{ route('clearances.edit') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-upload" aria-hidden="true"></i> Manage clearances
    </a>
    <p class="small text-secondary mt-3 mb-0">
        Companies can open these only after you apply to one of their jobs.
    </p>
</div>
