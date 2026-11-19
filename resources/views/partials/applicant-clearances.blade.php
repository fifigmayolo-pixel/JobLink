{{--
    The company's view of an applicant's clearances, on the application page.
    Pass $application. Only drawn for an application that is not withdrawn (the page already
    hides everything else for those).

    The links go through ClearanceFileController, which checks the policy again, so showing a
    link here never grants anything by itself.
--}}
@php
    $rows = \App\Models\ProfileClearance::forProfile($application->profile_id);
    $removed = $application->applicantRemoved();
@endphp

<div class="jl-card p-4 p-md-5 mt-3" id="applicant-clearances">
    <h2 class="h6 text-uppercase text-secondary mb-1">Clearances</h2>
    <p class="small text-secondary mb-3">
        NBI and barangay clearance PDFs the applicant uploaded. You can open them while this application is active. Each time you open one, JobLink records it.
    </p>

    @if($removed)
        <p class="small mb-3" style="color: var(--jl-rose);">
            This applicant's account was removed, so the clearance files can no longer be opened.
        </p>
    @endif

    <ul class="list-unstyled mb-0">
        @foreach(config('clearances.types') as $type => $meta)
            @php $clearance = $rows->get($type); @endphp
            <li class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 {{ $loop->first ? '' : 'border-top' }}" style="border-color: rgba(15,42,92,0.08) !important;">
                <div>
                    <div class="fw-semibold">{{ $meta['label'] }}</div>
                    @unless($clearance)
                        <div class="small text-secondary">Not on file. This application was sent before clearances were required, or the applicant removed it.</div>
                    @endunless
                </div>
                <div class="d-flex align-items-center gap-2">
                    @include('partials.clearance-badge', ['clearance' => $clearance])
                    @if($clearance && ! $removed)
                        <a href="{{ route('clearances.show', $clearance) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Open PDF
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>
