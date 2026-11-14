<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\CvSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'past' ? 'past' : 'active';

        // This applicant's own list, minus anything they removed from it.
        $mine = fn () => JobApplication::query()
            ->where('applicant_id', auth()->id())
            ->visibleToApplicant();

        $activeCount = $mine()->inProgress()->count();
        $pastCount = $mine()->finished()->count();

        $applications = $mine()
            ->when($tab === 'past', fn ($q) => $q->finished(), fn ($q) => $q->inProgress())
            ->with('jobPosting.company')
            ->latest('date_applied')
            ->paginate(15)
            ->withQueryString();

        return view('applications.index', compact('applications', 'tab', 'activeCount', 'pastCount'));
    }

    public function show(JobApplication $application)
    {
        Gate::authorize('view', $application);

        return view('applications.show', compact('application'));
    }

    /**
     * The copy of the CV this applicant sent to the company, read-only.
     * Gone after withdrawing (the snapshot is cleared), so that is a 404.
     */
    public function cv(JobApplication $application)
    {
        Gate::authorize('view', $application);

        abort_unless($application->hasCvSnapshot(), 404);

        return view('applications.cv', compact('application'));
    }

    public function store(StoreApplicationRequest $request, JobPosting $jobPosting)
    {
        // Nobody can apply to a posting the Job Coordinator hasn't approved,
        // or one whose company account is archived, even by posting to the
        // URL directly.
        abort_unless($jobPosting->isApproved() && $jobPosting->companyIsActive(), 404);

        Gate::authorize('create', [JobApplication::class, $jobPosting]);

        // The CV is the application, so it has to be ready first. This is checked
        // here and not only in the view, so posting to the URL can't skip it.
        $profile = $request->user()->profile;

        if (! $profile || ! $profile->cvReady()) {
            $missing = $profile
                ? $profile->cvCompleteness()['missing_required']
                : ['Personal summary', 'Skills'];

            return back()->withErrors([
                'application' => 'Finish your CV before applying. Still needed: '.implode(' and ', $missing).'.',
            ]);
        }

        $jobPosting->applications()->create([
            'applicant_id' => $request->user()->id,
            'profile_id' => $profile->id,
            // A copy as of today. Later edits to the CV do not change it.
            'cv_snapshot' => CvSnapshot::fromProfile($profile),
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Application submitted. The company can now see your CV.');
    }

    public function withdraw(JobApplication $application)
    {
        // Only the applicant, and only while it is still Pending.
        Gate::authorize('withdraw', $application);

        // The student no longer wants the company to have their CV, so it is
        // cleared. The row stays (as Withdrawn) for the company's and
        // Coordinator's records. Rejected applications keep their snapshot.
        $application->update([
            'status' => 'withdrawn',
            'cv_snapshot' => null,
            'profile_id' => null,
        ]);

        return redirect()->route('applications.show', $application)
            ->with('status', 'Application withdrawn. You can apply again if the job is still open.');
    }

    /**
     * Remove a finished (rejected or withdrawn) application from the student's
     * own list. Nothing is deleted: the company and the Coordinator keep the row.
     */
    public function hide(JobApplication $application)
    {
        Gate::authorize('hide', $application);

        // Timestamps off on purpose: the withdrawn/rejected dates shown on both
        // sides are read from updated_at, and hiding must not change them.
        $application->timestamps = false;
        $application->update(['hidden_by_applicant_at' => now()]);

        return redirect()->route('applications.index', ['tab' => 'past'])
            ->with('status', 'Application removed from your list.');
    }
}
