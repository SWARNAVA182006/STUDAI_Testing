<?php

namespace App\Http\Controllers;

use App\Models\SkillAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SkillAnalyzerWebController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();
        
        $gaps = $user->skillGaps()->with('learningPath')->rankedByPriority()->limit(20)->get();
        $activePaths = $user->learningPaths()->active()->with('resources')->get();
        $validations = $user->skillValidations()->highConfidence()->limit(10)->get();
        $recentAssessments = $user->skillAssessments()->latest()->limit(5)->get();
        
        return view('skills.dashboard', compact('gaps', 'activePaths', 'validations', 'recentAssessments'));
    }

    public function learningPaths(): View
    {
        $user = Auth::user();
        
        $activePaths = $user->learningPaths()->active()->with('resources', 'skillGap')->get();
        $completedPaths = $user->learningPaths()->completed()->with('resources', 'skillGap')->limit(10)->get();
        
        return view('skills.learning-paths', compact('activePaths', 'completedPaths'));
    }

    public function showLearningPath(int $id): View
    {
        $user = Auth::user();
        $path = $user->learningPaths()->with('resources', 'progress', 'skillGap')->findOrFail($id);
        
        return view('skills.learning-path-show', compact('path'));
    }

    public function validation(): View
    {
        $user = Auth::user();
        $validations = $user->skillValidations()->with('userSkill')->latest()->get();
        
        return view('skills.validation', compact('validations'));
    }

    public function assessments(): View
    {
        $user = Auth::user();
        
        $availableSkills = $user->skills()->verified()->get();
        $activeAssessments = $user->skillAssessments()->active()->latest()->get();
        $gradedAssessments = $user->skillAssessments()->where('status', 'graded')->latest()->limit(10)->get();
        
        return view('skills.assessments', compact('availableSkills', 'activeAssessments', 'gradedAssessments'));
    }

    public function takeAssessment(int $id): View
    {
        $user = Auth::user();
        $assessment = $user->skillAssessments()->findOrFail($id);
        
        return view('skills.assessment-take', compact('assessment'));
    }

    public function dailyLearning(): View
    {
        $user = Auth::user();
        
        $dailyTimeMinutes = $user->profile->learning_preferences['daily_time_commitment'] ?? 30;
        $activePaths = $user->learningPaths()->active()->with('resources')->get();
        
        $recommendations = [];
        foreach ($activePaths as $path) {
            $nextResource = $path->getNextResource();
            if ($nextResource) {
                $recommendations[] = [
                    'path' => $path,
                    'resource' => $nextResource,
                    'fits_schedule' => $nextResource->duration_minutes <= $dailyTimeMinutes,
                ];
            }
        }
        
        return view('skills.daily-learning', compact('recommendations', 'dailyTimeMinutes'));
    }

    public function showCertificate(string $hash): View
    {
        $assessment = SkillAssessment::where('certificate_hash', $hash)->firstOrFail();
        
        if (!$assessment->is_shareable) {
            abort(403, 'This certificate is not publicly shareable.');
        }
        
        if ($assessment->certificate_expires_at && $assessment->certificate_expires_at->isPast()) {
            abort(410, 'This certificate has expired.');
        }
        
        return view('skills.certificate-public', compact('assessment'));
    }
}
