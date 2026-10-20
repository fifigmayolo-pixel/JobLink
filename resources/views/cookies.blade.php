@extends('layouts.app')

@section('title', 'Cookie Policy')

@section('content')
<div style="width: 100%; max-width: 860px; margin: 0 auto;">

    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
       class="btn btn-outline-secondary btn-sm mb-3 js-legal-back">&larr; Back</a>

    <h1 class="h2 mb-1">Cookie Policy</h1>
    <p class="text-secondary mb-4">Last updated: [DATE]</p>

    <div class="jl-card p-4 p-md-5">
        <p>
            Cookies are small files a website saves in your browser. JobLink uses only the cookies
            it needs to work. We do not use advertising, analytics or tracking cookies.
        </p>

        <h2 class="h5 mt-4">1. Cookies we set</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">What it does</th>
                        <th scope="col">How long it lasts</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>{{ config('session.cookie') }}</code></td>
                        <td>Keeps you logged in and remembers your place while you use JobLink. You cannot log in without it.</td>
                        <td>
                            @if(config('session.expire_on_close'))
                                Removed when you close your browser.
                            @else
                                Expires {{ config('session.lifetime') }} minutes after your last visit.
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><code>XSRF-TOKEN</code></td>
                        <td>A security token that protects forms from being submitted by other websites on your behalf.</td>
                        <td>
                            @if(config('session.expire_on_close'))
                                Removed when you close your browser.
                            @else
                                Expires {{ config('session.lifetime') }} minutes after your last visit.
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>
            Both are strictly necessary for the site to work, so there is no Accept or Decline choice.
            You can block cookies in your browser settings, but you will not be able to log in.
        </p>

        <h2 class="h5 mt-4">2. The cookie notice</h2>
        <p>
            When you click "Got it" on the notice, a small note is saved in your browser's local
            storage (not in a cookie) so the notice does not appear again. Clearing your browser's
            site data brings it back.
        </p>

        <h2 class="h5 mt-4">3. Automatic logout</h2>
        <p>
            To protect your account, JobLink logs you out after {{ \App\Support\IdleTimeout::DEFAULT_MINUTES }}
            minutes without activity ({{ \App\Support\IdleTimeout::COORDINATOR_MINUTES }} minutes for the
            Job Coordinator). A warning appears {{ \App\Support\IdleTimeout::WARNING_SECONDS }} seconds
            before it happens, and you can choose to stay logged in.
        </p>

        <h2 class="h5 mt-4">4. Other services your browser contacts</h2>
        <p>
            To display pages, your browser loads fonts from Google Fonts and styling and icon files
            from the jsDelivr content network. Like any website you visit, these providers can see
            your IP address and browser details when your browser fetches those files. We do not send
            them any information from your account.
        </p>

        <h2 class="h5 mt-4">5. Changes and questions</h2>
        <p class="mb-0">
            If JobLink ever adds analytics or other optional cookies, this page will be updated and
            you will be asked for your choice first. Questions can go to the Data Protection Officer:
            [NAME AND EMAIL]. See also our <a href="{{ route('privacy') }}">Privacy Policy</a>.
        </p>
    </div>

</div>

@push('scripts')
<script>
document.querySelectorAll('.js-legal-back').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var fallback = this.href;
        var fromSite = document.referrer && document.referrer.indexOf(location.origin) === 0;

        if (!fromSite) return;            // typed or bookmarked: just follow the link

        e.preventDefault();
        if (history.length > 1) {         // came from a page in this tab
            history.back();
        } else {                          // opened in a new tab
            window.close();
            setTimeout(function () { window.location.href = fallback; }, 150);
        }
    });
});
</script>
@endpush
@endsection