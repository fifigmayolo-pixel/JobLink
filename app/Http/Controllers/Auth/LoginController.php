<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StudentId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        $field = $isEmail ? 'email' : 'student_id_number';

        // Emails are used as typed. Anything else is treated as a Student ID
        // and cleaned up, so "23-ac-2344" or "23ac2344" still log in as
        // 23-AC-2344. IDs already in the database in another format are
        // left alone by normalize(), so existing accounts keep working.
        $identifier = $isEmail ? $login : StudentId::normalize($login);

        // Helpful nudge instead of a generic wrong-password message, since
        // pre-activation accounts have an unguessable password by design.
        if ($field === 'student_id_number'
            && User::where('student_id_number', $identifier)->whereNull('activated_at')->exists()) {
            throw ValidationException::withMessages([
                'login' => 'This account hasn\'t been activated yet — use "Activate your account" below.',
            ]);
        }

        // No 'remember me' — there's no remember_token column (Phase 5 decision)
        if (! Auth::attempt([$field => $identifier, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'login' => "Those credentials don't match our records.",
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}