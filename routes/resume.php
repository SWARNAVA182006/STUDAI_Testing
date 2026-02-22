<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResumeController;

// Resume Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Resume List & Create
    Route::get('/resumes', [ResumeController::class, 'index'])->name('resume.index');
    Route::get('/resumes/create', [ResumeController::class, 'create'])->name('resume.create');
    Route::post('/resumes', [ResumeController::class, 'store'])->name('resume.store');
    
    // Resume Edit & Update
    Route::get('/resumes/{resume}/edit', [ResumeController::class, 'edit'])->name('resume.edit');
    Route::put('/resumes/{resume}', [ResumeController::class, 'update'])->name('resume.update');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->name('resume.destroy');
    
    // Resume Preview
    Route::get('/resumes/{resume}/preview', [ResumeController::class, 'preview'])->name('resume.preview');
    
    // Resume Export
    Route::get('/resumes/{resume}/export/pdf', [ResumeController::class, 'exportPdf'])->name('resume.export.pdf');
    Route::get('/resumes/{resume}/export/docx', [ResumeController::class, 'exportDocx'])->name('resume.export.docx');
    
    // Resume AI Features
    Route::post('/resumes/{resume}/ai/summary', [ResumeController::class, 'generateSummary'])->name('resume.ai.summary');
    Route::post('/resumes/{resume}/ai/skills', [ResumeController::class, 'extractSkills'])->name('resume.ai.skills');
    Route::post('/resumes/{resume}/ai/optimize', [ResumeController::class, 'optimizeForJob'])->name('resume.ai.optimize');
    Route::get('/resumes/{resume}/ai/analyze-ats', [ResumeController::class, 'analyzeATS'])->name('resume.ai.analyze-ats');
    
    // Resume Suggestions
    Route::post('/resumes/{resume}/suggestions/{suggestion}/accept', [ResumeController::class, 'acceptSuggestion'])->name('resume.suggestion.accept');
    Route::post('/resumes/{resume}/suggestions/{suggestion}/reject', [ResumeController::class, 'rejectSuggestion'])->name('resume.suggestion.reject');
    
    // Resume Actions
    Route::post('/resumes/{resume}/duplicate', [ResumeController::class, 'duplicate'])->name('resume.duplicate');
    Route::post('/resumes/{resume}/set-default', [ResumeController::class, 'setDefault'])->name('resume.set-default');
    Route::post('/resumes/{resume}/toggle-public', [ResumeController::class, 'togglePublic'])->name('resume.toggle-public');
});

// Public Resume View (no auth required)
Route::get('/r/{shareToken}', [ResumeController::class, 'publicView'])->name('resume.public');
