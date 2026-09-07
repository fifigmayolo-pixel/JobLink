<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;

class UpdatePasswordController extends Controller
{
    public function show()
    {
        return view('auth.password-edit');
    }

    public function update(UpdatePasswordRequest $request)
    {
        $request->user()->update([
            'password' => $request->validated('password'), // 'hashed' cast handles hashing
        ]);

        return redirect()->route('dashboard')->with('status', 'Password updated.');
    }
}