<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\ImportRosterRequest;
use App\Http\Requests\Coordinator\QuickAddStudentRequest;
use App\Jobs\ImportStudentRoster;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Support\Facades\Gate;

class StudentRosterController extends Controller
{
    public function showQuickAddForm()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.roster.quick-add');
    }

    public function quickAdd(QuickAddStudentRequest $request, AccountProvisioningService $provisioning)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $provisioning->quickAddStudentOrAlumni(
            $request->validated('student_id_number'),
            $request->validated('full_name'),
            $request->validated('role')
        );

        return back()->with('status', 'Added. Ready for the next one.');
    }

    public function showImportForm()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.roster.import');
    }

    public function import(ImportRosterRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $path = $request->file('roster')->store('roster-imports');

        ImportStudentRoster::dispatch($path, $request->validated('role'), auth()->id());

        return back()->with('status', "Import queued — you'll get a notification here when it's done.");
    }
}