<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Emergency Contact Controller
 * SAF-001: Emergency Contact System
 *
 * Handles worker emergency contact management including
 * adding, updating, verifying, and prioritizing contacts.
 */
class EmergencyContactController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'worker']);
    }

    /**
     * Display emergency contacts management page.
     *
     * GET /worker/emergency-contacts
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $worker = Auth::user();
        $contacts = EmergencyContact::forUser($worker->id)
            ->orderedByPriority()
            ->get();

        return view('worker.emergency-contacts.index', [
            'contacts' => $contacts,
            'relationships' => EmergencyContact::RELATIONSHIPS,
            'maxContacts' => EmergencyContact::MAX_CONTACTS_PER_USER,
            'remainingSlots' => EmergencyContact::remainingSlots($worker->id),
        ]);
    }

    /**
     * Get all emergency contacts for the worker (API).
     */
    public function list(): JsonResponse
    {
        $worker = Auth::user();
        $contacts = EmergencyContact::forUser($worker->id)
            ->orderedByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => $contacts,
                'max_contacts' => EmergencyContact::MAX_CONTACTS_PER_USER,
                'remaining_slots' => EmergencyContact::remainingSlots($worker->id),
                'relationships' => EmergencyContact::RELATIONSHIPS,
            ],
        ]);
    }

    /**
     * Store a new emergency contact.
     */
    public function store(Request $request): JsonResponse
    {
        $worker = Auth::user();

        // Check if user can add more contacts
        if (! EmergencyContact::canAddMore($worker->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum number of emergency contacts reached.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => ['required', 'string', Rule::in(array_keys(EmergencyContact::RELATIONSHIPS))],
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_primary' => 'boolean',
        ]);

        // Get next priority
        $validated['priority'] = EmergencyContact::getNextPriority($worker->id);
        $validated['user_id'] = $worker->id;

        $contact = EmergencyContact::create($validated);

        // If this is set as primary, update others
        if ($validated['is_primary'] ?? false) {
            $contact->setAsPrimary();
        }

        // If this is the first contact, make it primary
        $totalContacts = EmergencyContact::forUser($worker->id)->count();
        if ($totalContacts === 1) {
            $contact->setAsPrimary();
        }

        // Generate verification code
        $verificationCode = $contact->generateVerificationCode();

        // In production, send verification SMS/email here
        // For now, we'll return the code in development
        $responseData = [
            'success' => true,
            'message' => 'Emergency contact added successfully. A verification code has been sent.',
            'data' => $contact->fresh(),
        ];

        if (app()->environment('local', 'development')) {
            $responseData['verification_code'] = $verificationCode;
        }

        return response()->json($responseData, 201);
    }

    /**
     * Update an existing emergency contact.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $worker = Auth::user();
        $contact = EmergencyContact::forUser($worker->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'relationship' => ['sometimes', 'required', 'string', Rule::in(array_keys(EmergencyContact::RELATIONSHIPS))],
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        // If phone number changed, require re-verification
        if (isset($validated['phone']) && $validated['phone'] !== $contact->phone) {
            $validated['is_verified'] = false;
            $validated['verified_at'] = null;
        }

        $contact->update($validated);

        // If phone changed, generate new verification code
        if (isset($validated['phone']) && $validated['phone'] !== $contact->getOriginal('phone')) {
            $verificationCode = $contact->generateVerificationCode();

            $responseData = [
                'success' => true,
                'message' => 'Contact updated. Phone number changed - new verification required.',
                'data' => $contact->fresh(),
            ];

            if (app()->environment('local', 'development')) {
                $responseData['verification_code'] = $verificationCode;
            }

            return response()->json($responseData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency contact updated successfully.',
            'data' => $contact->fresh(),
        ]);
    }

    /**
     * Delete an emergency contact.
     */
    public function destroy(int $id): JsonResponse
    {
        $worker = Auth::user();
        $contact = EmergencyContact::forUser($worker->id)->findOrFail($id);

        $wasPrimary = $contact->is_primary;
        $contact->delete();

        // Normalize priorities after deletion
        EmergencyContact::normalizePriorities($worker->id);

        // If deleted contact was primary, assign new primary
        if ($wasPrimary) {
            $newPrimary = EmergencyContact::forUser($worker->id)
                ->orderedByPriority()
                ->first();

            if ($newPrimary) {
                $newPrimary->setAsPrimary();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency contact deleted successfully.',
        ]);
    }

    /**
     * Verify an emergency contact with code.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $worker = Auth::user();
        $contact = EmergencyContact::forUser($worker->id)->findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if ($contact->verify($validated['code'])) {
            return response()->json([
                'success' => true,
                'message' => 'Emergency contact verified successfully.',
                'data' => $contact->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code.',
        ], 422);
    }

    /**
     * Resend verification code.
     */
    public function resendVerification(int $id): JsonResponse
    {
        $worker = Auth::user();
        $contact = EmergencyContact::forUser($worker->id)->findOrFail($id);

        if ($contact->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Contact is already verified.',
            ], 422);
        }

        $verificationCode = $contact->generateVerificationCode();

        // In production, send SMS/email here

        $responseData = [
            'success' => true,
            'message' => 'Verification code has been resent.',
        ];

        if (app()->environment('local', 'development')) {
            $responseData['verification_code'] = $verificationCode;
        }

        return response()->json($responseData);
    }

    /**
     * Set a contact as primary.
     */
    public function setPrimary(int $id): JsonResponse
    {
        $worker = Auth::user();
        $contact = EmergencyContact::forUser($worker->id)->findOrFail($id);

        $contact->setAsPrimary();

        return response()->json([
            'success' => true,
            'message' => 'Primary contact updated successfully.',
            'data' => $contact->fresh(),
        ]);
    }

    /**
     * Update contact priorities (reorder).
     */
    public function updatePriorities(Request $request): JsonResponse
    {
        $worker = Auth::user();

        $validated = $request->validate([
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|integer',
            'priorities.*.priority' => 'required|integer|min:1',
        ]);

        foreach ($validated['priorities'] as $item) {
            $contact = EmergencyContact::forUser($worker->id)->find($item['id']);
            if ($contact) {
                $contact->update(['priority' => $item['priority']]);
            }
        }

        // Normalize to ensure no gaps
        EmergencyContact::normalizePriorities($worker->id);

        $contacts = EmergencyContact::forUser($worker->id)
            ->orderedByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Contact priorities updated successfully.',
            'data' => $contacts,
        ]);
    }

    /**
     * Get verification status for all contacts.
     */
    public function verificationStatus(): JsonResponse
    {
        $worker = Auth::user();
        $contacts = EmergencyContact::forUser($worker->id)
            ->orderedByPriority()
            ->get(['id', 'name', 'is_verified', 'verified_at', 'is_primary']);

        $allVerified = $contacts->every(fn ($c) => $c->is_verified);
        $verifiedCount = $contacts->where('is_verified', true)->count();
        $hasPrimary = $contacts->where('is_primary', true)->count() > 0;

        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => $contacts,
                'all_verified' => $allVerified,
                'verified_count' => $verifiedCount,
                'total_count' => $contacts->count(),
                'has_primary' => $hasPrimary,
                'has_contacts' => $contacts->count() > 0,
            ],
        ]);
    }
}
