<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\TracerStudyLinkRequest;
use App\Models\TracerStudyLink;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TracerStudyLinkController extends Controller
{
    public function index()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $links = TracerStudyLink::latest()->get();

        return view('coordinator.tracer-study-links.index', compact('links'));
    }

    /**
     * "Replace" per Phase 1/7: deactivates the current link and creates a
     * new row, rather than editing the URL in place — keeps history.
     */
    public function store(TracerStudyLinkRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        TracerStudyLink::active()->update(['is_active' => false]);

        TracerStudyLink::create([
            'url' => $request->validated('url'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Tracer study link updated.');
    }
}