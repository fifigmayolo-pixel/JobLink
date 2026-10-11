<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\OverrideLockedFieldRequest;
use App\Models\FieldChangeLog;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Coordinator\DestroyRecordRequest;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $profiles = StudentAlumniProfile::query()
            ->with('user')
            ->whereHas('user')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('student_id_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->program, fn ($q, $program) => $q->where('course_program', $program))
            ->when($request->year_graduated, fn ($q, $yearGraduated) => $q->where('year_graduated', $yearGraduated))
            ->paginate(25);

        return view('coordinator.records.index', compact('profiles'));
    }

    public function show(StudentAlumniProfile $profile)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.records.show', compact('profile'));
    }

    /**
     * Overrides a locked field (Phase 1: "Job Coordinator CAN EDIT LOCKED
     * FIELDS when corrections are necessary"). Every change is written to
     * field_change_logs — old value, new value, who, when, why.
     */
    public function overrideLockedField(OverrideLockedFieldRequest $request, StudentAlumniProfile $profile)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $field = $request->validated('field_name');
        $oldValue = $profile->{$field};
        $newValue = $request->validated('new_value');

        $profile->update([$field => $newValue]);

        FieldChangeLog::create([
            'user_id' => $profile->user_id,
            'changed_by' => auth()->id(),
            'field_name' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $request->validated('reason'),
        ]);

        return back()->with('status', 'Field updated and logged.');
    }

    public function destroy(DestroyRecordRequest $request, StudentAlumniProfile $profile)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        FieldChangeLog::create([
            'action' => 'record_deletion',
            'user_id' => $profile->user_id,
            'changed_by' => auth()->id(),
            'reason' => $request->validated('reason'),
        ]);

        $profile->user->delete(); // soft delete — preserves the profile + audit history

        return redirect()->route('coordinator.records.index')->with('status', 'Record removed.');
    }
}