<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function show()
    {
        return view('coordinator.profile-edit');
    }

    public function update(UpdateProfileRequest $request)
{
    $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $request->user()->update($data);

        return back()->with('status', 'Profile updated.');
    }
}