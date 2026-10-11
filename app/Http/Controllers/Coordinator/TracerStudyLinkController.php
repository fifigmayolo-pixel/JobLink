<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\TracerStudyLinkRequest;
use App\Models\FieldChangeLog;
use App\Models\TracerStudyLink;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;

class TracerStudyLinkController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.tracer-study-links.index', [
            'currentLink' => TracerStudyLink::current(),
        ]);
    }

    /**
     * "Replace" per Phase 1/7: deactivates the current link and creates a
     * new row, rather than editing the URL in place — keeps history. History
     * itself now lives in the Audit Log (tracer_study_update entries) rather
     * than a table on this page. Every replacement also notifies all alumni,
     * since the pinned card on the Notifications page only shows the current
     * link to whoever happens to visit — it gives no signal that this
     * year's link is a new one.
     */
    public function store(TracerStudyLinkRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $previousLink = TracerStudyLink::current();

        TracerStudyLink::active()->update(['is_active' => false]);

        TracerStudyLink::create([
            'url' => $request->validated('url'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        FieldChangeLog::create([
            'action' => 'tracer_study_update',
            'changed_by' => auth()->id(),
            'field_name' => 'url',
            'old_value' => $previousLink?->url,
            'new_value' => $request->validated('url'),
        ]);

        $alumniIds = User::alumni()->pluck('id')->all();

        if (! empty($alumniIds)) {
            $this->notifications->announcement(
                'New Tracer Study Available',
                "A new tracer study has been posted — please take a few minutes to fill it out. If you've already completed it, you can ignore this.",
                $alumniIds,
                'tracer_study'
            );
        }

        return back()->with('status', 'Tracer study link updated.');
    }
}