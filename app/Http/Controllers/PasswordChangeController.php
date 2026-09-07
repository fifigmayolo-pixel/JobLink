<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;

class PasswordChangeController extends Controller
{
    public function show()
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request)
    {
        $request->user()->update([
            'password' => $request->validated('password'), // 'hashed' cast handles hashing
            'must_change_password' => false,
        ]);

        return redirect()->intended(route('dashboard'));
    }
}