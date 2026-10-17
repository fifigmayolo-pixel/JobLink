<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\RegistrationRequestController;
use App\Http\Controllers\Auth\ActivateAccountController;
use App\Http\Controllers\Auth\TermsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Company\ApplicationController as CompanyApplicationController;
use App\Http\Controllers\Company\MessageController as CompanyMessageController;
use App\Http\Controllers\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\Coordinator\RegistrationReviewController;
use App\Http\Controllers\Coordinator\AccountController as CoordinatorAccountController;
use App\Http\Controllers\Coordinator\AnnouncementController;
use App\Http\Controllers\Coordinator\JobApprovalController;
use App\Http\Controllers\Coordinator\AuditLogController;
use App\Http\Controllers\Coordinator\BulkUpdateController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\ProfileController as CoordinatorProfileController;
use App\Http\Controllers\Coordinator\RecordController;
use App\Http\Controllers\Coordinator\StudentRosterController;
use App\Http\Controllers\Coordinator\TracerStudyLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedJobController;
use App\Http\Controllers\TracerStudyController;
use App\Http\Controllers\UpdatePasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LinkyController;

Route::get('/', fn () => redirect()->route('login'));

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegistrationRequestController::class, 'show'])->name('register.show');
    Route::post('/register', [RegistrationRequestController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/activate', [ActivateAccountController::class, 'showVerifyForm'])->name('activate.verify');
    Route::post('/activate', [ActivateAccountController::class, 'verify'])
        ->middleware('throttle:8,1')
        ->name('activate.verify.store');
    Route::get('/activate/password', [ActivateAccountController::class, 'showPasswordForm'])->name('activate.password');
    Route::post('/activate/password', [ActivateAccountController::class, 'setPassword'])->name('activate.password.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showFindAccountForm'])->name('password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'findAccount'])
        ->middleware('throttle:8,1')
        ->name('password.forgot.store');
    Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.forgot.verify');
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verifyCode'])
        ->middleware('throttle:8,1')
        ->name('password.forgot.verify.store');
    Route::post('/forgot-password/verify/resend', [ForgotPasswordController::class, 'resendCode'])
        ->middleware('throttle:5,1')
        ->name('password.forgot.verify.resend');
    Route::get('/forgot-password/reset', [ForgotPasswordController::class, 'showResetForm'])->name('password.forgot.reset');
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.forgot.reset.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/linky/chat', [LinkyController::class, 'chat'])->middleware('throttle:12,1')->name('linky.chat');

    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.store');

    Route::get('/terms', [TermsController::class, 'show'])->name('terms.show');
    Route::post('/terms', [TermsController::class, 'accept'])->name('terms.accept');

    Route::get('/password/edit', [UpdatePasswordController::class, 'show'])->name('password.edit');
    Route::put('/password/edit', [UpdatePasswordController::class, 'update'])->name('password.edit.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Static /jobs/create MUST be registered before the /jobs/{jobPosting}
    // wildcard below, or "create" gets swallowed as a job ID and 404s.
    Route::middleware('role:job_coordinator|company')->group(function () {
        Route::get('/jobs/create', [JobPostingController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobPostingController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{jobPosting}/edit', [JobPostingController::class, 'edit'])->name('jobs.edit');
        Route::patch('/jobs/{jobPosting}', [JobPostingController::class, 'update'])->name('jobs.update');
        Route::delete('/jobs/{jobPosting}', [JobPostingController::class, 'destroy'])->name('jobs.destroy');
    });

    Route::get('/jobs', [JobPostingController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{jobPosting}', [JobPostingController::class, 'show'])->name('jobs.show');
});

// Student & Alumni
Route::middleware(['auth', 'role:student|alumni'])->group(function () {
    Route::get('/tracer-study', [TracerStudyController::class, 'show'])->name('tracer-study.show');
    Route::post('/tracer-study/access', [TracerStudyController::class, 'access'])->name('tracer-study.access'); 
    Route::get('/profile/complete', [ProfileController::class, 'showCompletionForm'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileController::class, 'storeCompletion'])->name('profile.complete.store');
    Route::get('/profile/complete/cities', [ProfileController::class, 'citiesForProvince'])->name('profile.complete.cities');
    Route::get('/profile/complete/barangays', [ProfileController::class, 'barangaysForCity'])->name('profile.complete.barangays');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/my-applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/my-applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/my-applications/{application}/resume', [ApplicationController::class, 'downloadResume'])->name('applications.resume');
    Route::post('/jobs/{jobPosting}/apply', [ApplicationController::class, 'store'])->name('applications.store');

    Route::get('/saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
    Route::post('/jobs/{jobPosting}/save', [SavedJobController::class, 'store'])->name('saved-jobs.store');
    Route::delete('/jobs/{jobPosting}/save', [SavedJobController::class, 'destroy'])->name('saved-jobs.destroy');
});

// Company
Route::middleware(['auth', 'role:company'])->prefix('company')->name('company.')->group(function () {
    Route::get('/profile', [CompanyProfileController::class, 'show'])->name('profile.edit');
    Route::patch('/profile', [CompanyProfileController::class, 'update'])->name('profile.update');

    Route::get('/applications', [CompanyApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [CompanyApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/resume', [CompanyApplicationController::class, 'downloadResume'])->name('applications.resume');
    Route::patch('/applications/{application}/status', [CompanyApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::post('/applications/{application}/message', [CompanyMessageController::class, 'store'])->name('applications.message');
});

// Job Coordinator only
Route::middleware(['auth', 'role:job_coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [CoordinatorProfileController::class, 'show'])->name('profile.edit');
    Route::patch('/profile', [CoordinatorProfileController::class, 'update'])->name('profile.update');
    Route::get('/job-approvals', [JobApprovalController::class, 'index'])->name('job-approvals.index');
    Route::patch('/job-approvals/{jobPosting}/approve', [JobApprovalController::class, 'approve'])->name('job-approvals.approve');
    Route::patch('/job-approvals/{jobPosting}/reject', [JobApprovalController::class, 'reject'])->name('job-approvals.reject');

    Route::get('/accounts', [CoordinatorAccountController::class, 'index'])->name('accounts.index');

    // Static /accounts/company/create MUST stay before the
    // /accounts/company/{company} wildcard, or "create" gets swallowed as a company ID.
    Route::get('/accounts/company/create', [CoordinatorAccountController::class, 'showCompanyForm'])->name('accounts.company.create');
    Route::post('/accounts/company', [CoordinatorAccountController::class, 'storeCompany'])->name('accounts.company.store');
        Route::patch('/accounts/company/{company}/restore', [CoordinatorAccountController::class, 'restoreCompany'])->name('accounts.company.restore');
    Route::get('/accounts/company', [CoordinatorAccountController::class, 'companyIndex'])->name('accounts.company.index');
    Route::get('/accounts/company/{company}', [CoordinatorAccountController::class, 'showCompany'])->name('accounts.company.show');
    Route::get('/accounts/company/{company}/edit', [CoordinatorAccountController::class, 'editCompany'])->name('accounts.company.edit');
    Route::patch('/accounts/company/{company}', [CoordinatorAccountController::class, 'updateCompany'])->name('accounts.company.update');
    Route::delete('/accounts/company/{company}', [CoordinatorAccountController::class, 'destroyCompany'])->name('accounts.company.destroy');
    
    Route::patch('/accounts/company/{company}/temp-password', [CoordinatorAccountController::class, 'regenerateCompanyTempPassword'])
    ->middleware('throttle:5,1')
    ->name('accounts.company.temp-password');
    Route::get('/accounts/coordinator/create', [CoordinatorAccountController::class, 'showCoordinatorForm'])->name('accounts.coordinator.create');
    Route::post('/accounts/coordinator', [CoordinatorAccountController::class, 'storeCoordinator'])->name('accounts.coordinator.store');
    

    Route::get('/roster/quick-add', [StudentRosterController::class, 'showQuickAddForm'])->name('roster.quick-add');
    Route::post('/roster/quick-add', [StudentRosterController::class, 'quickAdd'])->name('roster.quick-add.store');
    Route::get('/roster/import', [StudentRosterController::class, 'showImportForm'])->name('roster.import');
    Route::post('/roster/import', [StudentRosterController::class, 'import'])->name('roster.import.store');

    Route::get('/records', [RecordController::class, 'index'])->name('records.index');
    Route::get('/records/{profile}', [RecordController::class, 'show'])->name('records.show');
    Route::patch('/records/{profile}/override', [RecordController::class, 'overrideLockedField'])->name('records.override');
    Route::delete('/records/{profile}', [RecordController::class, 'destroy'])->name('records.destroy');

    Route::get('/registration-requests', [RegistrationReviewController::class, 'index'])->name('registration-requests.index');
    Route::post('/registration-requests/{registrationRequest}/approve', [RegistrationReviewController::class, 'approve'])->name('registration-requests.approve');
    Route::post('/registration-requests/{registrationRequest}/reject', [RegistrationReviewController::class, 'reject'])->name('registration-requests.reject');

    Route::post('/bulk-update', [BulkUpdateController::class, 'store'])->name('bulk-update.store');

    Route::get('/tracer-study-links', [TracerStudyLinkController::class, 'index'])->name('tracer-study-links.index');
    Route::post('/tracer-study-links', [TracerStudyLinkController::class, 'store'])->name('tracer-study-links.store');

    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');

    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');
});