<?php

namespace App\Http\Controllers;

use App\Models\TracerStudyAccess;
use App\Models\TracerStudyLink;

class TracerStudyController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        if ($user->role !== 'alumni') {
            return redirect()->route('dashboard');
        }

        $link = TracerStudyLink::current();

        return view('alumni.tracer-study-intro', [
            'link' => $link,
            'accessed' => $link ? $link->accessedBy($user) : false,
            'profileComplete' => (bool) $user->profile?->profile_completed_at,
        ]);
    }

    /**
     * Records that the signed-in alumnus opened the current tracer study, then
     * sends them to the Coordinator's stored link. The user always comes from
     * the session and the destination always comes from the database, never
     * from the request.
     */
    public function access()
    {
        $user = auth()->user();
        abort_unless($user->role === 'alumni', 403);

        $link = TracerStudyLink::current();

        if (! $link) {
            return redirect()->route('tracer-study.show')
                ->with('status', 'There is no active tracer study right now.');
        }

        // Only ever send people to a web address.
        abort_unless(preg_match('#^https?://#i', $link->url), 404);

        // One record per alumnus per study; opening it again creates nothing new.
        TracerStudyAccess::createOrFirst(
            ['user_id' => $user->id, 'tracer_study_link_id' => $link->id],
            ['accessed_at' => now()],
        );

        return redirect()->away($link->url);
    }
}