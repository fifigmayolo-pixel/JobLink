<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\JobSuggestions;

class DashboardController extends Controller
{
    /** The four statuses the dashboard counts, in the order they are shown. */
    private const STATUSES = ['pending', 'interview', 'rejected', 'withdrawn'];

    public function index()
    {
        $user = auth()->user();

        return match ($user->role) {
            'student', 'alumni' => $this->studentDashboard($user),
            'company' => $this->companyDashboard($user),
            'job_coordinator' => redirect()->route('coordinator.dashboard'),
        };
    }

    /**
     * The partner company's own numbers. The company id comes from the logged-in
     * account, never from the request, and every query below is limited to it.
     * The one number that is not the company's own is the registered count: a single
     * figure, with no names or details behind it.
     */
    private function companyDashboard(User $user)
    {
        $company = $user->company;
        $companyId = $company?->id;

        // Same rule as the Applicants page: every application to one of this
        // company's postings. A login with no company record matches nothing.
        $own = fn () => $companyId === null
            ? JobApplication::query()->whereRaw('1 = 0')
            : JobApplication::query()->whereHas('jobPosting', fn ($q) => $q->where('company_id', $companyId));

        // Own postings by state (pending, not approved, open, closed): the same
        // states and the same meaning as Your Posted Jobs.
        $jobCounts = [];
        foreach (JobPosting::LISTING_STATES as $state) {
            $jobCounts[$state] = JobPosting::query()->ofCompany($companyId)->inListingState($state)->count();
        }

        $latestJobs = JobPosting::query()
            ->ofCompany($companyId)
            ->withCount(['applications as applicants_count' => fn ($q) => $q->where('status', '!=', 'withdrawn')])
            ->latest('date_posted')
            ->latest('id')
            ->limit(5)
            ->get();

        // Applicants = people who applied and have not withdrawn. A withdrawn
        // application has no CV left and is not shown as an applicant.
        $applicantsByStatus = $own()
            ->where('status', '!=', 'withdrawn')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplicants = $own()
            ->where('status', '!=', 'withdrawn')
            ->with(['applicant.profile', 'jobPosting'])
            ->latest('date_applied')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard.company', [
            'user' => $user,
            'company' => $company,
            // Active accounts, counted the way the Coordinator dashboard counts them:
            // activated, and not removed (a removed account is soft-deleted).
            'registeredCount' => User::query()
                ->whereIn('role', ['student', 'alumni'])
                ->whereNotNull('activated_at')
                ->count(),
            'jobCounts' => $jobCounts,
            'totalJobs' => array_sum($jobCounts),
            'latestJobs' => $latestJobs,
            'applicantsTotal' => (int) $applicantsByStatus->sum(),
            'waitingCount' => (int) ($applicantsByStatus['pending'] ?? 0),
            'recentApplicants' => $recentApplicants,
        ]);
    }

    /**
     * Everything here is read for the logged-in account only: the applicant id and
     * the notification recipient always come from auth(), never from the request.
     */
    private function studentDashboard(User $user)
    {
        $profile = $user->profile;
        $profile?->loadCv();

        $completeness = $profile?->cvCompleteness();
        $cvReady = (bool) ($completeness['ready'] ?? false);

        // The same list My Applications shows: applications the student removed
        // from their own list are not counted or shown.
        $mine = fn () => JobApplication::query()
            ->where('applicant_id', $user->id)
            ->visibleToApplicant();

        $counts = $mine()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts = [];
        foreach (self::STATUSES as $status) {
            $statusCounts[$status] = (int) ($counts[$status] ?? 0);
        }

        $latestApplications = $mine()
            ->with('jobPosting.company')
            ->latest('date_applied')
            ->latest('id')
            ->limit(5)
            ->get();

        $notifications = $user->notifications()
            ->with(['application.jobPosting.company', 'jobPosting.company'])
            ->latest()
            ->latest('id')
            ->limit(5)
            ->get();

        $unreadCount = $user->notifications()->unread()->count();

        // Suggestions need a CV to match on. A job already applied to is left out,
        // but a withdrawn application does not count: the student may apply again.
        $suggestions = collect();

        if ($profile && $cvReady) {
            $appliedJobIds = JobApplication::query()
                ->where('applicant_id', $user->id)
                ->where('status', '!=', 'withdrawn')
                ->pluck('job_posting_id')
                ->all();

            $suggestions = JobSuggestions::suggest($profile, $appliedJobIds);
        }

        return view('dashboard.student', [
            'user' => $user,
            'profile' => $profile,
            'completeness' => $completeness,
            'basicProfileDone' => $profile && $profile->profile_completed_at !== null,
            'cvReady' => $cvReady,
            'statusCounts' => $statusCounts,
            'totalApplications' => array_sum($statusCounts),
            'latestApplications' => $latestApplications,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'suggestions' => $suggestions,
        ]);
    }
}
