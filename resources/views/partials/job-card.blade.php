{{--
    Job card, Jobstreet-style. Used by the Open Positions list.

    Expects:
      $job          a JobPosting with its company loaded
      $savedJobIds  (optional) ids of the jobs the signed-in user has saved

    The whole card is one link (the title link is stretched over it); the save
    button sits above it. Only students and alumni get a save button, because
    only they can use the saved-jobs routes.
--}}
@php
    $company = $job->company;
    $accent = \App\Support\CompanyAccent::for($company?->id, $company?->company_name);
    $initial = \App\Support\CompanyAccent::initial($company?->company_name);

    $typeLabel = \App\Support\JobCardData::typeLabel($job->job_type);
    $excerpt = \App\Support\JobCardData::excerpt($job);
    $postedLabel = \App\Support\JobCardData::postedLabel($job->date_posted);
    $isNew = \App\Support\JobCardData::isNew($job->date_posted);
    $daysLeft = \App\Support\JobCardData::daysLeft($job->application_deadline);
    $isUrgent = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 14;

    $isSaved = in_array($job->id, $savedJobIds ?? []);
@endphp

@once
@push('styles')
<style>
    /* Grid used by the Open Positions list: as many columns as fit, never narrower than ~300px. */
    .jl-job-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 1rem; }
    .jl-job-grid > .jl-job-card { max-width: none; }

    .jl-job-card {
        position: relative; display: flex; flex-direction: column;
        flex: 1 1 320px; min-width: 0; max-width: 560px;
        background: #fff;
        border: 1px solid rgba(15, 42, 92, 0.10);
        border-left: 4px solid var(--accent, var(--jl-navy));
        border-radius: 0.85rem;
        padding: 1.1rem 1.15rem 1rem;
        box-shadow: 0 1px 3px rgba(15, 42, 92, 0.05);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .jl-job-card:hover, .jl-job-card:focus-within {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 42, 92, 0.12);
    }

    .jl-job-head { display: flex; align-items: flex-start; gap: 0.85rem; }
    .jl-job-logo {
        width: 48px; height: 48px; flex-shrink: 0; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.65rem; font-weight: 700; font-size: 1.15rem;
        background: var(--accent-soft, #E8EDF7); color: var(--accent-text, var(--jl-navy));
    }
    .jl-job-logo img { width: 100%; height: 100%; object-fit: cover; }
    .jl-job-headtext { min-width: 0; flex: 1 1 auto; }
    .jl-job-title { margin: 0; font-family: 'Lora', Georgia, serif; font-size: 1.08rem; font-weight: 700; line-height: 1.3; color: var(--jl-navy); }
    .jl-job-title a { color: inherit; text-decoration: none; }
    .jl-job-title a:hover { text-decoration: underline; }
    .jl-job-title a:focus-visible { outline: none; }
    .jl-job-title a:focus-visible::after { outline: 3px solid var(--jl-gold-soft); outline-offset: -3px; border-radius: 0.85rem; }
    .jl-job-company { margin: 0.15rem 0 0; font-size: 0.88rem; color: var(--jl-slate); }

    .jl-job-save { position: relative; z-index: 2; flex-shrink: 0; margin: -0.25rem -0.35rem 0 0; }
    .jl-job-save button {
        width: 38px; height: 38px; border: 0; border-radius: 50%; background: transparent; color: var(--jl-slate);
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .jl-job-save button:hover { background: rgba(15, 42, 92, 0.08); color: var(--jl-navy); }
    .jl-job-save button.is-saved { color: var(--jl-navy); }

    .jl-job-meta { display: flex; flex-wrap: wrap; gap: 0.35rem 1rem; margin: 0.9rem 0 0; padding: 0; list-style: none; font-size: 0.86rem; color: #4B5563; }
    .jl-job-meta li { display: inline-flex; align-items: center; gap: 0.35rem; }
    .jl-job-meta i { color: var(--jl-slate); }

    .jl-job-pills { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.75rem; }
    .jl-job-pill-type { background: var(--accent-soft, #E8EDF7); color: var(--accent-text, var(--jl-navy)); }
    .jl-job-pill-new { background: #DDF4E7; color: #14683F; }
    .jl-job-pill-urgent { background: #FDECC8; color: #8A5A00; }

    .jl-job-excerpt {
        margin: 0.8rem 0 0; font-size: 0.9rem; line-height: 1.5; color: #4B5563;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    .jl-job-foot { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-top: auto; padding-top: 1rem; }
    .jl-job-posted { font-size: 0.8rem; color: var(--jl-slate); }
    .jl-job-cta {
        display: inline-flex; align-items: center; gap: 0.4rem; flex-shrink: 0;
        padding: 0.45rem 0.95rem; border-radius: 0.5rem;
        background: var(--jl-gold); color: var(--jl-navy-deep); font-size: 0.88rem; font-weight: 600;
        transition: background 0.15s ease;
    }
    .jl-job-cta i { transition: transform 0.15s ease; }
    .jl-job-card:hover .jl-job-cta, .jl-job-card:focus-within .jl-job-cta { background: var(--jl-gold-soft); }
    .jl-job-card:hover .jl-job-cta i, .jl-job-card:focus-within .jl-job-cta i { transform: translateX(3px); }

    @media (max-width: 575.98px) {
        .jl-job-card { padding: 1rem; }
        .jl-job-title { font-size: 1.02rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .jl-job-card, .jl-job-card:hover, .jl-job-card:focus-within { transform: none; transition: none; }
        .jl-job-cta i, .jl-job-card:hover .jl-job-cta i { transform: none; transition: none; }
    }
</style>
@endpush
@endonce

<article class="jl-job-card" style="--accent: {{ $accent['base'] }}; --accent-soft: {{ $accent['soft'] }}; --accent-text: {{ $accent['text'] }};">
    <div class="jl-job-head">
        {{-- Logo; falls back to the company's coloured initial if there is no logo or it fails to load. --}}
        <span class="jl-job-logo" data-initial="{{ $initial }}">
            @if($company?->logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="" loading="lazy"
                     onerror="this.parentNode.textContent = this.parentNode.dataset.initial;">
            @else
                {{ $initial }}
            @endif
        </span>

        <div class="jl-job-headtext">
            <h3 class="jl-job-title"><a href="{{ route('jobs.show', $job) }}" class="stretched-link">{{ $job->title }}</a></h3>
            <p class="jl-job-company">{{ $company?->company_name ?? 'Partner company' }}</p>
        </div>

        @role('student|alumni')
            <form method="POST" action="{{ $isSaved ? route('saved-jobs.destroy', $job) : route('saved-jobs.store', $job) }}" class="jl-job-save">
                @csrf
                @if($isSaved)
                    @method('DELETE')
                @endif
                <button type="submit" class="{{ $isSaved ? 'is-saved' : '' }}"
                        aria-label="{{ $isSaved ? 'Remove from saved jobs' : 'Save job' }}"
                        title="{{ $isSaved ? 'Remove from saved jobs' : 'Save job' }}">
                    <i class="bi {{ $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' }}" aria-hidden="true"></i>
                </button>
            </form>
        @endrole
    </div>

    <ul class="jl-job-meta">
        <li><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ $job->location }}</span></li>
        @if($daysLeft !== null && ! $isUrgent)
            <li><i class="bi bi-calendar-event" aria-hidden="true"></i><span>Apply by {{ $job->application_deadline->format('M j, Y') }}</span></li>
        @endif
    </ul>

    <div class="jl-job-pills">
        @if($typeLabel !== '')
            <span class="jl-pill jl-job-pill-type">{{ $typeLabel }}</span>
        @endif
        @if($isNew)
            <span class="jl-pill jl-job-pill-new">New</span>
        @endif
        @if($isUrgent)
            <span class="jl-pill jl-job-pill-urgent">
                {{ $daysLeft === 0 ? 'Closes today' : $daysLeft.' '.\Illuminate\Support\Str::plural('day', $daysLeft).' left' }}
            </span>
        @endif
    </div>

    @if($excerpt !== '')
        <p class="jl-job-excerpt">{{ $excerpt }}</p>
    @endif

    <div class="jl-job-foot">
        <span class="jl-job-posted">{{ $postedLabel }}</span>
        {{-- Looks like a button; the real link is the stretched title above. --}}
        <span class="jl-job-cta" aria-hidden="true">View job <i class="bi bi-arrow-right"></i></span>
    </div>
</article>
