{{-- resources/views/errors/shell.blade.php
     Standalone on purpose: no layouts.app, no auth(), no database queries,
     so it still renders when the error IS the database or the session. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · JobLink · PSU Alaminos City</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --jl-navy: #0F2A5C;
            --jl-navy-deep: #081B3D;
            --jl-gold: #C9A227;
            --jl-gold-soft: #E4C766;
            --jl-cream: #F5F7FA;
            --jl-ink: #1C1E26;
            --jl-slate: #6B7280;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            display: flex;
            flex-direction: column;
            background: var(--jl-cream);
            color: var(--jl-ink);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            border-top: 4px solid var(--jl-gold);
        }
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem 1.25rem;
        }
        .err-logo { width: 64px; height: 64px; object-fit: contain; margin-bottom: 1.25rem; }
        .err-code {
            margin: 0;
            font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
            font-weight: 700;
            font-size: clamp(5.5rem, 24vw, 10rem);
            line-height: 1;
            letter-spacing: -0.03em;
            color: var(--jl-gold);
        }
        h1 {
            margin: 0.35rem 0 0.75rem;
            font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
            font-weight: 600;
            font-size: clamp(1.5rem, 4.5vw, 2rem);
            letter-spacing: -0.01em;
            color: var(--jl-navy);
        }
        .err-message {
            max-width: 46ch;
            margin: 0 auto 1.75rem;
            line-height: 1.6;
            color: var(--jl-slate);
        }
        .err-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .err-btn {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            font: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
            text-decoration: none;
            cursor: pointer;
        }
        .err-btn-gold { background: var(--jl-gold); border-color: var(--jl-gold); color: var(--jl-navy-deep); }
        .err-btn-gold:hover { background: var(--jl-gold-soft); border-color: var(--jl-gold-soft); }
        .err-btn-line { background: transparent; border-color: var(--jl-navy); color: var(--jl-navy); }
        .err-btn-line:hover { background: var(--jl-navy); color: #fff; }
        .err-btn:focus-visible { outline: 3px solid var(--jl-gold); outline-offset: 2px; }
        footer { padding: 1.25rem; text-align: center; font-size: 0.85rem; color: var(--jl-slate); border-top: 1px solid rgba(15, 42, 92, 0.08); }
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="err-logo">
        <p class="err-code" aria-hidden="true">@yield('code')</p>
        <h1><span class="sr-only">Error @yield('code'): </span>@yield('title')</h1>
        <p class="err-message">@yield('message')</p>

        <div class="err-actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <button type="button" class="err-btn err-btn-line" onclick="if (history.length > 1) { history.back(); } else { location.href = '{{ url('/') }}'; }">Go back</button>
                <a href="{{ url('/') }}" class="err-btn err-btn-gold">Back to JobLink</a>
            @endif
        </div>
    </main>

    <footer>JobLink · Pangasinan State University, Alaminos City Campus</footer>
</body>
</html>