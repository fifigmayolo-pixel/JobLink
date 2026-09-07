<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;

class SavedJobController extends Controller
{
    public function index()
    {
        $savedJobs = auth()->user()->savedJobs()
            ->with('jobPosting.company')
            ->latest()
            ->paginate(15);

        return view('saved-jobs.index', compact('savedJobs'));
    }

    public function store(JobPosting $jobPosting)
    {
        auth()->user()->savedJobs()->firstOrCreate([
            'job_posting_id' => $jobPosting->id,
        ]);

        return back()->with('status', 'Job saved.');
    }

    public function destroy(JobPosting $jobPosting)
    {
        auth()->user()->savedJobs()
            ->where('job_posting_id', $jobPosting->id)
            ->delete();

        return back()->with('status', 'Job removed from saved.');
    }
}