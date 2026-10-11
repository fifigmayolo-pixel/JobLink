<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'last_30_days');

        $statusCounts = JobApplication::query()
            ->when($range === 'today', fn ($q) => $q->whereDate('date_applied', now()->toDateString()))
            ->when($range === 'last_7_days', fn ($q) => $q->where('date_applied', '>=', now()->subDays(7)->startOfDay()))
            ->when($range === 'last_30_days', fn ($q) => $q->where('date_applied', '>=', now()->subDays(30)->startOfDay()))
            ->when($range === 'last_100_days', fn ($q) => $q->where('date_applied', '>=', now()->subDays(100)->startOfDay()))
            ->when($range === 'this_month', fn ($q) => $q->where('date_applied', '>=', now()->startOfMonth()))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $pendingCount = (int) $statusCounts->get('pending', 0);
        $interviewCount = (int) $statusCounts->get('interview', 0);
        $rejectedCount = (int) $statusCounts->get('rejected', 0);

        return view('coordinator.dashboard', [
            'totalStudents' => User::students()->count(),
            'totalAlumni' => User::alumni()->count(),
            'totalCompanies' => Company::count(),
            'totalJobsPosted' => JobPosting::count(),
            'totalApplications' => JobApplication::count(),
            'recentJobPostings' => JobPosting::with('company')->latest('date_posted')->take(5)->get(),
            'applicationRange' => $range,
            'pendingCount' => $pendingCount,
            'interviewCount' => $interviewCount,
            'rejectedCount' => $rejectedCount,
            'totalForRange' => $pendingCount + $interviewCount + $rejectedCount,
        ]);
    }
}