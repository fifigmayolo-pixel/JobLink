<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Jobs\NotifyNewJobPosting;
use App\Jobs\NotifyTaggedCompany;
use App\Models\Company;
use App\Models\JobPosting;
use App\Support\JobRelevance;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\FieldChangeLog;

class JobPostingController extends Controller
{
    public function index(Request $request)
{
    // A company account sees "Your Posted Jobs" instead: only its own postings,
    // never another company's. Everyone else keeps the public listing below.
    if ($request->user()->role === 'company') {
        return $this->companyIndex($request);
    }

    // Only approved, open postings from active companies appear.
    // Search ignores upper and lower case (see likeLower): on PostgreSQL a plain LIKE is
    // case sensitive, so "developer" would not find "Web Developer" on the live site.
    // The search and filters, written once so the page and the relevance scoring below use the same ones.
    $filters = fn ($q) => $q
        ->when($this->term($request->query('search')), fn ($q, $search) => $this->likeLower($q, 'job_postings.title', $search))
        ->when($this->term($request->query('location')), fn ($q, $location) => $this->likeLower($q, 'job_postings.location', $location))
        ->when($this->term($request->query('job_type')), fn ($q, $jobType) => $q->where('job_type', $jobType));

    $query = $filters(JobPosting::query()->listed()->with('company')->withInterviewCount());

    // Students and alumni: jobs related to their course and CV skills come first (see JobRelevance).
    // Nothing is hidden: every other job follows, newest first. Anyone else, or a profile with no
    // course words and no skills, keeps the normal newest-first order.
    $related = JobRelevance::forUser($request->user(), $filters);

    if ($related->active()) {
        $query->orderByRaw($related->orderSql());
    }

    $jobPostings = $query
        ->latest('date_posted')
        ->latest('id')
        ->paginate(15)->withQueryString();

    return view('jobs.index', [
        'jobPostings' => $jobPostings,
        'relatedJobIds' => $related->ids(),
        'relevanceActive' => $related->active(),
    ]);
}

    /**
     * Your Posted Jobs. The company id comes from the logged-in account, never from
     * the request, and the list includes every status (pending, not approved, open,
     * closed) so the company can follow its own postings through approval.
     */
    private function companyIndex(Request $request)
    {
        $status = in_array($request->query('status'), JobPosting::LISTING_STATES, true)
            ? $request->query('status')
            : null;

        $search = $this->term($request->query('search')) ?? '';

        $jobPostings = JobPosting::query()
            ->ofCompany($request->user()->company?->id)
            ->withCount(['applications as applicants_count' => fn ($q) => $q->where('status', '!=', 'withdrawn')])
            ->withInterviewCount()
            ->inListingState($status)
            ->when($search !== '', fn ($q) => $this->likeLower($q, 'job_postings.title', $search))
            ->latest('date_posted')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('jobs.mine', [
            'jobPostings' => $jobPostings,
            'status' => $status,
            'search' => $search,
            'filtersActive' => $status !== null || $search !== '',
        ]);
    }

    /**
     * A search box value as a trimmed string, or null when it is empty or not text
     * (a hand-made ?search[]=x would otherwise reach the query as an array).
     */
    private function term(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 100);
    }

    /**
     * "column contains text", ignoring case, the same on SQLite and PostgreSQL.
     */
    private function likeLower($query, string $column, string $text)
    {
        return $query->whereRaw("lower({$column}) like ?", ['%'.mb_strtolower($text).'%']);
    }

    public function show(JobPosting $jobPosting)
{
    // 404 rather than 403, so an unapproved posting's existence isn't revealed.
    abort_unless(Gate::allows('view', $jobPosting), 404);

    // Hide archived companies' jobs from everyone except the Coordinator.
    abort_if(
        ! $jobPosting->companyIsActive()
        && auth()->user()->role !== 'job_coordinator',
        404
    );

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

    /**
     * Edit, close and delete go through here. Someone who is not even allowed to
     * see the posting (another company) gets a 404, like show(), so ids can't be
     * probed; someone who can see it but not change it gets a 403.
     */
    private function authorizeManage(JobPosting $jobPosting): void
    {
        abort_unless(Gate::allows('view', $jobPosting), 404);

        Gate::authorize('update', $jobPosting);
    }

    public function edit(JobPosting $jobPosting)
    {
        $this->authorizeManage($jobPosting);

        return view('jobs.edit', compact('jobPosting'));
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting)
    {
        $this->authorizeManage($jobPosting);

        // The posting's company is fixed when it is posted. Whatever the request
        // carries, it can never move a posting to another company.
        $data = collect($request->validated())->except(['company_id', 'posted_by'])->all();
        $trackedFields = ['title', 'job_type', 'location', 'application_deadline', 'status', 'slots'];
        $changes = [];

        foreach ($trackedFields as $field) {
            // slots is optional in the request: a request without it keeps the current number.
            if (! array_key_exists($field, $data)) {
                continue;
            }

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

        // Who closed it? A company that sets Closed on an open posting closed it by hand, and
        // setting Open clears the "closed because full" mark. Closed to Closed keeps whatever it was.
        if ($data['status'] === 'open' || $jobPosting->status === 'open') {
            $data['closed_by_slots'] = false;
        }

        $jobPosting->update($data);

        foreach ($changes as $change) {
            FieldChangeLog::create($change);
        }

        // A new slot count can fill the posting (close it) or free a slot (reopen it).
        $slotChange = $jobPosting->syncSlots(auth()->id());

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

        return redirect()->route('jobs.show', $jobPosting)->with('status', match ($slotChange) {
            'closed' => 'Job updated. All interview slots are filled, so the posting is now closed.',
            'reopened' => 'Job updated. A slot is free again, so the posting is open.',
            default => 'Job updated.',
        });
    }

    public function destroy(JobPosting $jobPosting)
    {
        abort_unless(Gate::allows('view', $jobPosting), 404);

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