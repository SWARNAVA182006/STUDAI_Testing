<?php

namespace App\Http\Controllers;

use App\Services\PasswordSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordSecurityController extends Controller
{
    protected PasswordSecurityService $passwordService;

    public function __construct(PasswordSecurityService $passwordService)
    {
        $this->passwordService = $passwordService;
        $this->middleware('auth');
    }

    /**
     * Show password change form.
     */
    public function index()
    {
        $requirements = $this->passwordService->getPasswordRequirements();
        
        return view('settings.password', compact('requirements'));
    }

    /**
     * Change password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed',
        ]);

        $user = Auth::user();

        $result = $this->passwordService->changePassword(
            $user,
            $request->current_password,
            $request->new_password
        );

        if ($result['success']) {
            return redirect()->route('settings.password')
                ->with('success', 'Password changed successfully');
        }

        return back()->withErrors(['new_password' => $result['errors']]);
    }

    /**
     * Check password strength (AJAX).
     */
    public function checkStrength(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $score = $this->passwordService->calculatePasswordStrength($request->password);
        $errors = $this->passwordService->validatePassword($request->password);
        $compromised = $this->passwordService->isPasswordCompromised($request->password);

        $strength = 'weak';
        if ($score >= 80) $strength = 'strong';
        elseif ($score >= 60) $strength = 'medium';

        return response()->json([
            'score' => $score,
            'strength' => $strength,
            'errors' => $errors,
            'compromised' => $compromised,
        ]);
    }
}
