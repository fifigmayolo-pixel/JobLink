<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function store(Request $request, JobApplication $application)
    {
        Gate::authorize('review', $application);

                $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['interview', 'rejected'])],
        ], [
            'status.required' => 'Choose Invite to Interview or Reject before sending.',
            'status.in' => 'Choose Invite to Interview or Reject before sending.',
        ]);

        $decision = $data['status'] ?? null;

        // Same steps as ApplicationController::updateStatus(), done here so the
        // decision and the message go out together when Send Message is clicked.
        if ($decision) {
            $application->update(['status' => $decision]);
            $this->notifications->applicationStatusChanged($application);
        }

        $companyName = $application->jobPosting->company?->company_name ?? 'the company';

        $this->notifications->customMessage(
            recipient: $application->applicant,
            title: "Message from {$companyName}",
            message: $data['message'],
            application: $application
        );

        return back()->with(
            'status',
            $decision ? 'Message sent and application status updated.' : 'Message sent.'
        );
    }
}