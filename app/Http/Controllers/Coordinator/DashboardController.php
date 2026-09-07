<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('coordinator.dashboard', [
            'totalStudents' => User::students()->count(),
            'totalAlumni' => User::alumni()->count(),
            'totalCompanies' => Company::count(),
            'totalJobsPosted' => JobPosting::count(),
            'totalApplications' => JobApplication::count(),
        ]);
    }
}