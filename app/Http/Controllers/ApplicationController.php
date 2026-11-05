<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = auth()->user()->applications()
            ->with('jobPosting.company')
            ->latest('date_applied')
            ->paginate(15);

        return view('applications.index', compact('applications'));
    }

    public function show(JobApplication $application)
    {
        Gate::authorize('view', $application);

        return view('applications.show', compact('application'));
    }

    public function store(StoreApplicationRequest $request, JobPosting $jobPosting)
    {
        // Nobody can apply to a posting the Job Coordinator hasn't approved,
        // even by posting to the URL directly.
        abort_unless($jobPosting->isApproved(), 404);

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

    public function downloadResume(JobApplication $application)
    {
        Gate::authorize('view', $application);

        // The file is deleted when an application is withdrawn.
        abort_if($application->isWithdrawn(), 404);

        return Storage::response($application->resume_path);
    }
}