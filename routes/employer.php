<?php

use Illuminate\Support\Facades\Route;

// Employer Portal Routes
Route::prefix('employer')->middleware(['auth:employer'])->name('employer.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Employer\EmployerDashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/dashboard/job-analytics', [App\Http\Controllers\Employer\EmployerDashboardController::class, 'jobAnalytics'])
        ->name('dashboard.job-analytics');
    Route::get('/dashboard/team-performance', [App\Http\Controllers\Employer\EmployerDashboardController::class, 'teamPerformance'])
        ->name('dashboard.team-performance');
    Route::get('/dashboard/diversity', [App\Http\Controllers\Employer\EmployerDashboardController::class, 'diversityMetrics'])
        ->name('dashboard.diversity');
    
    // Applicant Tracking System (ATS)
    Route::prefix('ats')->name('ats.')->group(function () {
        Route::get('/', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'index'])
            ->name('index');
        Route::get('/applicant/{application}', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'show'])
            ->name('show');
        Route::patch('/applicant/{application}/status', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'updateStatus'])
            ->name('update-status');
        Route::post('/bulk-action', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'bulkAction'])
            ->name('bulk-action');
        Route::post('/applicant/{application}/note', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'addNote'])
            ->name('add-note');
        Route::post('/applicant/{application}/interview', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'scheduleInterview'])
            ->name('schedule-interview');
        Route::post('/compare', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'compare'])
            ->name('compare');
        Route::get('/export', [App\Http\Controllers\Employer\ApplicantTrackingController::class, 'export'])
            ->name('export');
    });
    
    // Interviews
    Route::prefix('interviews')->name('interviews.')->group(function () {
        Route::get('/', [App\Http\Controllers\Employer\InterviewManagementController::class, 'index'])
            ->name('index');
        Route::get('/{interview}', [App\Http\Controllers\Employer\InterviewManagementController::class, 'show'])
            ->name('show');
        Route::post('/', [App\Http\Controllers\Employer\InterviewManagementController::class, 'store'])
            ->name('store');
        Route::patch('/{interview}', [App\Http\Controllers\Employer\InterviewManagementController::class, 'update'])
            ->name('update');
        Route::patch('/{interview}/complete', [App\Http\Controllers\Employer\InterviewManagementController::class, 'complete'])
            ->name('complete');
        Route::delete('/{interview}', [App\Http\Controllers\Employer\InterviewManagementController::class, 'cancel'])
            ->name('cancel');
        Route::get('/calendar/availability', [App\Http\Controllers\Employer\InterviewManagementController::class, 'availability'])
            ->name('availability');
    });
    
    // Talent Pool
    Route::prefix('talent-pool')->name('talent-pool.')->group(function () {
        Route::get('/', [App\Http\Controllers\Employer\TalentPoolController::class, 'index'])
            ->name('index');
        Route::post('/add/{user}', [App\Http\Controllers\Employer\TalentPoolController::class, 'addCandidate'])
            ->name('add');
        Route::delete('/remove/{user}', [App\Http\Controllers\Employer\TalentPoolController::class, 'removeCandidate'])
            ->name('remove');
        Route::post('/tag', [App\Http\Controllers\Employer\TalentPoolController::class, 'tagCandidates'])
            ->name('tag');
        Route::get('/search', [App\Http\Controllers\Employer\TalentPoolController::class, 'search'])
            ->name('search');
        Route::post('/bulk-outreach', [App\Http\Controllers\Employer\TalentPoolController::class, 'bulkOutreach'])
            ->name('bulk-outreach');
    });
    
    // Messaging
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [App\Http\Controllers\Employer\MessagingController::class, 'index'])
            ->name('index');
        Route::get('/{conversation}', [App\Http\Controllers\Employer\MessagingController::class, 'show'])
            ->name('show');
        Route::post('/send', [App\Http\Controllers\Employer\MessagingController::class, 'send'])
            ->name('send');
        Route::get('/templates', [App\Http\Controllers\Employer\MessagingController::class, 'templates'])
            ->name('templates');
        Route::post('/templates', [App\Http\Controllers\Employer\MessagingController::class, 'saveTemplate'])
            ->name('save-template');
    });
    
    // Job Posting Wizard
    Route::prefix('jobs/wizard')->name('jobs.wizard.')->group(function () {
        Route::get('/start', [App\Http\Controllers\Employer\JobWizardController::class, 'start'])
            ->name('start');
        Route::post('/generate-description', [App\Http\Controllers\Employer\JobWizardController::class, 'generateDescription'])
            ->name('generate-description');
        Route::get('/templates', [App\Http\Controllers\Employer\JobWizardController::class, 'templates'])
            ->name('templates');
        Route::post('/preview', [App\Http\Controllers\Employer\JobWizardController::class, 'preview'])
            ->name('preview');
        Route::post('/publish', [App\Http\Controllers\Employer\JobWizardController::class, 'publish'])
            ->name('publish');
    });
    
    // Employee Referrals
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [App\Http\Controllers\Employer\ReferralController::class, 'index'])
            ->name('index');
        Route::post('/create', [App\Http\Controllers\Employer\ReferralController::class, 'create'])
            ->name('create');
        Route::get('/leaderboard', [App\Http\Controllers\Employer\ReferralController::class, 'leaderboard'])
            ->name('leaderboard');
        Route::patch('/{referral}/approve', [App\Http\Controllers\Employer\ReferralController::class, 'approve'])
            ->name('approve');
        Route::get('/settings', [App\Http\Controllers\Employer\ReferralController::class, 'settings'])
            ->name('settings');
        Route::post('/settings', [App\Http\Controllers\Employer\ReferralController::class, 'updateSettings'])
            ->name('update-settings');
    });
});
