<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SendMessageRequest;
use App\Models\JobApplication;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function store(SendMessageRequest $request, JobApplication $application)
    {
        Gate::authorize('review', $application);

        $this->notifications->customMessage(
            recipient: $application->applicant,
            title: $request->validated('title'),
            message: $request->validated('message'),
            application: $application
        );

        return back()->with('status', 'Message sent.');
    }
}