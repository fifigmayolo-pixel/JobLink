{{-- resources/views/partials/page-loader.blade.php --}}
{{-- Include right after <body> in layouts/app.blade.php:
     @include('partials.page-loader') --}}

<div id="page-loader" class="is-active" role="status" aria-live="polite" aria-label="Loading">
    <div class="pl-stage">
        <img src="{{ asset('images/logo.png') }}" alt="" class="pl-logo">
        <div class="pl-shadow"></div>
    </div>
</div>

<noscript><style>#page-loader{display:none !important;}</style></noscript>

<style>
    #page-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(10, 25, 60, .38);   /* navy tint; lower the .38 for more transparency */
        -webkit-backdrop-filter: blur(3px);
        backdrop-filter: blur(3px);
        opacity: 0;
        visibility: hidden;
        transition: opacity .25s ease, visibility .25s ease;
    }
    #page-loader.is-active {
        opacity: 1;
        visibility: visible;
    }

    #page-loader .pl-stage {
        height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
    }

    /* Logo: rises, falls, and squashes slightly when it lands */
    #page-loader .pl-logo {
        width: 120px;
        height: 120px;
        object-fit: contain;
        transform-origin: center bottom;
        animation: pl-bounce .9s infinite;
    }

    /* Ground shadow: shrinks and fades while the logo is in the air */
    #page-loader .pl-shadow {
        width: 90px;
        height: 12px;
        margin-top: 8px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .4);
        animation: pl-shadow .9s infinite;
    }

    @keyframes pl-bounce {
        0%, 100% { transform: translateY(0) scale(1.06, .92); animation-timing-function: cubic-bezier(.2, .7, .4, 1); }
        50%      { transform: translateY(-44px) scale(1, 1);   animation-timing-function: cubic-bezier(.6, 0, .8, .3); }
    }
    @keyframes pl-shadow {
        0%, 100% { transform: scaleX(1);  opacity: .4;  animation-timing-function: cubic-bezier(.2, .7, .4, 1); }
        50%      { transform: scaleX(.55); opacity: .15; animation-timing-function: cubic-bezier(.6, 0, .8, .3); }
    }

    @media (prefers-reduced-motion: reduce) {
        #page-loader .pl-logo, #page-loader .pl-shadow { animation: none; }
    }
</style>

<script>
    (function () {
        var loader = document.getElementById('page-loader');
        if (!loader) return;

        var failsafe;

        function show() {
            loader.classList.add('is-active');
            clearTimeout(failsafe);
            failsafe = setTimeout(hide, 15000); // never get stuck (e.g. file downloads)
        }

        function hide() {
            loader.classList.remove('is-active');
            clearTimeout(failsafe);
        }

        // First page load: overlay starts visible, fades out when the page is ready
        window.addEventListener('load', function () { setTimeout(hide, 150); });

        // Back/forward button restores the page from cache with the loader still on
        window.addEventListener('pageshow', function (e) { if (e.persisted) hide(); });

        // Show while navigating via links
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var a = e.target.closest('a[href]');
            if (!a) return;

            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(href)) return;
            if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-loader') || a.hasAttribute('data-bs-toggle')) return;
            if (a.origin !== window.location.origin) return;

            // Same-page anchor jumps
            if (a.pathname === window.location.pathname && a.search === window.location.search && a.hash) return;

            // File downloads don't leave the page, so the loader would never go away
            if (/\/(resume|export)(\/|$)/i.test(a.pathname)) return;

            show();
        });

        // Show while submitting forms (only fires when the browser validation passed)
        document.addEventListener('submit', function (e) {
            var f = e.target;
            if (e.defaultPrevented || f.hasAttribute('data-no-loader') || f.target === '_blank') return;
            show();
        });

        // Optional manual control, e.g. window.PageLoader.show() around a fetch()
        window.PageLoader = { show: show, hide: hide };
    })();
</script>