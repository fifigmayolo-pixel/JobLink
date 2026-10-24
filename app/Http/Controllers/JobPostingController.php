<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Jobs\NotifyNewJobPosting;
use App\Jobs\NotifyTaggedCompany;
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
        // Only approved AND open postings appear on the Jobs screen.
        $jobPostings = JobPosting::query()
            ->approved()
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
        // 404 rather than 403, so an unapproved posting's existence isn't revealed.
        abort_unless(Gate::allows('view', $jobPosting), 404);

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
        $isCoordinator = auth()->user()->role === 'job_coordinator';

        $data = $request->validated();
        $data['posted_by'] = auth()->id();
        $data['date_posted'] = now()->toDateString();
        $data['status'] = 'open';

        // A company's posting waits for Coordinator approval; the Coordinator's
        // own postings are approved immediately (they are the approver).
        $data['approval_status'] = $isCoordinator ? 'approved' : 'pending';

        if ($isCoordinator) {
            $data['reviewed_by'] = auth()->id();
            $data['reviewed_at'] = now();
        }

        if (auth()->user()->role === 'company') {
            $data['company_id'] = auth()->user()->company->id;
        }

        $jobPosting = JobPosting::create($data);

        $companyName = $jobPosting->company?->company_name ?? 'a company';

        FieldChangeLog::create([
            'action' => 'job_posting_created',
            'changed_by' => auth()->id(),
            'job_posting_id' => $jobPosting->id,
            'details' => $isCoordinator
                ? 'Posted for '.$companyName.'.'
                : 'Submitted for approval by '.$companyName.'.',
        ]);

        if ($isCoordinator) {
            // Students and alumni get a "new job" notification, fanned out in the
            // background so posting stays fast. Needs `php artisan queue:work`.
            NotifyNewJobPosting::dispatch($jobPosting->id);

            // The Coordinator tagged a specific company when posting this one
            // (the "Company" select on the create form) — let that company
            // know, via an in-app notification and an email to their
            // registered login address.
            if ($jobPosting->company_id) {
                NotifyTaggedCompany::dispatch($jobPosting->id);
            }

            return redirect()->route('jobs.show', $jobPosting)->with('status', 'Job posted.');
        }

        // Companies: the students' notification is sent later, when it is approved.
        return redirect()->route('jobs.show', $jobPosting)
            ->with('status', 'Job submitted for approval. You will be notified once the Job Coordinator reviews it.');
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

        // A company editing a REJECTED posting sends it back for review.
        $resubmitting = auth()->user()->role === 'company' && $jobPosting->isRejected();

        if ($resubmitting) {
            $data = array_merge($data, [
                'approval_status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }

        $jobPosting->update($data);

        foreach ($changes as $change) {
            FieldChangeLog::create($change);
        }

        if ($resubmitting) {
            FieldChangeLog::create([
                'action' => 'job_posting_resubmitted',
                'changed_by' => auth()->id(),
                'job_posting_id' => $jobPosting->id,
                'details' => 'Edited and resubmitted for approval.',
            ]);

            return redirect()->route('jobs.show', $jobPosting)
                ->with('status', 'Job updated and resubmitted for approval.');
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