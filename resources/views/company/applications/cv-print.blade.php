<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $cv['personal']['full_name'] ?? 'Applicant' }} &middot; CV</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --jl-navy: #0F2A5C;
            --jl-navy-deep: #081B3D;
            --jl-gold: #C9A227;
            --jl-gold-soft: #E4C766;
            --jl-cream: #F5F7FA;
            --jl-ink: #1C1E26;
            --jl-slate: #6B7280;
            --jl-rose: #B4553F;
        }
        body { background: var(--jl-cream); color: var(--jl-ink); font-family: 'Inter', sans-serif; }
        .cv-toolbar { max-width: 820px; margin: 1.25rem auto 0; padding: 0 1rem; }
        .cv-sheet {
            max-width: 820px;
            margin: 1rem auto 2rem;
            background: #fff;
            border: 1px solid rgba(15, 42, 92, 0.1);
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(15, 42, 92, 0.06);
            padding: 2.5rem;
        }
        .btn-print { background: var(--jl-gold); color: var(--jl-navy-deep); border: 0; font-weight: 600; }
        .btn-print:hover { background: var(--jl-gold-soft); color: var(--jl-navy-deep); }
        @media (max-width: 575.98px) { .cv-sheet { padding: 1.5rem 1.25rem; } }

        @page { size: A4; margin: 14mm; }
        @media print {
            body { background: #fff; }
            .cv-toolbar { display: none !important; }
            .cv-sheet { max-width: none; margin: 0; border: 0; border-radius: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="cv-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="{{ route('company.applications.show', $application) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to application
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="small text-secondary d-none d-sm-inline">Choose "Save as PDF" in the print window to keep a copy.</span>
            <button type="button" class="btn btn-print btn-sm" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <main class="cv-sheet">
        @include('cv._document', ['cv' => $cv])
    </main>
</body>
</html>
