@extends('layouts.app')

@section('title', 'Application Review')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="{{ route('company.applications.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <div class="jl-card p-4 p-md-5 mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h1 class="h4 mb-1">{{ $application->applicantName() }}</h1>
                    <p class="text-secondary mb-0">Applied for {{ $application->jobPosting->title }} &middot; {{ $application->date_applied->format('M j, Y') }}</p>
                </div>
                <span class="badge rounded-pill jl-badge-{{ $application->status }} px-3 py-2">{{ ucfirst($application->status) }}</span>
            </div>

            @if($application->applicantRemoved())
                <div class="alert alert-light jl-card mb-4" style="border-left: 4px solid var(--jl-rose) !important;" role="alert">
                    This applicant's account has been removed, so status updates and messages will not reach them. Their application and resume are kept for your records.
                </div>
            @endif

            <a href="{{ route('company.applications.resume', $application) }}" class="btn btn-outline-secondary mb-4" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> View Resume (PDF)
            </a>

            <hr class="jl-rule">

            <h2 class="h6 text-uppercase text-secondary mb-3">Send a Message</h2>

            <form method="POST" action="{{ route('company.applications.message', $application) }}" id="messageForm">
                @csrf
                {{-- Holds the selected decision ('interview' or 'rejected').
                     Nothing is applied until Send Message submits this form. --}}
                <input type="hidden" name="status" id="statusInput" value="{{ old('status') }}">

                <div class="mb-3">
                    <textarea name="message" rows="4" maxlength="2000" class="form-control @error('message') is-invalid @enderror" placeholder="Message to the applicant" required autofocus>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-jl-navy">Send Message</button>

                    <span class="vr mx-1"></span>
                    <span class="text-secondary small">Update status:</span>

                    <button type="button" class="btn btn-outline-secondary js-status-btn" data-status="interview">Invite to Interview</button>
                    <button type="button" class="btn btn-outline-secondary js-status-btn" data-status="rejected">Reject</button>
                </div>

                <p id="statusHint" class="small text-secondary mt-2 mb-0"></p>

                @error('status')
                    <p class="small text-danger mt-2 mb-0">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('messageForm');
    const input = document.getElementById('statusInput');
    const hint = document.getElementById('statusHint');
    const buttons = document.querySelectorAll('.js-status-btn');

    const selectedClass = { interview: 'btn-jl-gold', rejected: 'btn-secondary' };
    const hints = {
        interview: 'The applicant will be invited to an interview when you click Send Message.',
        rejected: 'The application will be marked as rejected when you click Send Message.',
    };

    function setHint(text, isError) {
        hint.textContent = text;
        hint.classList.toggle('text-danger', isError);
        hint.classList.toggle('text-secondary', !isError);
    }

    function render() {
        buttons.forEach(function (btn) {
            const selected = btn.dataset.status === input.value;
            btn.classList.remove('btn-outline-secondary', 'btn-jl-gold', 'btn-secondary');
            btn.classList.add(selected ? selectedClass[btn.dataset.status] : 'btn-outline-secondary');
            btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        setHint(hints[input.value] || '', false);
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Clicking the selected button again clears the choice.
            input.value = input.value === btn.dataset.status ? '' : btn.dataset.status;
            render();
        });
    });

    // A status choice is required: block Send Message until one is selected.
    form.addEventListener('submit', function (e) {
        if (!input.value) {
            e.preventDefault();
            setHint('Choose Invite to Interview or Reject before sending.', true);
        }
    });

    render();
})();
</script>
@endpush
@endsection