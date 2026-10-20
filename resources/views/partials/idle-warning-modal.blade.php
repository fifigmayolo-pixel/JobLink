{{-- Idle warning. Settings for public/js/idle-logout.js live in the data-* attributes. --}}
@auth
<div class="modal fade" id="idleWarningModal" tabindex="-1" role="alertdialog" aria-modal="true"
     aria-labelledby="idleWarningTitle" aria-describedby="idleWarningText"
     data-bs-backdrop="static" data-bs-keyboard="false"
     data-limit-minutes="{{ \App\Support\IdleTimeout::minutesFor(auth()->user()) }}"
     data-warning-seconds="{{ \App\Support\IdleTimeout::WARNING_SECONDS }}"
     data-keep-alive-url="{{ route('session.keep-alive') }}"
     data-expire-url="{{ route('dashboard') }}"
     data-csrf="{{ csrf_token() }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jl-card">
            <div class="modal-body text-center p-4">
                <i class="bi bi-hourglass-split" style="font-size: 2.25rem; color: var(--jl-gold);"></i>
                <h4 class="h5 mt-2" id="idleWarningTitle">Are you still there?</h4>
                <p class="text-secondary mb-4" id="idleWarningText">
                    For your security, you'll be logged out in
                    <strong class="text-dark" data-idle-countdown>1:00</strong>
                    because there has been no activity.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                    <button type="button" class="btn btn-jl-navy px-4" data-idle-stay>Stay logged in</button>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary px-4 w-100">Log out now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endauth