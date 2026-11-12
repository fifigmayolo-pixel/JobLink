<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateApplicationStatusRequest;
use App\Models\JobApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class ApplicationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'past' ? 'past' : 'active';
        $companyId = auth()->user()->company->id;

        $own = fn () => JobApplication::query()
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', $companyId));

        $activeCount = $own()->inProgress()->count();
        $pastCount = $own()->finished()->count();

        $applications = $own()
            ->when($tab === 'past', fn ($q) => $q->finished(), fn ($q) => $q->inProgress())
            ->with(['applicant.profile', 'jobPosting'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('date_applied')
            ->paginate(20)
            ->withQueryString();

        return view('company.applications.index', compact('applications', 'tab', 'activeCount', 'pastCount'));
    }

    public function show(JobApplication $application)
    {
        Gate::authorize('review', $application);

        return view('company.applications.show', compact('application'));
    }

   

    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application)
    {
        Gate::authorize('review', $application);

        abort_if($application->isWithdrawn(), 403, 'This application was withdrawn by the applicant.');

        $application->update(['status' => $request->validated('status')]);

        if (in_array($application->status, ['interview', 'rejected'])) {
            $this->notifications->applicationStatusChanged($application);
        }

        return back()->with('status', 'Application status updated.');
    }
}