<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return match (auth()->user()->role) {
            'student', 'alumni' => redirect()->route('jobs.index'),
            'company' => redirect()->route('company.applications.index'),
            'job_coordinator' => redirect()->route('coordinator.dashboard'),
        };
    }
}