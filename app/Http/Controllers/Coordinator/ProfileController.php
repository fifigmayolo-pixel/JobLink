<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\UpdateProfileRequest;
use App\Mail\CoordinatorEmailChangedMail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class ProfileController extends Controller
{
    public function show()
    {
        return view('coordinator.profile-edit');
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // Only real user columns: current_password and the email confirmation are checks, not data.
        $data = Arr::only($request->validated(), ['full_name', 'profile_picture', 'email']);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('profile-pictures', 'public');
        } else {
            unset($data['profile_picture']); // no new photo: keep the current one
        }

        $oldEmail = $user->email;
        $emailChanged = strtolower($data['email']) !== strtolower((string) $oldEmail);

        if (! $emailChanged) {
            unset($data['email']);
        }

        $user->update($data);

        if ($emailChanged) {
            $this->notifyEmailChanged($user, (string) $oldEmail);
        }

        return back()->with('status', $emailChanged
            ? "Profile updated. Your login email is now {$user->email}."
            : 'Profile updated.');
    }

    /**
     * Alerts the old address, and confirms to the new one that it works.
     * A mail problem never undoes the change.
     */
    private function notifyEmailChanged(User $user, string $oldEmail): void
    {
        foreach ([['old', $oldEmail], ['new', $user->email]] as [$audience, $to]) {
            try {
                Mail::to($to)->send(new CoordinatorEmailChangedMail(
                    fullName: $user->full_name ?? $user->email,
                    oldEmail: $oldEmail,
                    newEmail: $user->email,
                    audience: $audience,
                    loginUrl: route('login'),
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}