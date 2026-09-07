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

    public function downloadResume(JobApplication $application)
    {
        Gate::authorize('view', $application);

        return Storage::response($application->resume_path);
    }
}