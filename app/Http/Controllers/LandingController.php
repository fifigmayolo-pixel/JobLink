<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Support\CompanyAccent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // to appear here.
        $featuredJobs = Cache::remember('landing.featured-jobs', 300, function () {
            $typeLabels = [
                'full_time' => 'Full-time',
                'part_time' => 'Part-time',
                'internship' => 'Internship',
            ];

            // Same visibility rules as the Open Positions page (JobPostingController@index).
            return JobPosting::query()
                ->approved()
                ->open()
                ->with('company')
                ->latest('date_posted')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(function (JobPosting $job) use ($typeLabels) {
                    $name = $job->company?->company_name;
                    $accent = CompanyAccent::for($job->company?->id, $name);

                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company' => $name ?? 'Partner company',
                        'initial' => CompanyAccent::initial($name),
                        'logo' => $job->company?->logo ? Storage::url($job->company->logo) : null,
                        'location' => $job->location,
                        'type' => $typeLabels[$job->job_type] ?? Str::headline($job->job_type ?? ''),
                        'deadline' => $job->application_deadline->toDateString(),
                        'accentSoft' => $accent['soft'],
                        'accentText' => $accent['text'],
                    ];
                })
                ->all();
        });

        return view('landing', compact('featuredJobs'));
    }
}