@extends('layouts.app')

@section('title', 'My Clearances')

@section('content')
@php
    $maxMb = rtrim(rtrim(number_format($maxKb / 1024, 1), '0'), '.');
@endphp

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <a href="{{ route('cv.edit') }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to My CV
        </a>

        <h1 class="h2 mb-1">My Clearances</h1>
        <p class="text-secondary mb-4">
            You need both clearances to apply for a job. Upload each one as a PDF and enter the date it is valid until.
        </p>

        {{-- Who can open these files --}}
        <div class="jl-card p-4 mb-4" style="border-left: 4px solid var(--jl-gold) !important;">
            <h2 class="h6 mb-2"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Who can open your clearances</h2>
            <ul class="small mb-2">
                <li>You can, any time.</li>
                <li>A company can, only while you have an application to one of its jobs. It loses access if you withdraw the application.</li>
                <li>The Job Coordinator, other companies and other students cannot open them. They are not shown when companies search for candidates.</li>
            </ul>
            <p class="small text-secondary mb-0">
                The files are kept in private storage with no public link. Each time a company opens one, JobLink records it.
                When you replace or remove a file, the old one is deleted. See our <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
            </p>
        </div>

        @foreach($types as $type => $meta)
            @php
                $clearance = $clearances->get($type);
                $submitted = old('doc') === $type;
            @endphp

            <div class="jl-card p-4 mb-4" id="clearance-{{ $type }}">
                <div class="d-flex align-items-start gap-2 mb-3 flex-wrap">
                    <span class="jl-section-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="h6 text-uppercase text-secondary mb-0">{{ $meta['label'] }}</h2>
                        <p class="small text-secondary mb-0 mt-1">Required to apply.</p>
                    </div>
                    <span class="ms-auto">@include('partials.clearance-badge', ['clearance' => $clearance])</span>
                </div>

                @if($clearance)
                    @if($clearance->isExpired())
                        <div class="alert alert-light jl-card py-2 small mb-3" style="border-left: 4px solid var(--jl-rose) !important;" role="alert">
                            This clearance has expired, so you cannot apply for jobs until you upload a new one.
                        </div>
                    @endif

                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                        <span class="small text-secondary">
                            Uploaded {{ $clearance->updated_at->format('M j, Y') }} &middot; PDF, {{ $clearance->sizeLabel() }}
                        </span>
                        <a href="{{ route('clearances.show', $clearance) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm ms-sm-auto">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> View PDF
                        </a>
                        <form method="POST" action="{{ route('clearances.destroy', $type) }}"
                              onsubmit="return confirm('Remove your {{ $meta['sentence'] }}? The file is deleted and you cannot apply for jobs until you upload a new one.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                        </form>
                    </div>
                    <hr class="jl-rule">
                @endif

                {{-- One file per request: Vercel rejects a request over about 4.5 MB. --}}
                <form method="POST" action="{{ route('clearances.store', $type) }}" enctype="multipart/form-data" class="js-clearance-form" novalidate>
                    @csrf
                    <input type="hidden" name="doc" value="{{ $type }}">

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="file-{{ $type }}">{{ $clearance ? 'Replace the PDF' : 'PDF file' }}</label>
                            <input type="file" id="file-{{ $type }}" name="file" accept="application/pdf,.pdf"
                                   class="form-control js-clearance-file @if($submitted) @error('file') is-invalid @enderror @endif"
                                   data-max-bytes="{{ $maxKb * 1024 }}" data-max-label="{{ $maxMb }} MB" required>
                            <div class="form-text">PDF only, up to {{ $maxMb }} MB.</div>
                            <div class="small text-danger mt-1 d-none js-clearance-size-error" role="alert"></div>
                            @if($submitted)
                                @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="valid-{{ $type }}">Valid until</label>
                            <input type="date" id="valid-{{ $type }}" name="valid_until"
                                   value="{{ $submitted ? old('valid_until') : '' }}"
                                   min="{{ today()->toDateString() }}" max="{{ $latestDate }}"
                                   class="form-control @if($submitted) @error('valid_until') is-invalid @enderror @endif" required>
                            <div class="form-text">The date printed on the clearance.</div>
                            @if($submitted)
                                @error('valid_until') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        </div>
                    </div>

                    @if($needsConsent)
                        <div class="form-check mt-3">
                            <input class="form-check-input @if($submitted) @error('consent') is-invalid @enderror @endif" type="checkbox"
                                   id="consent-{{ $type }}" name="consent" value="1" @checked($submitted && old('consent'))>
                            <label class="form-check-label small" for="consent-{{ $type }}">
                                I agree that JobLink stores my clearances and that every company I apply to can open them while my application is active.
                            </label>
                            @if($submitted)
                                @error('consent') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        </div>
                    @endif

                    <button type="submit" class="btn btn-jl-gold mt-3 js-clearance-submit">
                        {{ $clearance ? 'Replace '.$meta['sentence'] : 'Upload '.$meta['sentence'] }}
                    </button>
                </form>
            </div>
        @endforeach

        @unless($needsConsent)
            <p class="small text-secondary">
                You agreed on {{ $profile->clearance_consent_at->format('M j, Y') }} that companies you apply to can open your clearances.
                Removing a clearance deletes its file.
            </p>
        @endunless
    </div>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-clearance-form').forEach(function (form) {
        var input = form.querySelector('.js-clearance-file');
        var error = form.querySelector('.js-clearance-size-error');
        var submit = form.querySelector('.js-clearance-submit');
        var label = submit.innerHTML;
        var sending = false;

        function tooBig() {
            var file = input.files && input.files[0];
            return !!file && file.size > parseInt(input.dataset.maxBytes, 10);
        }

        // Catch a file that is too large before it is sent: the server and Vercel refuse it anyway.
        input.addEventListener('change', function () {
            if (tooBig()) {
                error.textContent = 'That file is too large. The limit is ' + input.dataset.maxLabel + '.';
                error.classList.remove('d-none');
                input.value = '';
            } else {
                error.classList.add('d-none');
            }
        });

        form.addEventListener('submit', function (e) {
            if (sending) { e.preventDefault(); return; }
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return;
            }
            sending = true;
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Uploading...';
        });

        // Back button after an upload: undo the locked state.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { sending = false; submit.disabled = false; submit.innerHTML = label; }
        });
    });
})();
</script>
@endpush
@endsection
