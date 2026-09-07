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
        $request->user()->update($request->validated());

        return back()->with('status', 'Profile updated.');
    }
}