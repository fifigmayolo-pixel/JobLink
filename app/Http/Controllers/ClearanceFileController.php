<?php

namespace App\Http\Controllers;

use App\Models\FieldChangeLog;
use App\Models\ProfileClearance;
use App\Models\User;
use App\Support\ClearanceStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Streams one clearance PDF. This is the only way a clearance file is ever opened.
 *
 * There are no public links and no temporary links: the file is read from the private
 * disk and passed through, after ProfileClearancePolicy has said yes. Anyone it says no
 * to gets a plain 404, the same as for a clearance that does not exist.
 */
class ClearanceFileController extends Controller
{
    public function __invoke(Request $request, ProfileClearance $clearance)
    {
        abort_unless(Gate::allows('view', $clearance), 404);

        $disk = ClearanceStore::disk();

        abort_unless($disk->exists($clearance->file_path), 404);

        $this->logCompanyOpen($request->user(), $clearance);

        return $disk->response($clearance->file_path, $clearance->downloadName(), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ], 'inline');
    }

    /**
     * Each time a company opens a clearance is recorded, at most once a day per company and
     * document, like opening a searchable profile. The owner opening their own file is not.
     */
    private function logCompanyOpen(User $viewer, ProfileClearance $clearance): void
    {
        if ($viewer->role !== 'company') {
            return;
        }

        $ownerId = $clearance->profile->user_id;
        $field = $clearance->type.'_clearance';

        $alreadyToday = FieldChangeLog::query()
            ->where('action', 'clearance_opened')
            ->where('changed_by', $viewer->id)
            ->where('user_id', $ownerId)
            ->where('field_name', $field)
            ->where('created_at', '>=', today())
            ->exists();

        if ($alreadyToday) {
            return;
        }

        FieldChangeLog::create([
            'action' => 'clearance_opened',
            'user_id' => $ownerId,
            'changed_by' => $viewer->id,
            'field_name' => $field,
            'details' => $clearance->label().' opened by '.($viewer->company?->company_name ?? 'a company').'.',
        ]);
    }
}
