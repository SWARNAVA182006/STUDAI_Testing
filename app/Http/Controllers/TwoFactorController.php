<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('auth');
    }

    /**
     * Show 2FA setup page.
     */
    public function index()
    {
        $user = Auth::user();
        $isEnabled = $this->twoFactorService->isEnabled($user);
        $remainingCodes = $this->twoFactorService->getRemainingRecoveryCodes($user);

        return view('settings.two-factor', compact('isEnabled', 'remainingCodes'));
    }

    /**
     * Enable 2FA and show QR code.
     */
    public function enable(Request $request)
    {
        $user = Auth::user();

        // Verify password
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!auth()->validate(['email' => $user->email, 'password' => $request->password])) {
            return back()->withErrors(['password' => 'Incorrect password']);
        }

        $data = $this->twoFactorService->enable($user);

        return view('settings.two-factor-setup', [
            'secret' => $data['secret'],
            'qr_code' => $data['qr_code'],
            'recovery_codes' => $data['recovery_codes'],
        ]);
    }

    /**
     * Confirm 2FA with verification code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if ($this->twoFactorService->confirm($user, $request->code)) {
            return redirect()->route('settings.two-factor')
                ->with('success', 'Two-factor authentication has been enabled successfully');
        }

        return back()->withErrors(['code' => 'Invalid verification code']);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if ($this->twoFactorService->disable($user, $request->password)) {
            return redirect()->route('settings.two-factor')
                ->with('success', 'Two-factor authentication has been disabled');
        }

        return back()->withErrors(['password' => 'Incorrect password']);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (!auth()->validate(['email' => $user->email, 'password' => $request->password])) {
            return back()->withErrors(['password' => 'Incorrect password']);
        }

        try {
            $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

            return view('settings.two-factor-recovery-codes', compact('recoveryCodes'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show 2FA verification page during login.
     */
    public function verify()
    {
        if (!session('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify 2FA code during login.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = session('2fa:user:id');
        
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);

        if ($this->twoFactorService->verify($user, $request->code)) {
            // Clear 2FA session
            session()->forget('2fa:user:id');

            // Log in user
            Auth::login($user, session('2fa:remember', false));
            session()->forget('2fa:remember');

            // Log successful login
            app(\App\Services\AuditService::class)->logLogin($user);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'Invalid verification code']);
    }
}
