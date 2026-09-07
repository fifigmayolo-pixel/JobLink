<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetActivationPasswordRequest;
use App\Http\Requests\Auth\VerifyIdentityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivateAccountController extends Controller
{
    public function showVerifyForm()
    {
        return view('auth.activate-verify');
    }

    public function verify(VerifyIdentityRequest $request)
    {
        $user = User::whereIn('role', ['student', 'alumni'])
            ->where('student_id_number', $request->validated('student_id_number'))
            ->whereNull('activated_at') // one-time — already-activated accounts can't be re-claimed here
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($request->validated('full_name')))])
            ->first();

        if (! $user) {
            return back()->withErrors([
                'full_name' => "We couldn't find a matching, not-yet-activated record. Double check your Student ID and full name, or see your Job Coordinator.",
            ]);
        }

        session(['activation_user_id' => $user->id]);

        return redirect()->route('activate.password');
    }

    public function showPasswordForm()
    {
        abort_unless(session('activation_user_id'), 403);

        return view('auth.activate-password');
    }

    public function setPassword(SetActivationPasswordRequest $request)
    {
        $userId = session('activation_user_id');
        abort_unless($userId, 403);

        $user = User::whereNull('activated_at')->findOrFail($userId);

        $user->update([
            'password' => $request->validated('password'),
            'must_change_password' => false, // they just chose it deliberately
            'activated_at' => now(),
        ]);

        session()->forget('activation_user_id');

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}