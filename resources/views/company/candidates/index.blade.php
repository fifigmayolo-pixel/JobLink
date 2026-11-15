@extends('layouts.app')

@section('title', 'Find Candidates')

@push('styles')
<style>
    .jl-cand-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 330px), 1fr)); gap: 1rem; }
    .jl-cand-card {
        position: relative; display: flex; flex-direction: column;
        background: #fff; border: 1px solid rgba(15, 42, 92, 0.10); border-radius: 0.85rem;
        padding: 1.1rem 1.15rem 1rem; box-shadow: 0 1px 3px rgba(15, 42, 92, 0.05);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .jl-cand-card:hover, .jl-cand-card:focus-within { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15, 42, 92, 0.12); }
    .jl-cand-head { display: flex; align-items: flex-start; gap: 0.85rem; }
    .jl-cand-avatar {
        width: 46px; height: 46px; flex-shrink: 0; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(15, 42, 92, 0.08); color: var(--jl-navy); font-weight: 700;
    }
    .jl-cand-name { margin: 0; font-family: 'Lora', Georgia, serif; font-size: 1.05rem; font-weight: 700; line-height: 1.3; color: var(--jl-navy); }
    .jl-cand-name a { color: inherit; text-decoration: none; }
    .jl-cand-name a:hover { text-decoration: underline; }
    .jl-cand-name a:focus-visible { outline: none; }
    .jl-cand-name a:focus-visible::after { outline: 3px solid var(--jl-gold-soft); outline-offset: -3px; border-radius: 0.85rem; }
    .jl-cand-sub { margin: 0.1rem 0 0; font-size: 0.86rem; color: var(--jl-slate); }
    .jl-cand-place { margin: 0.75rem 0 0; font-size: 0.86rem; color: #4B5563; }
    .jl-cand-excerpt {
        margin: 0.7rem 0 0; font-size: 0.9rem; line-height: 1.5; color: #4B5563;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .jl-cand-skills { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.8rem; }
    .jl-cand-skill { font-size: 0.78rem; font-weight: 600; padding: 0.2rem 0.65rem; border-radius: 50rem; background: rgba(15, 42, 92, 0.08); color: var(--jl-navy); }
    .jl-cand-skill.is-match { background: var(--jl-gold-soft); color: var(--jl-navy-deep); }
    .jl-cand-foot { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-top: auto; padding-top: 1rem; }
    .jl-cand-match { font-size: 0.8rem; color: var(--jl-slate); }
    @media (prefers-reduced-motion: reduce) { .jl-cand-card, .jl-cand-card:hover, .jl-cand-card:focus-within { transform: none; transition: none; } }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-1">Find Candidates</h1>
    <p class="text-secondary mb-0">Search the CVs of students and alumni who chose to be found by partner companies.</p>
</div>

<div class="alert alert-light jl-card mb-3 small" style="border-left: 4px solid var(--jl-gold) !important;" role="note">
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
    Only people who turned on <strong>&ldquo;Make my profile searchable by partner companies&rdquo;</strong> appear here.
    You see their CV, not their email or mobile number. Contact details are shared when they apply to one of your jobs.
    Each profile you open is recorded in the audit log.
</div>

<form method="GET" action="{{ route('company.candidates.index') }}" class="jl-card p-3 mb-3" role="search">
    <div class="row g-2">
        <div class="col-md-4">
            <label class="visually-hidden" for="candQ">Keywords</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" id="candQ" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="form-control"
                       placeholder="Keywords, e.g. web developer" autocomplete="off">
            </div>
        </div>
        <div class="col-md-3">
            <label class="visually-hidden" for="candSkills">Skills</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-lightning-charge" aria-hidden="true"></i></span>
                <input type="text" id="candSkills" name="skills" value="{{ $filters['skills'] ?? '' }}" maxlength="200" class="form-control"
                       placeholder="Skills: PHP, Excel" autocomplete="off">
            </div>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="visually-hidden" for="candCourse">Course</label>
            <select id="candCourse" name="course" class="form-select">
                <option value="">All courses</option>
                @foreach(\App\Models\StudentAlumniProfile::PROGRAMS as $program)
                    <option value="{{ $program }}" @selected(($filters['course'] ?? '') === $program)>{{ $program }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-lg-1">
            <label class="visually-hidden" for="candStatus">Student or alumni</label>
            <select id="candStatus" name="status" class="form-select">
                <option value="">Any</option>
                <option value="student" @selected(($filters['status'] ?? '') === 'student')>Students</option>
                <option value="alumni" @selected(($filters['status'] ?? '') === 'alumni')>Alumni</option>
            </select>
        </div>
        <div class="col-12 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-jl-navy flex-grow-1">Search</button>
            @if($filtersActive)
                <a href="{{ route('company.candidates.index') }}" class="btn btn-outline-secondary" title="Clear filters" aria-label="Clear filters">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="form-text mt-2">Several keywords must all appear in the CV. Several skills (separated by commas) match anyone who has at least one, best matches first.</div>
</form>

@if($candidates->total() > 0)
    <p class="text-secondary small mb-3">
        {{ $candidates->total() }} candidate{{ $candidates->total() === 1 ? '' : 's' }}@if($filtersActive) matching your search @endif
    </p>

    <div class="jl-cand-grid">
        @foreach($candidates as $candidate)
            @php
                $shownSkills = array_slice($candidate['skills'], 0, 8);
                $moreSkills = count($candidate['skills']) - count($shownSkills);
                $isMatch = function ($skill) use ($skillTerms) {
                    foreach ($skillTerms as $term) {
                        if (str_contains(mb_strtolower($skill), $term)) {
                            return true;
                        }
                    }
                    return false;
                };
            @endphp
            <article class="jl-cand-card">
                <div class="jl-cand-head">
                    <span class="jl-cand-avatar" aria-hidden="true">{{ \App\Support\CompanyAccent::initial($candidate['name']) }}</span>
                    <div style="min-width: 0;">
                        <h2 class="jl-cand-name"><a href="{{ route('company.candidates.show', $candidate['id']) }}" class="stretched-link">{{ $candidate['name'] }}</a></h2>
                        <p class="jl-cand-sub">
                            {{ $candidate['course'] }}@if($candidate['year']) &middot; {{ $candidate['year'] }}@endif
                        </p>
                    </div>
                    <span class="jl-pill jl-pill-neutral ms-auto flex-shrink-0">{{ ucfirst($candidate['status']) }}</span>
                </div>

                @if($candidate['place'] !== '')
                    <p class="jl-cand-place"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $candidate['place'] }}</p>
                @endif

                @if($candidate['excerpt'] !== '')
                    <p class="jl-cand-excerpt">{{ $candidate['excerpt'] }}</p>
                @endif

                @if(count($shownSkills))
                    <div class="jl-cand-skills">
                        @foreach($shownSkills as $skill)
                            <span class="jl-cand-skill {{ $isMatch($skill) ? 'is-match' : '' }}">{{ $skill }}</span>
                        @endforeach
                        @if($moreSkills > 0)
                            <span class="jl-cand-skill">+{{ $moreSkills }} more</span>
                        @endif
                    </div>
                @endif

                <div class="jl-cand-foot">
                    <span class="jl-cand-match">
                        @if(count($skillTerms) && $candidate['matched_skills'] > 0)
                            {{ $candidate['matched_skills'] }} matching {{ \Illuminate\Support\Str::plural('skill', $candidate['matched_skills']) }}
                        @endif
                    </span>
                    <span class="btn btn-jl-navy btn-sm" aria-hidden="true">View profile <i class="bi bi-arrow-right"></i></span>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $candidates->links() }}
    </div>
@else
    <div class="jl-card p-5 text-center">
        <span class="jl-section-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
            <i class="bi {{ $filtersActive ? 'bi-search' : 'bi-people' }}" aria-hidden="true"></i>
        </span>
        <h2 class="h5 mb-1">{{ $filtersActive ? 'No candidates match your search' : 'No searchable profiles yet' }}</h2>
        <p class="text-secondary mb-3">
            {{ $filtersActive
                ? 'Try fewer keywords or another skill, or clear the filters.'
                : 'Students and alumni appear here only after they turn on "Make my profile searchable by partner companies".' }}
        </p>
        @if($filtersActive)
            <a href="{{ route('company.candidates.index') }}" class="btn btn-jl-navy">Clear filters</a>
        @endif
    </div>
@endif
@endsection
