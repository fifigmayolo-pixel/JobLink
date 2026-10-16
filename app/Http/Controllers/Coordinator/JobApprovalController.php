<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\RejectJobPostingRequest;
use App\Jobs\NotifyNewJobPosting;
use App\Models\FieldChangeLog;
use App\Models\JobPosting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobApprovalController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        Gate::authorize('review', JobPosting::class);

        $status = in_array($request->status, ['pending', 'approved', 'rejected'], true)
            ? $request->status
            : 'pending';

        $jobPostings = JobPosting::query()
            ->with(['company', 'poster', 'reviewer'])
            ->where('approval_status', $status)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = JobPosting::query()
            ->selectRaw('approval_status, count(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return view('coordinator.job-approvals.index', compact('jobPostings', 'status', 'counts'));
    }

    public function approve(JobPosting $jobPosting)
    {
        Gate::authorize('review', $jobPosting);

        // Conditional update: if two Coordinators click at once, only one wins,
        // so the company and students are never notified twice.
        $updated = JobPosting::query()
            ->whereKey($jobPosting->id)
            ->where('approval_status', 'pending')
            ->update([
                'approval_status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

        if (! $updated) {
            return back()->with('status', 'That posting has already been reviewed.');
        }

        $jobPosting->refresh();

        FieldChangeLog::create([
            'action' => 'job_posting_approved',
            'changed_by' => auth()->id(),
            'job_posting_id' => $jobPosting->id,
            'details' => 'Approved for '.($jobPosting->company?->company_name ?? 'a company').'.',
        ]);

        if ($poster = $jobPosting->poster) {
            $this->notifications->jobPostingReviewed($jobPosting, $poster, true);
        }

        // Students and alumni hear about it only now that it is visible to them.
        NotifyNewJobPosting::dispatch($jobPosting->id);

        return back()->with('status', 'Posting approved. Students and alumni will be notified.');
    }

    public function reject(RejectJobPostingRequest $request, JobPosting $jobPosting)
    {
        Gate::authorize('review', $jobPosting);

        $reason = $request->validated('reason');

        $updated = JobPosting::query()
            ->whereKey($jobPosting->id)
            ->where('approval_status', 'pending')
            ->update([
                'approval_status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

        if (! $updated) {
            return back()->with('status', 'That posting has already been reviewed.');
        }

        $jobPosting->refresh();

        FieldChangeLog::create([
            'action' => 'job_posting_rejected',
            'changed_by' => auth()->id(),
            'job_posting_id' => $jobPosting->id,
            'reason' => $reason,
            'details' => 'Rejected for '.($jobPosting->company?->company_name ?? 'a company').'.',
        ]);

        if ($poster = $jobPosting->poster) {
            $this->notifications->jobPostingReviewed($jobPosting, $poster, false, $reason);
        }

        return back()->with('status', 'Posting rejected. The company has been notified with your reason.');
    }
}