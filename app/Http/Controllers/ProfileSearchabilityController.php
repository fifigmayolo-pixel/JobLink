<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSearchabilityRequest;
use App\Models\FieldChangeLog;
use App\Support\CandidateCv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileSearchabilityController extends Controller
{
    /**
     * The "Make my profile searchable by partner companies" switch.
     * Off by default. Every change is written to the audit log with the date, which
     * is the consent trail for the Data Privacy Act.
     */
    public function update(UpdateSearchabilityRequest $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        $on = $request->boolean('searchable');

        if ($on === (bool) $profile->searchable_by_companies) {
            return back()->with('status', 'No change: your setting is already '.($on ? 'on' : 'off').'.');
        }

        DB::transaction(function () use ($profile, $user, $on) {
            $profile->update([
                'searchable_by_companies' => $on,
                // The moment of consent. Cleared again when they turn it off.
                'searchable_consented_at' => $on ? now() : null,
            ]);

            FieldChangeLog::create([
                'action' => $on ? 'searchable_enabled' : 'searchable_disabled',
                'user_id' => $user->id,
                'changed_by' => $user->id,
                'details' => $on
                    ? 'Chose to make their profile searchable by partner companies.'
                    : 'Turned off "searchable by partner companies".',
            ]);
        });

        if (! $on) {
            return back()->with('status', 'Your profile is no longer searchable. Companies you applied to still have the CV you sent them.');
        }

        $profile = $profile->fresh();

        if ($profile->isSearchable()) {
            return back()->with('status', 'Your profile is now searchable by partner companies.');
        }

        $blockers = $profile->searchabilityBlockers();

        return back()->with('status', 'Saved. You will appear in company search once your profile is ready.'
            .($blockers ? ' Still needed: '.implode(', ', $blockers).'.' : ''));
    }

    /**
     * Exactly what a company sees in search, so the student can check before (or after)
     * turning the switch on.
     */
    public function preview(Request $request)
    {
        $profile = $request->user()->profile;

        abort_unless($profile, 403);

        return view('profile.searchable-preview', [
            'cv' => CandidateCv::forCompany($profile),
            'switchOn' => (bool) $profile->searchable_by_companies,
            'visibleNow' => $profile->isSearchable(),
            'missing' => $profile->searchabilityBlockers(),
        ]);
    }
}
