<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Jobs\NotifyNewJobPosting;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\FieldChangeLog;

class JobPostingController extends Controller
{
    public function index(Request $request)
    {
        $jobPostings = JobPosting::query()
            ->open()
            ->with('company')
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->location, fn ($q, $location) => $q->where('location', 'like', "%{$location}%"))
            ->when($request->job_type, fn ($q, $jobType) => $q->where('job_type', $jobType))
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
            'companies' => auth()->user()->role === 'job_coordinator'
                ? Company::whereHas('user')->orderBy('company_name')->get()
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

        FieldChangeLog::create([
            'action' => 'job_posting_created',
            'changed_by' => auth()->id(),
            'job_posting_id' => $jobPosting->id,
            'details' => 'Posted for '.($jobPosting->company?->company_name ?? 'a company').'.',
        ]);

        // Students and alumni get a "new job" notification, fanned out in the
        // background so posting stays fast. Needs `php artisan queue:work`.
        NotifyNewJobPosting::dispatch($jobPosting->id);

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

        $data = $request->validated();
        $trackedFields = ['title', 'job_type', 'location', 'application_deadline', 'status'];
        $changes = [];

        foreach ($trackedFields as $field) {
            $oldValue = $jobPosting->{$field};

            // application_deadline is cast to Carbon; compare/store as a
            // plain Y-m-d string so it matches what the date input submits.
            if ($field === 'application_deadline') {
                $oldValue = $oldValue?->format('Y-m-d');
            }

            if ((string) $oldValue !== (string) $data[$field]) {
                $changes[] = [
                    'action' => 'job_posting_update',
                    'changed_by' => auth()->id(),
                    'job_posting_id' => $jobPosting->id,
                    'field_name' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $data[$field],
                ];
            }
        }

        $jobPosting->update($data);

        foreach ($changes as $change) {
            FieldChangeLog::create($change);
        }

        return redirect()->route('jobs.show', $jobPosting)->with('status', 'Job updated.');
    }

    public function destroy(JobPosting $jobPosting)
    {
        Gate::authorize('delete', $jobPosting);

        $jobPosting->delete();

        // Only log removals of postings that were still live (deadline today
        // or later). Clearing out an already-expired posting is routine
        // cleanup, not something the Coordinator needs to audit.
        $deadline = Carbon::parse($jobPosting->application_deadline);

        if ($deadline->gte(today())) {
            FieldChangeLog::create([
                'action' => 'job_posting_deleted',
                'changed_by' => auth()->id(),
                'job_posting_id' => $jobPosting->id,
                'details' => 'Removed before its '.$deadline->format('M j, Y').' deadline.',
            ]);
        }

        return redirect()->route('jobs.index')->with('status', 'Job removed.');
    }
}