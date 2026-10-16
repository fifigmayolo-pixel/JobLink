<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationApprovedMail;
use App\Mail\RegistrationRejectedMail;
use App\Models\FieldChangeLog;
use App\Models\RegistrationRequest;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class RegistrationReviewController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

        public function index(Request $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : 'pending';

        $search = trim((string) $request->query('search'));
        $type = in_array($request->query('type'), ['student', 'alumni'], true)
            ? $request->query('type')
            : null;

        // The same search + type filters apply to the list and to the tab counts.
        $applyFilters = function ($query) use ($search, $type) {
            return $query
                ->when($search !== '', function ($q) use ($search) {
                    $like = '%'.addcslashes($search, '%_\\').'%';

                    $q->where(function ($w) use ($like) {
                        $w->where('student_id_number', 'like', $like)
                            ->orWhere('full_name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
                })
                ->when($type, fn ($q) => $q->where('applicant_type', $type));
        };

        $requests = $applyFilters(RegistrationRequest::query())
            ->with('reviewer')
            ->where('status', $status)
            ->when(
                $status === 'pending',
                fn ($q) => $q->orderBy('created_at'),
                fn ($q) => $q->orderByDesc('reviewed_at')
            )
            ->paginate(20)
            ->withQueryString();

        $counts = $applyFilters(RegistrationRequest::query())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $flags = $status === 'pending' ? $this->flagsFor($requests->getCollection()) : [];

        return view('coordinator.registration-requests.index', compact('requests', 'status', 'counts', 'flags', 'search', 'type'));
    }

    public function approve(RegistrationRequest $registrationRequest, AccountProvisioningService $provisioning)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        // Locked re-read inside a transaction so a double-click or two
        // Coordinators can't approve the same request twice.
        $result = DB::transaction(function () use ($registrationRequest, $provisioning) {
            $req = RegistrationRequest::lockForUpdate()->findOrFail($registrationRequest->id);

            if ($req->status !== RegistrationRequest::STATUS_PENDING) {
                return ['error' => 'This request has already been reviewed.'];
            }

            // withTrashed(): a removed account still owns its Student ID (unique index).
            if (User::withTrashed()->where('student_id_number', $req->student_id_number)->exists()) {
                return ['error' => 'This Student ID already has an account, so the request cannot be approved. Reject it instead.'];
            }

            $user = $provisioning->quickAddStudentOrAlumni(
                $req->student_id_number,
                $req->full_name,
                $req->applicant_type
            );

            FieldChangeLog::create([
                'action' => 'student_alumni_added',
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'details' => 'Added as '.ucfirst($user->role).' (approved registration request).',
            ]);

            $req->update([
                'status' => RegistrationRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'user_id' => $user->id,
            ]);

            return ['request' => $req];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['registration' => $result['error']]);
        }

        $req = $result['request'];

        $this->queueMail($req->email, new RegistrationApprovedMail(
            fullName: $req->full_name,
            studentId: $req->student_id_number,
            applicantType: $req->applicant_type,
            activateUrl: route('activate.verify'),
        ));

        return back()->with('status', "Approved. {$req->full_name} can now activate their account — an email was queued for {$req->email}.");
    }

    public function reject(Request $request, RegistrationRequest $registrationRequest)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = filled($data['reason'] ?? null) ? trim($data['reason']) : null;

        $req = DB::transaction(function () use ($registrationRequest, $reason) {
            $req = RegistrationRequest::lockForUpdate()->findOrFail($registrationRequest->id);

            if ($req->status !== RegistrationRequest::STATUS_PENDING) {
                return null;
            }

            $req->update([
                'status' => RegistrationRequest::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $req;
        });

        if (! $req) {
            return back()->withErrors(['registration' => 'This request has already been reviewed.']);
        }

        $this->queueMail($req->email, new RegistrationRejectedMail(
            fullName: $req->full_name,
            reason: $reason,
            registerUrl: route('register.show'),
        ));

        return back()->with('status', "Request from {$req->full_name} rejected. An email was queued for {$req->email}.");
    }

    /**
     * An email problem must never undo or block a review that already happened.
     */
    private function queueMail(string $to, $mailable): void
    {
        try {
            Mail::to($to)->queue($mailable);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Helps the Coordinator judge each pending request against what's already
     * in the system: Student ID taken, same name under another ID, email
     * already on an existing profile.
     */
    private function flagsFor(Collection $requests): array
    {
        if ($requests->isEmpty()) {
            return [];
        }

        $lower = fn ($v) => mb_strtolower(trim((string) $v));

        $ids = $requests->pluck('student_id_number')->map($lower)->unique()->values()->all();
        $names = $requests->pluck('full_name')->map($lower)->unique()->values()->all();
        $emails = $requests->pluck('email')->map($lower)->unique()->values()->all();

        $marks = fn (array $list) => implode(',', array_fill(0, count($list), '?'));

        $takenIds = User::withTrashed()
            ->whereRaw('LOWER(TRIM(student_id_number)) IN ('.$marks($ids).')', $ids)
            ->pluck('student_id_number')
            ->map($lower)
            ->all();

        $sameName = User::withTrashed()
            ->whereIn('role', ['student', 'alumni'])
            ->whereRaw('LOWER(TRIM(full_name)) IN ('.$marks($names).')', $names)
            ->get(['student_id_number', 'full_name'])
            ->groupBy(fn ($u) => $lower($u->full_name));

        $emailsInUse = StudentAlumniProfile::query()
            ->whereRaw('LOWER(TRIM(personal_email)) IN ('.$marks($emails).')', $emails)
            ->pluck('personal_email')
            ->map($lower)
            ->all();

        $flags = [];

        foreach ($requests as $r) {
            $flags[$r->id] = [
                'id_taken' => in_array($lower($r->student_id_number), $takenIds, true),
                'same_name_ids' => $sameName->get($lower($r->full_name), collect())
                    ->pluck('student_id_number')
                    ->filter()
                    ->reject(fn ($id) => $lower($id) === $lower($r->student_id_number))
                    ->values()
                    ->all(),
                'email_in_use' => in_array($lower($r->email), $emailsInUse, true),
            ];
        }

        return $flags;
    }
}