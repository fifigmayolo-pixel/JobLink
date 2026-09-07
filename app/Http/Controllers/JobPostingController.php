<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobPostingController extends Controller
{
    public function index(Request $request)
    {
        $jobPostings = JobPosting::query()
            ->open()
            ->with('company')
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->location, fn ($q, $location) => $q->where('location', 'like', "%{$location}%"))
            ->latest('date_posted')
            ->paginate(15);

        return view('jobs.index', compact('jobPostings'));
    }

    public function show(JobPosting $jobPosting)
    {
        return view('jobs.show', compact('jobPosting'));
    }

    public function create()
    {
        Gate::authorize('create', JobPosting::class);

        return view('jobs.create', [
            'companies' => in_array(auth()->user()->role, ['peso', 'job_coordinator'])
                ? Company::orderBy('company_name')->get()
                : null,
        ]);
    }

    public function store(StoreJobPostingRequest $request)
    {
        $data = $request->validated();
        $data['posted_by'] = auth()->id();
        $data['date_posted'] = now()->toDateString();
        $data['status'] = 'open';

        if (auth()->user()->role === 'company') {
            $data['company_id'] = auth()->user()->company->id;
        }

        $jobPosting = JobPosting::create($data);

        return redirect()->route('jobs.show', $jobPosting)->with('status', 'Job posted.');
    }

    public function edit(JobPosting $jobPosting)
    {
        Gate::authorize('update', $jobPosting);

        return view('jobs.edit', compact('jobPosting'));
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting)
    {
        Gate::authorize('update', $jobPosting);

        $jobPosting->update($request->validated());

        return redirect()->route('jobs.show', $jobPosting)->with('status', 'Job updated.');
    }

    public function destroy(JobPosting $jobPosting)
    {
        Gate::authorize('delete', $jobPosting);

        $jobPosting->delete();

        return redirect()->route('jobs.index')->with('status', 'Job removed.');
    }
}