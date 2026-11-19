<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateApplicationStatusRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'past' ? 'past' : 'active';
        $companyId = auth()->user()->company->id;

        $own = fn () => JobApplication::query()
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', $companyId));

        $activeCount = $own()->inProgress()->count();
        $pastCount = $own()->finished()->count();

        $applications = $own()
            ->when($tab === 'past', fn ($q) => $q->finished(), fn ($q) => $q->inProgress())
            ->with(['applicant.profile', 'jobPosting'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('date_applied')
            ->paginate(20)
            ->withQueryString();

        return view('company.applications.index', compact('applications', 'tab', 'activeCount', 'pastCount'));
    }

    public function show(JobApplication $application)
    {
        Gate::authorize('review', $application);

        return view('company.applications.show', compact('application'));
    }

    /**
     * Printable copy of the CV the applicant submitted. Same rule as the review
     * page: only the company that owns the job posting can open it. A withdrawn
     * application has no CV left (it was cleared), and an application from before
     * the CV system never had one, so both are a 404.
     */
    public function cv(JobApplication $application)
    {
        Gate::authorize('review', $application);

        abort_unless($application->hasCvSnapshot(), 404);

        return response()
            ->view('company.applications.cv-print', [
                'application' => $application,
                'cv' => $application->cv_snapshot,
            ])
            // Personal details: don't let browsers or proxies keep a copy, or search engines index it.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application)
    {
        Gate::authorize('review', $application);

        abort_if($application->isWithdrawn(), 403, 'This application was withdrawn by the applicant.');

        $newStatus = $request->validated('status');

        // Each applicant in Interview fills one of the posting's slots. The count, the check and the
        // change happen together with the posting row locked, so two invitations at the same moment
        // cannot both take the last slot.
        $outcome = DB::transaction(function () use ($application, $newStatus) {
            $posting = JobPosting::withTrashed()->whereKey($application->job_posting_id)->lockForUpdate()->first();
            $liveSlots = $posting && ! $posting->trashed();

            $takesNewSlot = $newStatus === 'interview' && $application->status !== 'interview';

            if ($liveSlots && $takesNewSlot && $posting->interviewCount() >= $posting->slotsTotal()) {
                return ['full', null];
            }

            $application->update(['status' => $newStatus]);

            // The posting closes when the last slot is taken, and reopens when one is freed.
            return ['ok', $liveSlots ? $posting->syncSlots(auth()->id()) : null];
        });

        if ($outcome[0] === 'full') {
            return back()->withErrors([
                'status' => 'Every interview slot for this job is filled. '
                    .'Raise the number of slots, or move someone out of Interview, to invite another applicant.',
            ]);
        }

        if (in_array($application->status, ['interview', 'rejected'])) {
            $this->notifications->applicationStatusChanged($application);
        }

        $message = match ($outcome[1]) {
            'closed' => 'Application status updated. All interview slots are now filled, so the job is closed to new applications.',
            'reopened' => 'Application status updated. A slot is free again, so the job is open to new applications.',
            default => 'Application status updated.',
        };

        return back()->with('status', $message);
    }
}
