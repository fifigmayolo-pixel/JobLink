<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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

    public function store(StoreApplicationRequest $request, JobPosting $jobPosting)
    {
        // Nobody can apply to a posting the Job Coordinator hasn't approved,
        // or one whose company account is archived, even by posting to the
        // URL directly.
        abort_unless($jobPosting->isApproved() && $jobPosting->companyIsActive(), 404);

        Gate::authorize('create', [JobApplication::class, $jobPosting]);

        $path = $request->file('resume')->store('resumes');

        $jobPosting->applications()->create([
            'applicant_id' => auth()->id(),
            'resume_path' => $path,
            'date_applied' => now(),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Application submitted.');
    }

    public function withdraw(JobApplication $application)
    {
        // Only the applicant, and only while it is still Pending.
        Gate::authorize('withdraw', $application);

        $resumePath = $application->resume_path;

        $application->update(['status' => 'withdrawn']);

        // The student no longer wants the company to have it, so delete the file.
        // The row stays (as Withdrawn) for the company's and Coordinator's records.
        try {
            Storage::delete($resumePath);
        } catch (\Throwable $e) {
            report($e);
        }

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

    public function downloadResume(JobApplication $application)
    {
        Gate::authorize('view', $application);

        // The file is deleted when an application is withdrawn.
        abort_if($application->isWithdrawn(), 404);
        abort_unless($application->resume_path && Storage::exists($application->resume_path), 404);

        return Storage::response($application->resume_path);
    }
}