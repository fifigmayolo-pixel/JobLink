<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateApplicationStatusRequest;
use App\Models\JobApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $applications = JobApplication::query()
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', auth()->user()->company->id))
            ->with(['applicant.profile', 'jobPosting'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('date_applied')
            ->paginate(20);

        return view('company.applications.index', compact('applications'));
    }

    public function show(JobApplication $application)
    {
        Gate::authorize('review', $application);

        return view('company.applications.show', compact('application'));
    }

    public function downloadResume(JobApplication $application)
    {
        Gate::authorize('review', $application);

        return Storage::response($application->resume_path);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application)
    {
        Gate::authorize('review', $application);

        $application->update(['status' => $request->validated('status')]);

        if (in_array($application->status, ['interview', 'rejected'])) {
            $this->notifications->applicationStatusChanged($application);
        }

        return back()->with('status', 'Application status updated.');
    }
}