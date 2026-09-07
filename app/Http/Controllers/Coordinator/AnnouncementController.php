<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\PostAnnouncementRequest;
use App\Jobs\BroadcastAnnouncement;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AnnouncementController extends Controller
{
    public function create()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.announcements.create');
    }

    public function store(PostAnnouncementRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        BroadcastAnnouncement::dispatch(
            $request->validated('title'),
            $request->validated('message'),
            $request->validated('audience'),
            auth()->id(),
        );

        return back()->with('status', 'Announcement queued — you\'ll get a confirmation here once it reaches everyone.');
    }
}