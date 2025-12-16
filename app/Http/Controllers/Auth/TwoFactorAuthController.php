<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * TwoFactorAuthController
 *
 * Handles TOTP-based two-factor authentication for OvertimeStaff.
 * This includes enabling/disabling 2FA, verifying codes during login,
 * and managing recovery codes.
 *
 * @package App\Http\Controllers\Auth
 */
class TwoFactorAuthController extends Controller
{
    /**
     * The Google2FA instance.
     *
     * @var Google2FA
     */
    protected Google2FA $google2fa;

    /**
     * Number of recovery codes to generate.
     */
    protected const RECOVERY_CODE_COUNT = 8;

    /**
     * Length of each recovery code.
     */
    protected const RECOVERY_CODE_LENGTH = 10;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['verify', 'verifyCode', 'showRecoveryForm', 'verifyRecoveryCode']);
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the 2FA settings page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('auth.two-factor.index', [
            'user' => $user,
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'recoveryCodesCount' => $user->recoveryCodesCount(),
        ]);
    }

    /**
     * Show the form to enable 2FA.
     * Generates a new secret and displays QR code.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        // If already enabled, redirect to settings
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.index')
                ->with('info', 'Two-factor authentication is already enabled.');
        }

        // Generate a new secret key
        $secret = $this->google2fa->generateSecretKey(32);

        // Store secret temporarily in session (not yet confirmed)
        session(['two_factor_secret' => $secret]);

        // Generate QR code
        $qrCodeSvg = $this->generateQrCode($user, $secret);

        return view('auth.two-factor.enable', [
            'user' => $user,
            'secret' => $secret,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    /**
     * Confirm and enable 2FA after user verifies their first code.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();
        $secret = session('two_factor_secret');

        if (!$secret) {
            return redirect()->route('two-factor.enable')
                ->with('error', 'Please start the 2FA setup process again.');
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid. Please try again.'],
            ]);
        }

        // Enable 2FA
        $user->enableTwoFactorAuth($secret);
        $user->confirmTwoFactorAuth();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->replaceRecoveryCodes($recoveryCodes);

        // Clear session
        session()->forget('two_factor_secret');

        // Log security event
        \Log::channel('security')->info('2FA enabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('two-factor.recovery-codes')
            ->with('success', 'Two-factor authentication has been enabled! Please save your recovery codes.');
    }

    /**
     * Show the 2FA verification form during login.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function verify(Request $request)
    {
        // User ID should be stored in session after credentials verification
        if (!session('two_factor_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Please login again.');
        }

        return view('auth.two-factor.verify');
    }

    /**
     * Verify the 2FA code during login.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $userId = session('two_factor_user_id');
        $remember = session('two_factor_remember', false);

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            session()->forget(['two_factor_user_id', 'two_factor_remember']);
            return redirect()->route('login')
                ->with('error', 'Invalid session. Please login again.');
        }

        // Verify the TOTP code
        $valid = $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $request->code
        );

        if (!$valid) {
            // Log failed 2FA attempt
            \Log::channel('security')->warning('Failed 2FA verification attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid.'],
            ]);
        }

        // Clear 2FA session data
        session()->forget(['two_factor_user_id', 'two_factor_remember']);

        // Log the user in
        auth()->login($user, $remember);

        // Regenerate session
        $request->session()->regenerate();

        // Log successful 2FA verification
        \Log::channel('security')->info('Successful 2FA verification', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // Redirect to intended URL or dashboard
        return redirect()->intended($user->getDashboardRoute());
    }

    /**
     * Show the recovery code verification form.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showRecoveryForm(Request $request)
    {
        if (!session('two_factor_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Please login again.');
        }

        return view('auth.two-factor.recovery');
    }

    /**
     * Verify a recovery code during login.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => ['required', 'string'],
        ]);

        $userId = session('two_factor_user_id');
        $remember = session('two_factor_remember', false);

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            session()->forget(['two_factor_user_id', 'two_factor_remember']);
            return redirect()->route('login')
                ->with('error', 'Invalid session. Please login again.');
        }

        // Normalize the recovery code (remove dashes and spaces)
        $code = str_replace(['-', ' '], '', strtoupper($request->recovery_code));

        // Check if the recovery code is valid
        if (!$user->isValidRecoveryCode($code)) {
            \Log::channel('security')->warning('Invalid recovery code attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            throw ValidationException::withMessages([
                'recovery_code' => ['The provided recovery code is invalid.'],
            ]);
        }

        // Use the recovery code (removes it from the list)
        $user->useRecoveryCode($code);

        // Clear 2FA session data
        session()->forget(['two_factor_user_id', 'two_factor_remember']);

        // Log the user in
        auth()->login($user, $remember);

        // Regenerate session
        $request->session()->regenerate();

        // Log recovery code usage
        \Log::channel('security')->warning('Recovery code used for 2FA', [
            'user_id' => $user->id,
            'email' => $user->email,
            'remaining_codes' => $user->recoveryCodesCount(),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // Redirect to dashboard with warning about recovery codes
        $remainingCodes = $user->recoveryCodesCount();
        $message = "Logged in using a recovery code. You have {$remainingCodes} recovery codes remaining.";

        if ($remainingCodes <= 2) {
            $message .= " Please generate new recovery codes soon.";
        }

        return redirect()->intended($user->getDashboardRoute())
            ->with('warning', $message);
    }

    /**
     * Disable 2FA for the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Disable 2FA
        $user->disableTwoFactorAuth();

        // Log security event
        \Log::channel('security')->warning('2FA disabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('two-factor.index')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Show the current recovery codes.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.index')
                ->with('error', 'Two-factor authentication is not enabled.');
        }

        return view('auth.two-factor.recovery-codes', [
            'recoveryCodes' => $user->recoveryCodes(),
        ]);
    }

    /**
     * Regenerate recovery codes.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.index')
                ->with('error', 'Two-factor authentication is not enabled.');
        }

        // Generate new recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->replaceRecoveryCodes($recoveryCodes);

        // Log security event
        \Log::channel('security')->info('Recovery codes regenerated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('two-factor.recovery-codes')
            ->with('success', 'New recovery codes have been generated. Please save them securely.');
    }

    /**
     * Generate a QR code SVG for the authenticator app.
     *
     * @param User $user
     * @param string $secret
     * @return string
     */
    protected function generateQrCode(User $user, string $secret): string
    {
        $companyName = config('app.name', 'OvertimeStaff');
        $accountName = $user->email;

        // Generate the provisioning URI
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $accountName,
            $secret
        );

        // Create QR code using Bacon QR Code
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Generate a set of recovery codes.
     *
     * @return array
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            // Generate a random alphanumeric code
            $code = strtoupper(Str::random(self::RECOVERY_CODE_LENGTH));
            $codes[] = $code;
        }

        return $codes;
    }
}
