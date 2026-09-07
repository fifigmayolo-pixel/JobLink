<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\BulkUpdateStatusRequest;
use App\Jobs\ConvertAlumniToStudents;
use App\Jobs\ConvertStudentsToAlumni;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class BulkUpdateController extends Controller
{
    public function store(BulkUpdateStatusRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        if ($request->validated('direction') === 'to_alumni') {
            ConvertStudentsToAlumni::dispatch(
                $request->validated('profile_ids'),
                $request->validated('year_graduated'),
                auth()->id(),
            );

            return back()->with('status', 'Bulk conversion to Alumni queued — it will process in the background.');
        }

        ConvertAlumniToStudents::dispatch(
            $request->validated('profile_ids'),
            $request->validated('year_level'),
            auth()->id(),
        );

        return back()->with('status', 'Bulk conversion to Student queued — it will process in the background.');
    }
}