<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClearanceRequest;
use App\Models\FieldChangeLog;
use App\Support\ClearanceStore;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * "My Clearances": the owner uploads, replaces and removes their own NBI and barangay
 * clearance. Everything here works on the logged-in person's own profile; no id from the
 * request ever picks whose clearance it is.
 *
 * Opening a file is a different controller (ClearanceFileController), because that is the
 * one a company also uses.
 */
class ClearanceController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.complete');
        }

        return view('clearances.edit', [
            'profile' => $profile,
            'clearances' => $profile->clearances()->get()->keyBy('type'),
            'types' => config('clearances.types'),
            'needsConsent' => $profile->clearance_consent_at === null,
            'maxKb' => (int) config('clearances.max_kb', 4096),
            'latestDate' => now()->addYears((int) config('clearances.max_validity_years', 2))->toDateString(),
        ]);
    }

    public function store(StoreClearanceRequest $request, string $type, ClearanceStore $store)
    {
        $user = $request->user();
        $profile = $user->profile;
        $label = config("clearances.types.{$type}.label");

        $validUntil = Carbon::createFromFormat('Y-m-d', $request->validated('valid_until'))->startOfDay();

        try {
            $clearance = $store->save($profile, $type, $request->file('file'), $validUntil);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'file' => 'We could not save the file. Please try again in a moment.',
            ]);
        }

        if ($profile->clearance_consent_at === null) {
            $profile->forceFill(['clearance_consent_at' => now()])->save();
        }

        FieldChangeLog::create([
            'action' => $clearance->wasRecentlyCreated ? 'clearance_uploaded' : 'clearance_replaced',
            'user_id' => $user->id,
            'changed_by' => $user->id,
            'field_name' => $type.'_clearance',
            'new_value' => $validUntil->toDateString(),
            'details' => $label.', valid until '.$validUntil->format('M j, Y').'.',
        ]);

        return redirect()->route('clearances.edit')->with('status', $clearance->wasRecentlyCreated
            ? $label.' saved. Companies you apply to can open it.'
            : $label.' replaced. The old file was deleted.');
    }

    public function destroy(Request $request, string $type, ClearanceStore $store)
    {
        $user = $request->user();
        $label = config("clearances.types.{$type}.label");

        $clearance = $user->profile?->clearances()->where('type', $type)->first();

        if (! $clearance) {
            return redirect()->route('clearances.edit')->with('status', 'There was no '.$label.' to remove.');
        }

        $store->remove($clearance);

        FieldChangeLog::create([
            'action' => 'clearance_removed',
            'user_id' => $user->id,
            'changed_by' => $user->id,
            'field_name' => $type.'_clearance',
            'details' => $label.' removed.',
        ]);

        return redirect()->route('clearances.edit')
            ->with('status', $label.' removed and its file deleted. Upload a new one before you apply for a job.');
    }
}
