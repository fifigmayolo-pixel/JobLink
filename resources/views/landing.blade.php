@extends('layouts.app')

@section('title', 'JobLink — PSU Alaminos City Career Placement Portal')

@push('styles')
<style>
    /* Full-bleed trick, same as login.blade.php: cancel <main class="jl-content">'s
       padding so the bands run edge-to-edge; inner wrappers re-center the content. */
    .jl-landing-breakout {
        margin: -1.75rem;
        font-family: 'Inter', sans-serif;
        color: var(--jl-ink);
        background: var(--jl-cream);
    }
    .jl-landing-container { max-width: 1120px; margin: 0 auto; padding: 0 24px; }
    /* Bootstrap underlines links by default; keep this page clean. */
    .jl-landing-breakout a { text-decoration: none; }

    /* Campus photo + navy overlay (replaces the flat navy bands).
       Raise/lower the two alpha values to make the photo more/less visible. */
    .jl-landing-photo-bg {
        background:
            linear-gradient(rgba(8,27,61,0.78), rgba(8,27,61,0.90)),
            url('{{ asset('images/campus-photo.jpg') }}') center / cover no-repeat;
        background-color: var(--jl-navy-deep);
        color: #fff;
    }

    /* ===== Header ===== */
    .jl-landing-header-inner {
        padding: 16px 24px; display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
    }
    .jl-landing-logo {
        display: flex; align-items: center; gap: 12px; color: #fff;
        font-family: 'Lora', Georgia, serif; font-weight: 700; font-size: 22px;
    }
    .jl-landing-logo:hover { color: #fff; }
    .jl-landing-logo img { height: 44px; width: auto; display: block; }
    .jl-landing-nav { margin-left: auto; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
    .jl-landing-nav a { color: #E5E9F2; font-size: 15px; }
    .jl-landing-nav a:hover { color: #fff; }
    .jl-landing-nav-login { color: #fff !important; font-weight: 600; }
    .jl-landing-nav-cta {
        background: var(--jl-gold); color: var(--jl-navy-deep) !important; font-weight: 600;
        padding: 10px 18px; border-radius: 8px;
    }
    .jl-landing-nav-cta:hover { background: var(--jl-gold-soft); }

    /* ===== Hero ===== */
    .jl-landing-hero-inner {
        padding: 56px 24px 104px; display: flex; flex-wrap: wrap; gap: 48px; align-items: center;
    }
    .jl-landing-hero-copy { flex: 1 1 420px; }
    .jl-landing-hero-eyebrow {
        margin: 0 0 16px; color: var(--jl-gold-soft); font-size: 14px; font-weight: 600;
        letter-spacing: 0.08em; text-transform: uppercase;
    }
    .jl-landing-hero-title {
        margin: 0 0 20px; font-family: 'Lora', Georgia, serif; font-size: 56px;
        line-height: 1.1; font-weight: 700; color: #fff;
    }
    .jl-landing-hero-sub { margin: 0 0 32px; font-size: 19px; line-height: 1.6; color: #E5E9F2; max-width: 520px; }
    .jl-landing-hero-ctas { display: flex; gap: 12px; flex-wrap: wrap; }
    .jl-landing-btn-solid {
        background: var(--jl-gold); color: var(--jl-navy-deep); font-weight: 600;
        padding: 14px 26px; border-radius: 8px; display: inline-block;
    }
    .jl-landing-btn-solid:hover { background: var(--jl-gold-soft); color: var(--jl-navy-deep); }
    .jl-landing-btn-outline {
        border: 1.5px solid var(--jl-gold-soft); color: #fff; font-weight: 600;
        padding: 12px 26px; border-radius: 8px; display: inline-block;
    }
    .jl-landing-btn-outline:hover { background: rgba(255,255,255,0.08); color: #fff; }

    .jl-landing-hero-preview { flex: 1 1 340px; max-width: 460px; display: flex; flex-direction: column; gap: 16px; }
    .jl-landing-preview-card { background: #fff; color: var(--jl-ink); border-radius: 12px; padding: 24px; }
    .jl-landing-preview-card-head { display: flex; gap: 14px; align-items: center; }
    .jl-landing-preview-logo {
        width: 48px; height: 48px; border-radius: 8px; flex-shrink: 0; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: var(--accent-soft, #E8EDF7); color: var(--accent-text, var(--jl-navy));
        font-weight: 700; font-size: 20px;
    }
    .jl-landing-preview-logo img { width: 100%; height: 100%; object-fit: cover; }
    .jl-landing-preview-card-head + .jl-landing-preview-apply { margin-top: 16px; }
    .jl-landing-preview-apply:hover { background: var(--jl-gold-soft); color: var(--jl-navy-deep); }

    /* Rotating job card: slides share one grid cell so the card keeps the
       height of the tallest slide and nothing jumps when it changes. */
    .jl-landing-rotator-stage { display: grid; }
    .jl-landing-slide { grid-area: 1 / 1; opacity: 0; visibility: hidden; transition: opacity .4s ease, visibility 0s .4s; }
    .jl-landing-slide.is-active { opacity: 1; visibility: visible; transition: opacity .4s ease; }
    .jl-landing-rotator-foot {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 12px; padding-top: 8px; border-top: 1px solid #EEF1F6;
    }
    .jl-landing-dots { display: flex; }
    .jl-landing-dot {
        width: 22px; height: 22px; padding: 6px; border: 0; border-radius: 50%; cursor: pointer;
        background: #CBD3E1; background-clip: content-box;
    }
    .jl-landing-dot.is-active { background-color: var(--jl-navy); }
    .jl-landing-rotator-toggle { border: 0; background: none; color: #4B5563; font-size: 13px; padding: 4px 6px; border-radius: 6px; }
    .jl-landing-rotator-toggle:hover { color: var(--jl-navy); text-decoration: underline; }
    @media (prefers-reduced-motion: reduce) {
        .jl-landing-slide, .jl-landing-slide.is-active { transition: none; }
    }
    .jl-landing-preview-card-head h4 { margin: 0; font-family: 'Lora', Georgia, serif; font-weight: 700; font-size: 19px; color: var(--jl-navy); }
    .jl-landing-preview-card-head p { margin: 0; font-size: 14px; color: #4B5563; }
    .jl-landing-preview-pills { display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; font-size: 13px; }
    .jl-landing-preview-pills span { background: #EEF1F6; padding: 4px 10px; border-radius: 99px; }
    .jl-landing-preview-apply {
        display: block; text-align: center; background: var(--jl-gold); color: var(--jl-navy-deep);
        font-weight: 600; padding: 12px; border-radius: 8px;
    }
    .jl-landing-app-card {
        background: #fff; color: var(--jl-ink); border-radius: 12px; padding: 16px 24px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .jl-landing-app-card-label { font-size: 13px; color: #4B5563; }
    .jl-landing-app-card-title { font-weight: 600; color: var(--jl-navy); }
    .jl-landing-status-pill {
        background: #F8EFCF; color: #6B5310; font-size: 13px; font-weight: 600;
        padding: 5px 12px; border-radius: 99px; flex-shrink: 0;
    }

    /* ===== Promo video (inline, autoplay, muted, looping) ===== */
    .jl-landing-video { max-width: 960px; margin: -56px auto 0; padding: 0 24px; position: relative; }
    .jl-landing-video-card { background: #fff; border-radius: 20px; padding: 16px; box-shadow: 0 12px 32px rgba(8,27,61,0.18); }
    .jl-landing-video-frame {
        position: relative; aspect-ratio: 16/9; background: var(--jl-navy-deep);
        border-radius: 12px; overflow: hidden;
    }
    .jl-landing-video-frame video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .jl-landing-video-controls {
        position: absolute; right: 14px; bottom: 14px; display: flex; gap: 8px;
    }
    .jl-landing-video-btn {
        width: 44px; height: 44px; border-radius: 50%; border: 0; cursor: pointer;
        background: rgba(8,27,61,0.72); color: #fff;
        display: flex; align-items: center; justify-content: center;
    }
    .jl-landing-video-btn:hover { background: var(--jl-gold); color: var(--jl-navy-deep); }
    .jl-landing-video-btn:focus-visible { outline: 3px solid var(--jl-gold-soft); outline-offset: 2px; }
    .jl-landing-video-btn svg { display: block; }
    .jl-landing-video-meta {
        display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        padding: 14px 8px 4px; color: #4B5563; font-size: 14px;
    }

    /* ===== Shared section header + card grid ===== */
    .jl-landing-section-header h2 { margin: 0 0 8px; font-family: 'Lora', Georgia, serif; font-size: 36px; color: var(--jl-navy); }
    .jl-landing-section-header p { margin: 0 0 40px; color: #4B5563; font-size: 17px; }
    .jl-landing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; }

    .jl-landing-features { padding: 72px 24px 8px; }
    .jl-landing-how { padding: 80px 24px 24px; }
    .jl-landing-who { padding: 64px 24px 80px; }

    .jl-landing-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 10px rgba(15,42,92,0.06); }
    .jl-landing-card-icon {
        width: 44px; height: 44px; border-radius: 50%; background: #E8EDF7;
        display: flex; align-items: center; justify-content: center; margin-bottom: 16px;
    }
    .jl-landing-card h3 { margin: 0 0 8px; font-family: 'Lora', Georgia, serif; font-size: 21px; color: var(--jl-navy); }
    .jl-landing-card p { margin: 0; line-height: 1.6; color: #4B5563; }

    .jl-landing-step-num {
        width: 40px; height: 40px; border-radius: 50%; background: var(--jl-gold); color: var(--jl-navy-deep);
        font-weight: 700; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;
    }
    .jl-landing-how .jl-landing-card h3 { font-size: 22px; }

    .jl-landing-role-card { border: 1px solid rgba(15,42,92,0.15); border-radius: 12px; padding: 28px; background: #fff; }

    /* ===== Closing CTA band (same photo overlay as the hero) ===== */
    .jl-landing-cta-inner {
        padding: 64px 24px; display: flex; flex-wrap: wrap; gap: 24px;
        align-items: center; justify-content: space-between;
    }
    .jl-landing-cta-copy { flex: 1 1 420px; }
    .jl-landing-cta-copy h2 { margin: 0 0 8px; font-family: 'Lora', Georgia, serif; font-size: 34px; color: #fff; }
    .jl-landing-cta-copy p { margin: 0; color: #E5E9F2; font-size: 17px; line-height: 1.6; }
    .jl-landing-cta-actions { display: flex; gap: 12px; flex-wrap: wrap; }

    .jl-landing-footer { padding: 28px 24px; text-align: center; font-size: 14px; color: #4B5563; }
    .jl-landing-footer a { color: #4B5563; text-decoration: underline; }

    @media (max-width: 575.98px) {
        .jl-landing-hero-title { font-size: 38px; }
        .jl-landing-cta-copy h2 { font-size: 26px; }
        .jl-landing-section-header h2 { font-size: 28px; }
    }
</style>
@endpush

@section('content')
<div class="jl-landing-breakout">

    {{-- Header + hero share one photo background so the image runs behind the nav too. --}}
    <div class="jl-landing-photo-bg">
        <header>
            <div class="jl-landing-container jl-landing-header-inner">
                <a href="{{ route('landing') }}" class="jl-landing-logo">
                    <img src="{{ asset('Images/logo.png') }}" alt="" aria-hidden="true">JobLink
                </a>
                <nav class="jl-landing-nav">
                    <a href="#features">Features</a>
                    <a href="#how">How it works</a>
                    <a href="#who">Who it&rsquo;s for</a>
                    <a href="{{ route('login') }}" class="jl-landing-nav-login">Log in</a>
                    <a href="{{ route('register.show') }}" class="jl-landing-nav-cta">Request an account</a>
                </nav>
            </div>
        </header>

        <section>
            <div class="jl-landing-container jl-landing-hero-inner">
                <div class="jl-landing-hero-copy">
                    <p class="jl-landing-hero-eyebrow">PSU Alaminos City Campus</p>
                    <h1 class="jl-landing-hero-title">Your next step starts here.</h1>
                    <p class="jl-landing-hero-sub">JobLink connects PSU Alaminos City students and alumni with partner companies, and every posting is reviewed by the Job Coordinator first.</p>
                    <div class="jl-landing-hero-ctas">
                        <a href="{{ route('login') }}" class="jl-landing-btn-solid">Log in</a>
                        <a href="{{ route('register.show') }}" class="jl-landing-btn-outline">Request an account</a>
                    </div>
                </div>
                <div class="jl-landing-hero-preview">
                    @php $featuredJobs = $featuredJobs ?? []; @endphp
                    @if(count($featuredJobs))
                        {{-- Real postings (approved + open), supplied by LandingController. --}}
                        <div class="jl-landing-preview-card" id="jobRotator" role="group" aria-roledescription="carousel" aria-label="Latest open positions">
                            <div class="jl-landing-rotator-stage" aria-live="off">
                                @foreach($featuredJobs as $i => $j)
                                    <article class="jl-landing-slide {{ $i === 0 ? 'is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($featuredJobs) }}">
                                        <div class="jl-landing-preview-card-head">
                                            <span class="jl-landing-preview-logo" data-initial="{{ $j['initial'] }}" style="--accent-soft: {{ $j['accentSoft'] }}; --accent-text: {{ $j['accentText'] }};">
                                                @if($j['logo'])
                                                    <img src="{{ $j['logo'] }}" alt="" onerror="this.parentNode.textContent = this.parentNode.dataset.initial;">
                                                @else
                                                    {{ $j['initial'] }}
                                                @endif
                                            </span>
                                            <div>
                                                <h4>{{ $j['title'] }}</h4>
                                                <p>{{ $j['company'] }} &middot; {{ $j['location'] }}</p>
                                            </div>
                                        </div>
                                        <div class="jl-landing-preview-pills">
                                            @if($j['type'])<span>{{ $j['type'] }}</span>@endif
                                            <span>Deadline {{ \Carbon\Carbon::parse($j['deadline'])->format('M j, Y') }}</span>
                                        </div>
                                        <a href="{{ route('jobs.show', $j['id']) }}" class="jl-landing-preview-apply">View job</a>
                                    </article>
                                @endforeach
                            </div>
                            @if(count($featuredJobs) > 1)
                                <div class="jl-landing-rotator-foot">
                                    <div class="jl-landing-dots">
                                        @foreach($featuredJobs as $i => $j)
                                            <button type="button" class="jl-landing-dot {{ $i === 0 ? 'is-active' : '' }}" aria-label="Show job {{ $i + 1 }} of {{ count($featuredJobs) }}"></button>
                                        @endforeach
                                    </div>
                                    <button type="button" class="jl-landing-rotator-toggle" aria-label="Pause rotation">Pause</button>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Nothing approved and open right now: keep the hero looking finished. --}}
                        <div class="jl-landing-preview-card">
                            <div class="jl-landing-preview-card-head">
                                <span class="jl-landing-preview-logo" aria-hidden="true"><img src="{{ asset('images/logo.png') }}" alt=""></span>
                                <div>
                                    <h4>New openings are on the way</h4>
                                    <p>Partner companies post here once approved.</p>
                                </div>
                            </div>
                            <a href="{{ route('login') }}" class="jl-landing-preview-apply">Log in to browse jobs</a>
                        </div>
                    @endif
                    <div class="jl-landing-app-card">
                        <div>
                            <div class="jl-landing-app-card-label">My Applications</div>
                            <div class="jl-landing-app-card-title">Customer Service Associate</div>
                        </div>
                        <span class="jl-landing-status-pill">Interview</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Promo video: plays on page load (muted, looping, inline). File goes in
         public/videos/joblink-promo.mp4. Browsers only allow autoplay when the
         video is muted, so it starts silent; the speaker button turns sound on. --}}
    <section id="video" class="jl-landing-video">
        <div class="jl-landing-video-card">
            <div class="jl-landing-video-frame">
                <video id="promoVideo" autoplay loop muted playsinline preload="auto"
                       poster="{{ asset('Images/campus-photo.jpg') }}" aria-label="JobLink promo video">
                    <source src="{{ asset('videos/joblink-promo.mp4') }}" type="video/mp4">
                    Your browser can&rsquo;t play embedded video.
                </video>
                <div class="jl-landing-video-controls">
                    <button type="button" id="promoPause" class="jl-landing-video-btn" aria-label="Pause video">
                        <span class="ic-pause"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"></path></svg></span>
                        <span class="ic-play" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg></span>
                    </button>
                    <button type="button" id="promoMute" class="jl-landing-video-btn" aria-label="Unmute video">
                        <span class="ic-sound-on" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9z" fill="currentColor"></path><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12"></path></svg></span>
                        <span class="ic-sound-off"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9z" fill="currentColor"></path><path d="M17 9l5 6M22 9l-5 6"></path></svg></span>
                    </button>
                </div>
            </div>
            <div class="jl-landing-video-meta">
                <span>Meet JobLink: the career-placement portal of PSU Alaminos City Campus</span>
            </div>
        </div>
    </section>

    <section id="features" class="jl-landing-features">
        <div class="jl-landing-container">
            <div class="jl-landing-section-header">
                <h2>Features</h2>
                <p>Everything students, alumni and companies need to connect.</p>
            </div>
            <div class="jl-landing-grid">
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path></svg>
                    </div>
                    <h3>Approved jobs only</h3>
                    <p>Every posting is reviewed by the Job Coordinator before students and alumni can see it.</p>
                </div>
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l5 5v13H7z"></path><path d="M14 3v5h5"></path></svg>
                    </div>
                    <h3>Apply with a PDF resume</h3>
                    <p>Pick a job, upload your resume and send it. Your status shows as Pending, Interview or Rejected.</p>
                </div>
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 17v-6a6 6 0 0 1 12 0v6l2 2H4z"></path><path d="M10 21h4"></path></svg>
                    </div>
                    <h3>Stay in the loop</h3>
                    <p>Messages and updates from companies reach you in JobLink and by email.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how" class="jl-landing-how">
        <div class="jl-landing-container">
            <div class="jl-landing-section-header">
                <h2>How it works</h2>
                <p>Three steps from sign-up to your first application.</p>
            </div>
            <div class="jl-landing-grid">
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-step-num">1</div>
                    <h3>Get your account</h3>
                    <p>On the school&rsquo;s master list? Activate with your Student ID. New here? Request an account and the Job Coordinator will review it.</p>
                </div>
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-step-num">2</div>
                    <h3>Complete your profile</h3>
                    <p>Add your details and program once. Alumni also open the Tracer Study so the campus can follow your journey.</p>
                </div>
                <div class="jl-landing-card">
                    <div aria-hidden="true" class="jl-landing-step-num">3</div>
                    <h3>Apply and track</h3>
                    <p>Browse approved jobs, apply with a PDF resume, and follow your status: Pending, Interview or Rejected.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="who" class="jl-landing-who">
        <div class="jl-landing-container">
            <div class="jl-landing-section-header">
                <h2>One place for everyone involved</h2>
                <p>Each role sees only what it needs.</p>
            </div>
            <div class="jl-landing-grid">
                <div class="jl-landing-role-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 4-6 8-6s8 2 8 6"></path></svg>
                    </div>
                    <h3>Students and alumni</h3>
                    <p>Save jobs you like, apply with your resume, and get notified in JobLink and by email.</p>
                </div>
                <div class="jl-landing-role-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="1"></rect><path d="M9 8h2M13 8h2M9 12h2M13 12h2M10 21v-4h4v4"></path></svg>
                    </div>
                    <h3>Companies</h3>
                    <p>Post openings, review applicants and resumes, and invite the right people to interview.</p>
                </div>
                <div class="jl-landing-role-card">
                    <div aria-hidden="true" class="jl-landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F2A5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path></svg>
                    </div>
                    <h3>Job Coordinator</h3>
                    <p>Approves postings, manages accounts and records, and keeps the campus tracer study on track.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="jl-landing-photo-bg">
        <div class="jl-landing-container jl-landing-cta-inner">
            <div class="jl-landing-cta-copy">
                <h2>Ready to find your first opportunity?</h2>
                <p>Log in with your Student ID, or request an account to get started.</p>
            </div>
            <div class="jl-landing-cta-actions">
                <a href="{{ route('login') }}" class="jl-landing-btn-solid">Log in</a>
                <a href="{{ route('register.show') }}" class="jl-landing-btn-outline">Request an account</a>
            </div>
        </div>
    </section>

    <footer class="jl-landing-footer">
        JobLink &middot; Pangasinan State University, Alaminos City Campus &middot;
        <a href="{{ route('privacy') }}">Privacy Policy</a> &middot;
        <a href="{{ route('cookies') }}">Cookie Policy</a>
    </footer>

</div>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('jobRotator');
    if (!root) { return; }
    var slides = root.querySelectorAll('.jl-landing-slide');
    var dots = root.querySelectorAll('.jl-landing-dot');
    var toggle = root.querySelector('.jl-landing-rotator-toggle');
    if (slides.length < 2 || !toggle) { return; }

    var INTERVAL = 5000;
    var current = 0, timer = null, hovering = false;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var userPaused = reduce; // reduced-motion visitors start paused; dots still work

    function show(i) {
        current = (i + slides.length) % slides.length;
        slides.forEach(function (el, n) { el.classList.toggle('is-active', n === current); });
        dots.forEach(function (el, n) { el.classList.toggle('is-active', n === current); });
    }
    function stop() { clearInterval(timer); timer = null; }
    function start() {
        stop();
        if (!userPaused && !hovering) { timer = setInterval(function () { show(current + 1); }, INTERVAL); }
    }
    function label() {
        toggle.textContent = userPaused ? 'Play' : 'Pause';
        toggle.setAttribute('aria-label', userPaused ? 'Resume rotation' : 'Pause rotation');
    }

    dots.forEach(function (el, n) { el.addEventListener('click', function () { show(n); start(); }); });
    toggle.addEventListener('click', function () { userPaused = !userPaused; label(); start(); });
    root.addEventListener('mouseenter', function () { hovering = true; stop(); });
    root.addEventListener('mouseleave', function () { hovering = false; start(); });
    root.addEventListener('focusin', function () { hovering = true; stop(); });
    root.addEventListener('focusout', function () { hovering = false; start(); });

    label();
    start();
})();
</script>
<script>
(function () {
    var video = document.getElementById('promoVideo');
    var pauseBtn = document.getElementById('promoPause');
    var muteBtn = document.getElementById('promoMute');
    if (!video || !pauseBtn || !muteBtn) { return; }

    // Keep both buttons' icons and labels in step with the real video state,
    // so they stay correct even if the browser blocks autoplay.
    function sync() {
        pauseBtn.setAttribute('aria-label', video.paused ? 'Play video' : 'Pause video');
        pauseBtn.querySelector('.ic-pause').hidden = video.paused;
        pauseBtn.querySelector('.ic-play').hidden = !video.paused;

        muteBtn.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
        muteBtn.querySelector('.ic-sound-on').hidden = video.muted;
        muteBtn.querySelector('.ic-sound-off').hidden = !video.muted;
    }

    pauseBtn.addEventListener('click', function () {
        if (video.paused) { video.play().catch(function () {}); } else { video.pause(); }
    });
    muteBtn.addEventListener('click', function () {
        video.muted = !video.muted;
        if (!video.muted && video.volume === 0) { video.volume = 1; }
    });
    ['play', 'pause', 'volumechange'].forEach(function (evt) { video.addEventListener(evt, sync); });

    // Respect visitors who ask their device to reduce motion: don't autoplay for them.
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        video.pause();
    } else {
        video.play().catch(function () { /* autoplay declined; the play button still works */ });
    }
    sync();
})();
</script>
@endpush
@endsection