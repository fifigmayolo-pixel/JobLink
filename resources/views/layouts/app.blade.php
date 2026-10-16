<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'JobLink') · PSU Alaminos City</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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

        

        body {
            background: var(--jl-cream);
            color: var(--jl-ink);
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3, h4, .display-font {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            color: var(--jl-navy);
            letter-spacing: -0.01em;
        }

        a { color: var(--jl-navy); }

        .jl-seal {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid var(--jl-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Fraunces', serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--jl-gold);
            flex-shrink: 0;
        }
        .jl-logo { width: 56px; height: 56px; object-fit: contain; flex-shrink: 0; }
        .jl-company-logo {
            width: 64px;
            height: 64px;
            border-radius: 0.5rem;
            border: 1px solid rgba(15, 42, 92, 0.1);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
}
.jl-company-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.jl-company-logo-placeholder {
    color: var(--jl-slate);
    font-size: 1.5rem;
}
        /* ===== Shell: sidebar + main column ===== */
        .jl-shell { display: flex; min-height: 100vh; }

        .jl-sidebar {
            width: 260px;
            background: var(--jl-navy) !important;
            border-right: 3px solid var(--jl-gold) !important;
            flex-shrink: 0;
        }
        .jl-sidebar .offcanvas-header { border-bottom: 1px solid rgba(255,255,255,0.12); }
        .jl-sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 1.5rem 1.35rem 1rem;
            font-family: 'Fraunces', serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: #fff;
            text-decoration: none;
        }
        .jl-sidebar-brand:hover { color: var(--jl-gold-soft); }
        .jl-sidebar-nav { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.5rem 0.9rem 1.5rem; }
        .jl-sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.9rem;
            border-radius: 0.5rem;
            color: rgba(255,255,255,0.72);
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .jl-sidebar-link i { font-size: 1.05rem; width: 1.25rem; text-align: center; }
        .jl-sidebar-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .jl-sidebar-link.active { background: rgba(201,162,39,0.18); color: var(--jl-gold-soft); font-weight: 600; }

        .jl-main-wrapper { flex: 1; min-width: 0; display: flex; flex-direction: column; }

        .jl-topbar {
            background: #fff;
            box-shadow: 0 1px 3px rgba(15,42,92,0.08);
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .jl-sidebar-toggle { background: none; border: none; font-size: 1.4rem; color: var(--jl-navy); line-height: 1; }
        .jl-topbar-icon { color: var(--jl-navy); font-size: 1.2rem; text-decoration: none; }
        .jl-topbar-profile {
           display: flex;
           align-items: center;
           gap: 0.6rem;
           color: var(--jl-ink);
           text-decoration: none;
}
.jl-topbar-profile:hover { color: var(--jl-navy); }
.jl-topbar-profile-text { display: flex; flex-direction: column; line-height: 1.25; }
.jl-topbar-profile .jl-role-label { font-size: 0.78rem; color: var(--jl-slate); font-weight: 400; }

        .jl-content { flex: 1; padding: 1.75rem; }

        .jl-footer { padding: 1.25rem 1.75rem; text-align: center; border-top: 1px solid rgba(15,42,92,0.08); }

        /* ===== Cards, buttons, misc ===== */
        .jl-rule { border: none; border-top: 1px solid var(--jl-gold); opacity: 0.5; margin: 1.5rem 0; }

        .jl-card {
            background: #fff;
            border: 1px solid rgba(15, 42, 92, 0.08);
            border-radius: 0.75rem;
            box-shadow: 0 2px 10px rgba(15,42,92,0.06);
        }
        .jl-card-hover { transition: box-shadow 0.2s ease, transform 0.2s ease; }
        .jl-card-hover:hover { box-shadow: 0 8px 20px rgba(15,42,92,0.1); transform: translateY(-2px); }

        .jl-badge-pending { background: rgba(107,114,128,0.12); color: var(--jl-slate); }
        .jl-badge-interview { background: rgba(201,162,39,0.15); color: #8A6C16; }
        .jl-badge-rejected { background: rgba(180,85,63,0.12); color: var(--jl-rose); }

        .btn-jl-gold, .btn-jl-navy { border-radius: 0.5rem; font-weight: 600; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .btn-jl-gold { background: var(--jl-gold); border-color: var(--jl-gold); color: var(--jl-navy-deep); }
        .btn-jl-gold:hover { background: var(--jl-gold-soft); border-color: var(--jl-gold-soft); color: var(--jl-navy-deep); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(201,162,39,0.3); }
        .btn-jl-navy { background: var(--jl-navy); border-color: var(--jl-navy); color: #fff; }
        .btn-jl-navy:hover { background: var(--jl-navy-deep); border-color: var(--jl-navy-deep); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(15,42,92,0.25); }

        .form-control, .form-select { border-radius: 0.5rem; }

        .jl-password-toggle {
            position: absolute;
            top: 50%;
            right: 0.85rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
            color: var(--jl-slate);
            font-size: 1.05rem;
            display: flex;
            align-items: center;
        }
        .jl-password-toggle:hover { color: var(--jl-navy); }

        :focus-visible { outline: 3px solid var(--jl-gold); outline-offset: 2px; }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>

    @stack('styles')
</head>
<body>

     @include('partials.page-loader')
    <div class="jl-shell">
        @auth
        <aside class="offcanvas-lg offcanvas-start jl-sidebar" tabindex="-1" id="jlSidebar">
            <div class="offcanvas-header d-lg-none">
                <span class="d-flex align-items-center gap-2 text-white fw-semibold" style="font-family: 'Fraunces', serif;">
                    <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo"> JobLink
                </span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#jlSidebar" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
                <a class="jl-sidebar-brand d-none d-lg-flex" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="JobLink" class="jl-logo">
                    JobLink
                </a>
                <nav class="jl-sidebar-nav">
                    <a class="jl-sidebar-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}" href="{{ route('jobs.index') }}">
                        <i class="bi bi-briefcase"></i> Jobs
                    </a>

                    @role('student|alumni')
                        <a class="jl-sidebar-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}">
                            <i class="bi bi-file-earmark-text"></i> My Applications
                        </a>
                        <a class="jl-sidebar-link {{ request()->routeIs('saved-jobs.*') ? 'active' : '' }}" href="{{ route('saved-jobs.index') }}">
                            <i class="bi bi-bookmark"></i> Saved
                        </a>
                    @endrole

                    @role('company')
                        <a class="jl-sidebar-link {{ request()->routeIs('company.applications.*') ? 'active' : '' }}" href="{{ route('company.applications.index') }}">
                            <i class="bi bi-people"></i> Applicants
                        </a>
                    @endrole

                    @role('job_coordinator')
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}" href="{{ route('coordinator.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.accounts.*') ? 'active' : '' }}" href="{{ route('coordinator.accounts.index') }}">
                            <i class="bi bi-person-badge"></i> Accounts
                        </a>
                        @php $pendingJobApprovals = \App\Models\JobPosting::awaitingApproval()->count(); @endphp
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.job-approvals.*') ? 'active' : '' }}" href="{{ route('coordinator.job-approvals.index') }}">
                            <i class="bi bi-patch-check"></i> Job Approvals
                            @if($pendingJobApprovals > 0)
                                <span class="badge rounded-pill ms-auto" style="background: var(--jl-gold); color: var(--jl-navy-deep);">{{ $pendingJobApprovals }}</span>
                            @endif
                        </a>

                        @php $pendingRegistrations = \App\Models\RegistrationRequest::pending()->count(); @endphp
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.registration-requests.*') ? 'active' : '' }}" href="{{ route('coordinator.registration-requests.index') }}">
                            <i class="bi bi-person-plus"></i> Registration Requests
                            @if($pendingRegistrations > 0)
                                <span class="badge rounded-pill ms-auto" style="background: var(--jl-gold); color: var(--jl-navy-deep);">{{ $pendingRegistrations }}</span>
                            @endif
                        </a>

                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.records.*') ? 'active' : '' }}" href="{{ route('coordinator.records.index') }}">
                            <i class="bi bi-folder2-open"></i> Records
                        </a>
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.announcements.*') ? 'active' : '' }}" href="{{ route('coordinator.announcements.create') }}">
                            <i class="bi bi-megaphone"></i> Announce
                        </a>
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.tracer-study-links.*') ? 'active' : '' }}" href="{{ route('coordinator.tracer-study-links.index') }}">
                            <i class="bi bi-link-45deg"></i> Tracer Study Link
                        </a>
                        <a class="jl-sidebar-link {{ request()->routeIs('coordinator.audit-log.*') ? 'active' : '' }}" href="{{ route('coordinator.audit-log.index') }}">
                            <i class="bi bi-clock-history"></i> Audit Log
                        </a>
                    @endrole
                </nav>
            </div>
        </aside>
        @endauth

        <div class="jl-main-wrapper">
            @auth
            <header class="jl-topbar">
                <button class="jl-sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#jlSidebar" aria-controls="jlSidebar" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <a class="jl-topbar-icon position-relative" href="{{ route('notifications.index') }}" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if(($unreadNotificationCount ?? 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" style="background: var(--jl-gold); color: var(--jl-navy-deep); font-size: 0.65rem;">
                                {{ $unreadNotificationCount }}
                            </span>
                        @endif
                    </a>

                    <div class="dropdown">
                          <a class="jl-topbar-profile dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @php
                        $topbarAvatarPath = match (true) {
                                in_array(auth()->user()->role, ['student', 'alumni']) => auth()->user()->profile?->profile_picture,
                                auth()->user()->role === 'company' => auth()->user()->company?->logo,
                                auth()->user()->role === 'job_coordinator' => auth()->user()->profile_picture,
                                default => null,
};
                            @endphp
                            @include('partials.profile-avatar', ['imagePath' => $topbarAvatarPath, 'label' => null, 'size' => 40])
                            <span class="jl-topbar-profile-text">
                                @if(in_array(auth()->user()->role, ['student', 'alumni', 'job_coordinator']))
                                    <span>{{ auth()->user()->profile?->full_name ?? auth()->user()->full_name ?? auth()->user()->email }}</span>
                                    <span class="jl-role-label">
                                        @switch(auth()->user()->role)
                                            @case('job_coordinator')
                                                Job Coordinator
                                                @break
                                            @case('student')
                                                Student
                                                @break
                                            @case('alumni')
                                                Alumni
                                                @break
                                        @endswitch
                                    </span>
                                @else
                                    {{ auth()->user()->company?->company_name ?? auth()->user()->email ?? auth()->user()->student_id_number }}
                                @endif
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @role('student|alumni')
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                            @endrole
                            @role('company')
                                <li><a class="dropdown-item" href="{{ route('company.profile.edit') }}">Company Profile</a></li>
                            @endrole
                            @role('job_coordinator')
                                <li><a class="dropdown-item" href="{{ route('coordinator.profile.edit') }}">Profile</a></li>
                            @endrole
                            <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            @endauth

            <main class="jl-content">
                @if(session('status'))
                    <div class="alert alert-light jl-card mb-4" style="border-left: 4px solid var(--jl-gold) !important;" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-light jl-card mb-4" style="border-left: 4px solid var(--jl-rose) !important;" role="alert">
                        <strong>Please check the following:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            @unless(request()->routeIs('login'))
                <footer class="jl-footer">
                    <small class="text-secondary">JobLink · Pangasinan State University, Alaminos City Campus</small>
                </footer>
            @endunless
        </div>
    </div>


        @auth
        @include('partials.linky-widget')
    @endauth
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>