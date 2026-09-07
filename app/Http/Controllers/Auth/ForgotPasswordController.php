<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FindAccountRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function showFindAccountForm()
    {
        return view('auth.forgot-password');
    }

    public function findAccount(FindAccountRequest $request)
    {
        $identifier = $request->validated('identifier');
        $isEmail = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::whereIn('role', ['job_coordinator', 'peso', 'company'])->where('email', $identifier)->first()
            : User::whereIn('role', ['student', 'alumni'])->where('student_id_number', $identifier)->first();

        if (! $user) {
            return back()->withErrors([
                'identifier' => "We couldn't find an account matching that Student ID or email.",
            ]);
        }

        $destination = $this->destinationEmailFor($user);

        if (! $destination) {
            return back()->withErrors([
                'identifier' => 'No recovery email is on file for this account yet. Please see your Job Coordinator for help.',
            ]);
        }

        $this->issueCodeFor($user, $destination);

        session(['password_reset_user_id' => $user->id]);

        return redirect()->route('password.forgot.verify');
    }

    public function showVerifyForm()
    {
        abort_unless(session('password_reset_user_id'), 403);

        return view('auth.forgot-password-verify');
    }

    public function verifyCode(VerifyResetCodeRequest $request)
    {
        $userId = session('password_reset_user_id');
        abort_unless($userId, 403);

        $record = PasswordResetCode::where('user_id', $userId)->first();

        if (! $record || $record->isExpired()) {
            return back()->withErrors([
                'code' => 'That code has expired. Request a new one below.',
            ]);
        }

        if ($record->attempts >= 5) {
            return back()->withErrors([
                'code' => 'Too many incorrect attempts. Request a new code below.',
            ]);
        }

        if (! Hash::check($request->validated('code'), $record->code)) {
            $record->increment('attempts');

            return back()->withErrors([
                'code' => "That code doesn't match.",
            ]);
        }

        $record->delete(); // single-use — consumed on success

        session(['password_reset_verified' => true]);

        return redirect()->route('password.forgot.reset');
    }

    public function resendCode()
    {
        $userId = session('password_reset_user_id');
        abort_unless($userId, 403);

        $user = User::findOrFail($userId);
        $destination = $this->destinationEmailFor($user);

        if (! $destination) {
            return back()->withErrors(['code' => 'No recovery email is on file for this account.']);
        }

        $this->issueCodeFor($user, $destination);

        return back()->with('status', 'A new code has been sent.');
    }

    public function showResetForm()
    {
        abort_unless(session('password_reset_user_id') && session('password_reset_verified'), 403);

        return view('auth.forgot-password-reset');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $userId = session('password_reset_user_id');
        abort_unless($userId && session('password_reset_verified'), 403);

        $user = User::findOrFail($userId);

        $user->update([
            'password' => $request->validated('password'), // 'hashed' cast handles hashing
        ]);

        session()->forget(['password_reset_user_id', 'password_reset_verified']);

        return redirect()->route('login')->with('status', 'Password reset — you can log in now.');
    }

    private function destinationEmailFor(User $user): ?string
    {
        return in_array($user->role, ['job_coordinator', 'peso', 'company'])
            ? $user->email
            : $user->profile?->personal_email;
    }

    private function issueCodeFor(User $user, string $destination): void
    {
        $code = (string) random_int(100000, 999999);

        PasswordResetCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
            ]
        );

        Mail::to($destination)->send(new PasswordResetCodeMail($code));
    }
}