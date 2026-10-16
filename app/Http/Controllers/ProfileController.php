<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileCompletionRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\PhilippineLocations;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function showCompletionForm()
    {
        return view('profile.complete', [
            'isStudent' => auth()->user()->role === 'student',
        ]);
    }

        public function storeCompletion(ProfileCompletionRequest $request)
    {
        $user = $request->user();

        // accept_terms is a checkbox, not a profile column.
        $data = Arr::except($request->validated(), ['accept_terms']);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $data['full_name'] = $user->full_name; // set at registration, not re-typed here
        $data['status'] = $user->role;
        $data['profile_completed_at'] = now();

        DB::transaction(function () use ($user, $data) {
            $user->profile()->create($data);

            // The agreement happens on this form, so record it with the profile.
            if (! $user->terms_accepted_at) {
                $user->update(['terms_accepted_at' => now()]);
            }
        });

        return redirect()->route('dashboard')->with('status', 'Profile completed.');
    }

    public function citiesForProvince(Request $request)
    {
        return response()->json(
            PhilippineLocations::citiesForProvince($request->query('province'))
        );
    }

    public function barangaysForCity(Request $request)
    {
        return response()->json(
            PhilippineLocations::barangaysForCity($request->query('province'), $request->query('city'))
        );
    }

    public function edit()
    {
        return view('profile.edit', ['profile' => auth()->user()->profile]);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $request->user()->profile()->update($data);

        return back()->with('status', 'Profile updated.');
    }
}