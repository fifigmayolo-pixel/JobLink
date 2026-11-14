<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Support\CompanyAccent;
use App\Support\JobCardData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    public function __invoke()
    {
        // Logged-in users skip the marketing page, same as before.
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        // Public page, so cache briefly instead of querying on every visit.
        // Stored as plain arrays (not Eloquent models) so the cache stays
        // safe across deploys. A newly approved job can take up to 5 minutes
        // to appear here. The key has a version suffix: bump it when the shape
        // of these arrays changes, so a deploy never reads old-shaped entries.
        $featuredJobs = Cache::remember('landing.featured-jobs.v2', 300, function () {
            // listed() = the exact same visibility rules as the Open Positions page
            // (JobPostingController@index): approved, open (status and deadline),
            // and the company's login is not archived.
            return JobPosting::query()
                ->listed()
                ->with('company')
                ->latest('date_posted')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(function ($job) {
                    $name = $job->company?->company_name;
                    $accent = CompanyAccent::for($job->company?->id, $name);

                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company' => $name ?? 'Partner company',
                        'initial' => CompanyAccent::initial($name),
                        'logo' => $job->company?->logo ? Storage::disk('public')->url($job->company->logo) : null,
                        'location' => $job->location,
                        'type' => JobCardData::typeLabel($job->job_type),
                        'excerpt' => JobCardData::excerpt($job, 140),
                        'deadline' => $job->application_deadline->toDateString(),
                        'posted' => $job->date_posted?->toDateString(),
                        'accentSoft' => $accent['soft'],
                        'accentText' => $accent['text'],
                    ];
                })
                ->all();
        });

        return view('landing', compact('featuredJobs'));
    }
}
