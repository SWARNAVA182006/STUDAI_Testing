<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /**
     * Show the two-factor authentication setup page.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return redirect()->route('two-factor.enable');
        }

        return view('auth.two-factor.show', [
            'user' => $user,
            'recoveryCodes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_secret) {
            return redirect()->route('two-factor.show')
                ->with('status', '2FA is already enabled');
        }

        // Generate secret and recovery codes
        $user->forceFill([
            'two_factor_secret' => encrypt(app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(\Illuminate\Support\Collection::times(8, function () {
                return \Illuminate\Support\Str::random(10).'-'.\Illuminate\Support\Str::random(10);
            })->all())),
        ])->save();

        return redirect()->route('two-factor.confirm');
    }

    /**
     * Confirm two-factor authentication setup.
     */
    public function confirm(Request $request)
    {
        $user = $request->user();

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            decrypt($user->two_factor_secret)
        );

        // Generate QR code SVG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('auth.two-factor.confirm', [
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => decrypt($user->two_factor_secret),
        ]);
    }

    /**
     * Verify and activate two-factor authentication.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        $valid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        // Mark 2FA as confirmed
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return redirect()->route('two-factor.show')
            ->with('status', '2FA has been enabled successfully');
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('dashboard')
            ->with('status', '2FA has been disabled');
    }

    /**
     * Show recovery codes.
     */
    public function recoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return redirect()->route('two-factor.enable');
        }

        return view('auth.two-factor.recovery-codes', [
            'recoveryCodes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(\Illuminate\Support\Collection::times(8, function () {
                return \Illuminate\Support\Str::random(10).'-'.\Illuminate\Support\Str::random(10);
            })->all())),
        ])->save();

        return back()->with('status', 'Recovery codes regenerated');
    }
}

