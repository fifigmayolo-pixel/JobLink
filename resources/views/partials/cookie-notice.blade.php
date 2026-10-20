{{-- First-visit notice. JobLink only sets strictly necessary cookies, so this is an
     information notice with a "Got it" button, not an Accept/Decline consent banner.
     Dismissal is remembered in localStorage (not a cookie). --}}
<div id="cookieNotice" class="jl-cookie-notice d-none" role="region" aria-label="Cookie notice">
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 gap-md-3">
        <p class="mb-0 small flex-grow-1">
            <i class="bi bi-shield-check me-1" style="color: var(--jl-gold-soft);"></i>
            JobLink uses only the cookies needed to keep you logged in and your forms secure.
            <a href="{{ route('cookies') }}">Cookie Policy</a>
        </p>
        <button type="button" class="btn btn-jl-gold btn-sm px-4" id="cookieNoticeDismiss">Got it</button>
    </div>
</div>

<style>
    .jl-cookie-notice {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1040;
        background: var(--jl-navy-deep);
        color: #fff;
        padding: 0.85rem 1.25rem;
        border-top: 3px solid var(--jl-gold);
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.15);
    }
    .jl-cookie-notice a { color: var(--jl-gold-soft); }
</style>

<script>
(function () {
    var KEY = 'jl:cookieNoticeSeen';
    var el = document.getElementById('cookieNotice');
    if (!el) { return; }

    try { if (localStorage.getItem(KEY) === '1') { return; } } catch (e) { /* storage blocked: show it */ }

    el.classList.remove('d-none');

    document.getElementById('cookieNoticeDismiss').addEventListener('click', function () {
        try { localStorage.setItem(KEY, '1'); } catch (e) { /* ignore */ }
        el.classList.add('d-none');
    });
})();
</script>