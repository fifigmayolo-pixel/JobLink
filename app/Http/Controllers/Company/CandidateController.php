<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CandidateSearchRequest;
use App\Models\FieldChangeLog;
use App\Models\JobApplication;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use App\Support\CandidateCv;
use App\Support\CandidateSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateController extends Controller
{
    public function index(CandidateSearchRequest $request)
    {
        $filters = $request->validated();

        // The view gets small arrays of allowed fields, never the profile models.
        $candidates = CandidateSearch::query($filters)
            ->paginate(CandidateSearch::PER_PAGE)
            ->withQueryString()
            ->through(fn (StudentAlumniProfile $profile) => CandidateSearch::card($profile));

        return view('company.candidates.index', [
            'candidates' => $candidates,
            'filters' => $filters,
            'skillTerms' => CandidateSearch::skillTerms($filters['skills'] ?? null),
            'filtersActive' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    public function show(Request $request, StudentAlumniProfile $profile)
    {
        // 404, not 403: a profile that is not searchable looks exactly like one that
        // does not exist, so ids can't be probed. (Also covers a removed account.)
        abort_unless(Gate::allows('viewAsCandidate', $profile), 404);

        $company = $request->user();

        $this->logView($company, $profile);

        // If they have already applied to one of this company's jobs, point to the
        // application, which carries the contact details they chose to send.
        $application = JobApplication::query()
            ->where('applicant_id', $profile->user_id)
            ->where('status', '!=', 'withdrawn')
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', $company->company->id))
            ->with('jobPosting')
            ->latest('date_applied')
            ->first();

        return response()
            ->view('company.candidates.show', [
                'candidateId' => $profile->id,
                'cv' => CandidateCv::forCompany($profile),
                'application' => $application,
            ])
            // Personal details: no caching by browsers or proxies, no indexing.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * One audit entry per company, candidate and day, so reloading the page does not
     * fill the log. The Coordinator sees it in the Audit Log.
     */
    private function logView(User $company, StudentAlumniProfile $profile): void
    {
        $alreadyLoggedToday = FieldChangeLog::query()
            ->where('action', 'candidate_profile_viewed')
            ->where('changed_by', $company->id)
            ->where('user_id', $profile->user_id)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($alreadyLoggedToday) {
            return;
        }

        FieldChangeLog::create([
            'action' => 'candidate_profile_viewed',
            'user_id' => $profile->user_id,
            'changed_by' => $company->id,
            'details' => ($company->company?->company_name ?? 'A company').' opened this searchable profile.',
        ]);
    }
}
