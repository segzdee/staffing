<?php

namespace App\Http\Controllers;

use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use App\Http\Requests\SelectRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class OnboardingController extends Controller
{
    /**
     * Error codes for role selection failures.
     */
    public const ERROR_CODES = [
        'UNAUTHORIZED' => 'E001',
        'INVALID_ROLE' => 'E002',
        'DUPLICATE_PROFILE' => 'E003',
        'DATABASE_ERROR' => 'E004',
        'NETWORK_ERROR' => 'E005',
        'UNKNOWN_ERROR' => 'E999',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show role selection page.
     * This is step 1 of onboarding - selecting account type.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRoleSelection()
    {
        $user = Auth::user();

        // If user already has a type set and has started onboarding, redirect to continue
        if ($user->user_type && $user->user_type !== 'worker' && $user->onboarding_step) {
            return $this->redirectToOnboardingStep($user);
        }

        // If already completed onboarding, redirect to dashboard
        if ($user->onboarding_completed) {
            return $this->redirectToOnboardingStep($user);
        }

        return view('onboarding.role-selection', [
            'user' => $user
        ]);
    }

    /**
     * Handle role selection with improved validation and error handling.
     *
     * @param \App\Http\Requests\SelectRoleRequest $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function storeRoleSelection(SelectRoleRequest $request)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Get validated data
            $validated = $request->validated();
            $userType = $validated['user_type'];

            // Check if profile already exists for this user
            $existingProfile = $this->checkExistingProfile($user, $userType);
            
            if ($existingProfile) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'error_code' => self::ERROR_CODES['DUPLICATE_PROFILE'],
                    'message' => 'A profile already exists for this account type. Refresh the page to continue.',
                    'retry' => true,
                    'existing_profile' => $existingProfile,
                    'status' => 409, // Conflict
                ], 409);
            }

            // Create appropriate profile for the user type
            $this->createProfileForType($user, $userType);

            // Update user record
            $user->update([
                'user_type' => $userType,
                'onboarding_step' => $userType === 'worker' ? 'availability' : 'business_profile',
                'onboarding_completed' => false,
                'updated_at' => now(),
            ]);

            // Log successful role selection
            Log::info('User role selected successfully', [
                'user_id' => $user->id,
                'user_type' => $userType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            // Log successful role selection
            Log::info('User role selected successfully', [
                'user_id' => $user->id,
                'user_type' => $userType,
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role selected successfully!',
                    'redirect' => $this->getNextStepUrl($userType),
                    'user_type' => $userType,
                ]);
            }

            // Redirect to next onboarding step
            return redirect()->to($this->getNextStepUrl($userType))
                ->with('success', 'Great choice! Let\'s set up your profile.');

        } catch (QueryException $e) {
            DB::rollBack();

            // Handle specific database errors
            $errorInfo = $this->handleDatabaseError($e);

            Log::error('Role selection database error', [
                'user_id' => $user->id,
                'user_type' => $userType ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error_code' => $errorInfo['code'],
                    'message' => $errorInfo['message'],
                    'retry' => $errorInfo['retry'],
                    'status' => $errorInfo['status'],
                ], $errorInfo['status_code']);
            }

            return redirect()->back()
                ->with('error', $errorInfo['message'])
                ->with('error_code', $errorInfo['code'])
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Role selection unexpected error', [
                'user_id' => $user->id,
                'user_type' => $userType ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error_code' => self::ERROR_CODES['UNKNOWN_ERROR'],
                    'message' => 'An unexpected error occurred. Please try again.',
                    'retry' => true,
                    'status' => 500,
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->with('error_code', self::ERROR_CODES['UNKNOWN_ERROR'])
                ->withInput();
        }
    }

    /**
     * Get the next step URL based on user type.
     */
    private function getNextStepUrl(string $userType): string
    {
        return match($userType) {
            'worker' => route('worker.onboarding.availability'),
            'business' => route('business.onboarding.profile'),
            'agency' => route('agency.onboarding.profile'),
            'admin' => route('admin.dashboard'),
            default => route('dashboard'),
        };
    }

    /**
     * Redirect user to appropriate onboarding step.
     */
    private function redirectToOnboardingStep($user)
    {
        $nextStep = match($user->user_type) {
            'worker' => 'worker.onboarding.availability',
            'business' => 'business.onboarding.profile',
            'agency' => 'agency.onboarding.profile',
            'admin' => 'admin.dashboard',
            default => 'dashboard',
        };

        return redirect()->to($nextStep);
    }

    /**
     * Create appropriate profile for the user type.
     */
    protected function createProfileForType($user, string $userType): void
    {
        switch ($userType) {
            case 'worker':
                WorkerProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                break;

            case 'business':
                BusinessProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'business_name' => $user->name . '\'s Business',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                break;

            case 'agency':
                AgencyProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'agency_name' => $user->name . '\'s Agency',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                break;

            case 'admin':
                // Admins don't need additional profiles
                break;

            default:
                throw new \InvalidArgumentException("Invalid user type: {$userType}");
        }
    }

    /**
     * Check if a profile already exists for this user type.
     */
    protected function checkExistingProfile($user, string $userType): ?array
    {
        switch ($userType) {
            case 'worker':
                return WorkerProfile::where('user_id', $user->id)->first();
            case 'business':
                return BusinessProfile::where('user_id', $user->id)->first();
            case 'agency':
                return AgencyProfile::where('user_id', $user->id)->first();
            default:
                return null;
        }
    }

    /**
     * Handle database errors and return appropriate error info.
     *
     * @param \Illuminate\Database\QueryException $e
     * @return array
     */
    protected function handleDatabaseError(QueryException $e): array
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $errorCode = $e->errorInfo[1] ?? null;

        // Duplicate entry (SQLSTATE 23000)
        if ($sqlState === '23000' || $errorCode === 1062) {
            return [
                'code' => self::ERROR_CODES['DUPLICATE_PROFILE'],
                'message' => 'A profile already exists for this account type. Please refresh the page.',
                'retry' => false,
                'status' => 409, // Conflict
                'sql_state' => $sqlState,
                'error_code' => $errorCode,
            ];
        }

        // Connection/timeout errors
        if (in_array($sqlState, ['HY000', '08S01', '08006'])) {
            return [
                'code' => self::ERROR_CODES['NETWORK_ERROR'],
                'message' => 'Connection error. Please check your internet connection and try again.',
                'retry' => true,
                'status' => 503, // Service Unavailable
                'sql_state' => $sqlState,
                'error_code' => $errorCode,
            ];
        }

        // Generic database error
        return [
            'code' => self::ERROR_CODES['DATABASE_ERROR'],
            'message' => 'A database error occurred. Please try again.',
            'retry' => true,
            'status' => 500, // Internal Server Error
            'sql_state' => $sqlState,
            'error_code' => $errorCode,
        ];
    }
}