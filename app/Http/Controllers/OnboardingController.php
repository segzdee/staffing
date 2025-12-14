<?php

namespace App\Http\Controllers;

use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show onboarding start page based on user type.
     */
    public function start()
    {
        $user = Auth::user();

        // If already completed onboarding, redirect to appropriate dashboard
        if ($user->onboarding_completed) {
            return $this->redirectToDashboard($user);
        }

        // Determine which onboarding flow to show
        $userType = $user->user_type;

        return view('onboarding.start', compact('user', 'userType'));
    }

    /**
     * Process worker onboarding.
     */
    public function workerOnboarding(Request $request)
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            abort(403, 'Only workers can access worker onboarding.');
        }

        $validator = Validator::make($request->all(), [
            // Personal Information
            'date_of_birth' => 'required|date|before:-18 years',
            'phone_number' => 'required|string|max:20',
            'location_address' => 'required|string|max:255',
            'location_city' => 'required|string|max:100',
            'location_state' => 'required|string|max:100',
            'location_country' => 'required|string|max:100',
            'location_postal_code' => 'required|string|max:20',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',

            // Work Preferences
            'industries_experience' => 'required|array|min:1',
            'preferred_radius' => 'required|integer|min:1|max:100',
            'availability' => 'required|array',
            'transportation' => 'required|in:own_vehicle,public_transit,bicycle,walking',

            // Skills
            'skills' => 'nullable|array',

            // Employment Details
            'employment_eligibility' => 'required|boolean',
            'has_ssn' => 'required|boolean',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_relationship' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create or update worker profile
            $profile = WorkerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $request->date_of_birth,
                    'phone_number' => $request->phone_number,
                    'location_address' => $request->location_address,
                    'location_city' => $request->location_city,
                    'location_state' => $request->location_state,
                    'location_country' => $request->location_country,
                    'location_postal_code' => $request->location_postal_code,
                    'location_lat' => $request->location_lat,
                    'location_lng' => $request->location_lng,
                    'industries_experience' => $request->industries_experience,
                    'preferred_radius' => $request->preferred_radius,
                    'availability' => $request->availability,
                    'transportation' => $request->transportation,
                    'employment_eligibility' => $request->employment_eligibility,
                    'has_ssn' => $request->has_ssn,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'emergency_contact_relationship' => $request->emergency_contact_relationship,
                    'onboarding_completed_at' => now(),
                ]
            );

            // Add skills if provided
            if ($request->has('skills') && !empty($request->skills)) {
                foreach ($request->skills as $skill) {
                    $user->skills()->create([
                        'skill_name' => $skill,
                        'verified' => false,
                    ]);
                }
            }

            // Update user onboarding status
            $user->update([
                'onboarding_completed' => true,
                'onboarding_step' => 'completed',
            ]);

            DB::commit();

            return redirect()->route('onboarding.complete')
                ->with('success', 'Worker profile setup complete!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred during onboarding. Please try again.')
                ->withInput();
        }
    }

    /**
     * Process business onboarding.
     */
    public function businessOnboarding(Request $request)
    {
        $user = Auth::user();

        if (!$user->isBusiness()) {
            abort(403, 'Only businesses can access business onboarding.');
        }

        $validator = Validator::make($request->all(), [
            // Business Information
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:restaurant,hotel,retail_store,event_venue,warehouse,healthcare_facility,office,other',
            'industry' => 'required|in:hospitality,healthcare,retail,events,warehouse,professional',
            'business_phone' => 'required|string|max:20',
            'business_email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',

            // Business Address
            'business_address' => 'required|string|max:255',
            'business_city' => 'required|string|max:100',
            'business_state' => 'required|string|max:100',
            'business_country' => 'required|string|max:100',
            'business_postal_code' => 'required|string|max:20',

            // Legal Information
            'ein' => 'required|string|max:20',
            'business_license_number' => 'nullable|string|max:50',
            'years_in_business' => 'required|integer|min:0',

            // Contact Person
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'required|string|max:100',
            'contact_person_phone' => 'required|string|max:20',

            // Payment
            'billing_same_as_business' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create or update business profile
            $profile = BusinessProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $request->business_name,
                    'business_type' => $request->business_type,
                    'industry' => $request->industry,
                    'business_phone' => $request->business_phone,
                    'business_email' => $request->business_email,
                    'website' => $request->website,
                    'business_address' => $request->business_address,
                    'business_city' => $request->business_city,
                    'business_state' => $request->business_state,
                    'business_country' => $request->business_country,
                    'business_postal_code' => $request->business_postal_code,
                    'ein' => $request->ein,
                    'business_license_number' => $request->business_license_number,
                    'years_in_business' => $request->years_in_business,
                    'contact_person_name' => $request->contact_person_name,
                    'contact_person_title' => $request->contact_person_title,
                    'contact_person_phone' => $request->contact_person_phone,
                    'billing_same_as_business' => $request->billing_same_as_business,
                    'onboarding_completed_at' => now(),
                ]
            );

            // Update user onboarding status
            $user->update([
                'onboarding_completed' => true,
                'onboarding_step' => 'completed',
            ]);

            DB::commit();

            return redirect()->route('onboarding.complete')
                ->with('success', 'Business profile setup complete!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred during onboarding. Please try again.')
                ->withInput();
        }
    }

    /**
     * Process agency onboarding.
     */
    public function agencyOnboarding(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAgency()) {
            abort(403, 'Only agencies can access agency onboarding.');
        }

        $validator = Validator::make($request->all(), [
            // Agency Information
            'agency_name' => 'required|string|max:255',
            'agency_type' => 'required|in:staffing_agency,temp_agency,recruitment_firm,other',
            'industries_served' => 'required|array|min:1',
            'agency_phone' => 'required|string|max:20',
            'agency_email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',

            // Agency Address
            'agency_address' => 'required|string|max:255',
            'agency_city' => 'required|string|max:100',
            'agency_state' => 'required|string|max:100',
            'agency_country' => 'required|string|max:100',
            'agency_postal_code' => 'required|string|max:20',

            // Legal Information
            'ein' => 'required|string|max:20',
            'license_number' => 'required|string|max:50',
            'license_state' => 'required|string|max:100',
            'years_in_business' => 'required|integer|min:0',

            // Operations
            'service_areas' => 'required|array|min:1',
            'worker_count' => 'required|integer|min:1',

            // Contact Person
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'required|string|max:100',
            'contact_person_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create or update agency profile
            $profile = AgencyProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'agency_name' => $request->agency_name,
                    'agency_type' => $request->agency_type,
                    'industries_served' => $request->industries_served,
                    'agency_phone' => $request->agency_phone,
                    'agency_email' => $request->agency_email,
                    'website' => $request->website,
                    'agency_address' => $request->agency_address,
                    'agency_city' => $request->agency_city,
                    'agency_state' => $request->agency_state,
                    'agency_country' => $request->agency_country,
                    'agency_postal_code' => $request->agency_postal_code,
                    'ein' => $request->ein,
                    'license_number' => $request->license_number,
                    'license_state' => $request->license_state,
                    'years_in_business' => $request->years_in_business,
                    'service_areas' => $request->service_areas,
                    'worker_count' => $request->worker_count,
                    'contact_person_name' => $request->contact_person_name,
                    'contact_person_title' => $request->contact_person_title,
                    'contact_person_phone' => $request->contact_person_phone,
                    'onboarding_completed_at' => now(),
                ]
            );

            // Update user onboarding status
            $user->update([
                'onboarding_completed' => true,
                'onboarding_step' => 'completed',
            ]);

            DB::commit();

            return redirect()->route('onboarding.complete')
                ->with('success', 'Agency profile setup complete!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred during onboarding. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show onboarding completion page with next steps.
     */
    public function complete()
    {
        $user = Auth::user();

        // Redirect if onboarding not completed
        if (!$user->onboarding_completed) {
            return redirect()->route('onboarding.start');
        }

        // Determine next steps based on user type
        $nextSteps = $this->getNextSteps($user);

        return view('onboarding.complete', compact('user', 'nextSteps'));
    }

    /**
     * Get next steps based on user type.
     */
    protected function getNextSteps($user)
    {
        switch ($user->user_type) {
            case 'worker':
                return [
                    'title' => 'Welcome to OvertimeStaff!',
                    'subtitle' => 'Start finding shifts near you',
                    'steps' => [
                        [
                            'icon' => 'id-card',
                            'title' => 'Complete Verification',
                            'description' => 'Upload your ID and get verified to access more shifts',
                            'action' => 'Get Verified',
                            'route' => route('settings.verify'),
                        ],
                        [
                            'icon' => 'credit-card',
                            'title' => 'Connect Payment Method',
                            'description' => 'Set up instant payouts via Stripe to get paid 15 minutes after shifts',
                            'action' => 'Connect Stripe',
                            'route' => route('stripe.connect'),
                        ],
                        [
                            'icon' => 'briefcase',
                            'title' => 'Browse Available Shifts',
                            'description' => 'Find shifts that match your skills and availability',
                            'action' => 'Find Shifts',
                            'route' => route('shifts.index'),
                        ],
                    ],
                ];

            case 'business':
                return [
                    'title' => 'Welcome to OvertimeStaff!',
                    'subtitle' => 'Start posting shifts and finding workers',
                    'steps' => [
                        [
                            'icon' => 'credit-card',
                            'title' => 'Add Payment Method',
                            'description' => 'Add a payment method to post shifts and hire workers',
                            'action' => 'Add Payment',
                            'route' => route('settings.payout'),
                        ],
                        [
                            'icon' => 'id-card',
                            'title' => 'Verify Business',
                            'description' => 'Upload business documents to access premium features',
                            'action' => 'Verify Business',
                            'route' => route('settings.verify'),
                        ],
                        [
                            'icon' => 'plus-circle',
                            'title' => 'Post Your First Shift',
                            'description' => 'Create a shift and let our AI match you with qualified workers',
                            'action' => 'Post Shift',
                            'route' => route('shift.create'),
                        ],
                    ],
                ];

            case 'agency':
                return [
                    'title' => 'Welcome to OvertimeStaff!',
                    'subtitle' => 'Manage shifts for multiple clients',
                    'steps' => [
                        [
                            'icon' => 'id-card',
                            'title' => 'Agency Verification',
                            'description' => 'Upload agency license and business documents',
                            'action' => 'Get Verified',
                            'route' => route('settings.verify'),
                        ],
                        [
                            'icon' => 'users',
                            'title' => 'Add Your Workers',
                            'description' => 'Import your existing worker roster',
                            'action' => 'Add Workers',
                            'route' => route('business.dashboard'),
                        ],
                        [
                            'icon' => 'building',
                            'title' => 'Add Client Businesses',
                            'description' => 'Connect client businesses to manage their shifts',
                            'action' => 'Add Clients',
                            'route' => route('business.dashboard'),
                        ],
                    ],
                ];

            default:
                return [
                    'title' => 'Welcome to OvertimeStaff!',
                    'subtitle' => 'Your account is ready',
                    'steps' => [],
                ];
        }
    }

    /**
     * Redirect user to appropriate dashboard.
     */
    protected function redirectToDashboard($user)
    {
        switch ($user->user_type) {
            case 'worker':
                return redirect()->route('worker.dashboard');
            case 'business':
            case 'agency':
                return redirect()->route('business.dashboard');
            case 'admin':
                return redirect()->route('admin');
            default:
                return redirect()->route('home');
        }
    }
}
