<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\CrossBorderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * GLO-008: Cross-Border Payments - Bank Account Controller
 *
 * Allows users to manage their bank accounts for cross-border payouts.
 */
class BankAccountController extends Controller
{
    protected CrossBorderPaymentService $crossBorderService;

    public function __construct(CrossBorderPaymentService $crossBorderService)
    {
        $this->middleware('auth');
        $this->crossBorderService = $crossBorderService;
    }

    /**
     * Display a list of user's bank accounts.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $bankAccounts = BankAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'bank_accounts' => $bankAccounts->map(fn ($account) => $this->formatBankAccount($account)),
            ]);
        }

        return view('payment.bank-accounts.index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Show the form for creating a new bank account.
     */
    public function create(Request $request)
    {
        return view('payment.bank-accounts.create', [
            'countries' => $this->getSupportedCountries(),
            'currencies' => $this->getSupportedCurrencies(),
        ]);
    }

    /**
     * Store a newly created bank account.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'currency_code' => ['required', 'string', 'size:3'],
            'account_type' => ['required', Rule::in(['checking', 'savings'])],

            // US accounts
            'routing_number' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:50'],

            // UK accounts
            'sort_code' => ['nullable', 'string', 'max:10'],

            // Australian accounts
            'bsb_code' => ['nullable', 'string', 'max:10'],

            // International accounts
            'iban' => ['nullable', 'string', 'max:50'],
            'swift_bic' => ['nullable', 'string', 'max:15'],

            'is_primary' => ['nullable', 'boolean'],
        ]);

        // Create the bank account
        $bankAccount = new BankAccount($validated);
        $bankAccount->user_id = $user->id;

        // Validate based on country requirements
        $validation = $this->crossBorderService->validateBankAccount($bankAccount);
        if (! $validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors'],
            ], 422);
        }

        try {
            DB::transaction(function () use ($bankAccount, $user, $validated) {
                $bankAccount->save();

                // If this should be primary or is the first account
                $existingCount = BankAccount::where('user_id', $user->id)
                    ->where('id', '!=', $bankAccount->id)
                    ->count();

                if ($existingCount === 0 || ($validated['is_primary'] ?? false)) {
                    $bankAccount->markAsPrimary();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Bank account added successfully.',
                'bank_account' => $this->formatBankAccount($bankAccount->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to add bank account. Please try again.',
            ], 500);
        }
    }

    /**
     * Display the specified bank account.
     */
    public function show(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('view', $bankAccount);

        return response()->json([
            'success' => true,
            'bank_account' => $this->formatBankAccount($bankAccount),
            'transfers' => $bankAccount->transfers()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($transfer) => [
                    'id' => $transfer->id,
                    'reference' => $transfer->transfer_reference,
                    'amount' => $transfer->getFormattedDestinationAmount(),
                    'status' => $transfer->getStatusLabel(),
                    'status_color' => $transfer->getStatusColor(),
                    'created_at' => $transfer->created_at->format('M j, Y'),
                ]),
        ]);
    }

    /**
     * Update the specified bank account.
     */
    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $bankAccount);

        $validated = $request->validate([
            'account_holder_name' => ['sometimes', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        // Only allow updating non-sensitive fields
        $bankAccount->fill($validated);
        $bankAccount->save();

        if ($validated['is_primary'] ?? false) {
            $bankAccount->markAsPrimary();
        }

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully.',
            'bank_account' => $this->formatBankAccount($bankAccount->fresh()),
        ]);
    }

    /**
     * Remove the specified bank account.
     */
    public function destroy(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('delete', $bankAccount);

        $user = $request->user();

        // Check if this is the only bank account
        $accountCount = BankAccount::where('user_id', $user->id)->count();

        // Check for pending transfers
        $pendingTransfers = $bankAccount->transfers()
            ->whereIn('status', ['pending', 'processing', 'sent'])
            ->exists();

        if ($pendingTransfers) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete this bank account while transfers are pending.',
            ], 422);
        }

        $wasPrimary = $bankAccount->is_primary;

        // Soft delete
        $bankAccount->delete();

        // If this was primary and there are other accounts, set a new primary
        if ($wasPrimary && $accountCount > 1) {
            $newPrimary = BankAccount::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $newPrimary?->markAsPrimary();
        }

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully.',
        ]);
    }

    /**
     * Set a bank account as the primary account.
     */
    public function setPrimary(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $bankAccount);

        $bankAccount->markAsPrimary();

        return response()->json([
            'success' => true,
            'message' => 'Bank account set as primary.',
            'bank_account' => $this->formatBankAccount($bankAccount->fresh()),
        ]);
    }

    /**
     * Validate bank account details (IBAN, routing number, etc.).
     */
    public function validate(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $value = $request->input('value');

        $valid = match ($type) {
            'iban' => $this->crossBorderService->validateIBAN($value),
            'routing_number' => $this->crossBorderService->validateRoutingNumber($value),
            'sort_code' => $this->crossBorderService->validateSortCode($value),
            'bsb_code' => $this->crossBorderService->validateBSBCode($value),
            'swift_bic' => $this->crossBorderService->validateSwiftBic($value),
            default => false,
        };

        $error = null;
        if (! $valid && $type === 'iban') {
            $error = $this->crossBorderService->getIBANValidationError($value);
        }

        return response()->json([
            'valid' => $valid,
            'error' => $error,
        ]);
    }

    /**
     * Get a transfer quote for a bank account.
     */
    public function getQuote(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('view', $bankAccount);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $sourceCountry = config('services.platform.country', 'US');

        $quote = $this->crossBorderService->getQuote(
            $sourceCountry,
            $bankAccount->country_code,
            $validated['amount'],
            $validated['payment_method'] ?? null
        );

        if (! $quote['success']) {
            return response()->json([
                'success' => false,
                'error' => $quote['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'quote' => [
                'source_amount' => $quote['fees']['source_amount'],
                'destination_amount' => $quote['fees']['destination_amount'],
                'total_fee' => $quote['fees']['total_fee'],
                'total_deduction' => $quote['fees']['total_deduction'],
                'exchange_rate' => $quote['fees']['exchange_rate'],
                'source_currency' => $quote['fees']['source_currency'],
                'destination_currency' => $quote['fees']['destination_currency'],
                'payment_method' => $quote['corridor']->payment_method,
                'payment_method_label' => $quote['corridor']->getPaymentMethodLabel(),
                'estimated_delivery' => $quote['delivery']['display'],
                'estimated_arrival_min' => $quote['delivery']['min_date']->format('M j, Y'),
                'estimated_arrival_max' => $quote['delivery']['max_date']->format('M j, Y'),
            ],
        ]);
    }

    /**
     * Get available payment corridors for a bank account.
     */
    public function getCorridors(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('view', $bankAccount);

        $amount = $request->input('amount');
        $sourceCountry = config('services.platform.country', 'US');

        $corridors = $this->crossBorderService->getAvailableCorridors(
            $sourceCountry,
            $bankAccount->country_code,
            $amount ? (float) $amount : null
        );

        return response()->json([
            'success' => true,
            'corridors' => $corridors->map(fn ($corridor) => [
                'id' => $corridor->id,
                'payment_method' => $corridor->payment_method,
                'payment_method_label' => $corridor->getPaymentMethodLabel(),
                'fee_fixed' => $corridor->fee_fixed,
                'fee_percent' => $corridor->fee_percent,
                'estimated_delivery' => $corridor->getEstimatedDeliveryRange(),
                'min_amount' => $corridor->min_amount,
                'max_amount' => $corridor->max_amount,
            ]),
        ]);
    }

    /**
     * Format bank account for JSON response.
     */
    protected function formatBankAccount(BankAccount $account): array
    {
        return [
            'id' => $account->id,
            'account_holder_name' => $account->account_holder_name,
            'bank_name' => $account->bank_name,
            'country_code' => $account->country_code,
            'currency_code' => $account->currency_code,
            'account_type' => $account->account_type,
            'account_type_label' => $account->getAccountTypeLabel(),
            'masked_account' => $account->getMaskedAccountNumber(),
            'display_name' => $account->getDisplayName(),
            'is_verified' => $account->is_verified,
            'is_primary' => $account->is_primary,
            'suggested_payment_method' => $account->getSuggestedPaymentMethod(),
            'created_at' => $account->created_at->format('M j, Y'),
        ];
    }

    /**
     * Get list of supported countries.
     */
    protected function getSupportedCountries(): array
    {
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'IE' => 'Ireland',
            'PT' => 'Portugal',
            'PL' => 'Poland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IN' => 'India',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'KE' => 'Kenya',
            'PH' => 'Philippines',
        ];
    }

    /**
     * Get list of supported currencies.
     */
    protected function getSupportedCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'JPY' => 'Japanese Yen',
            'INR' => 'Indian Rupee',
            'MXN' => 'Mexican Peso',
            'BRL' => 'Brazilian Real',
            'ZAR' => 'South African Rand',
            'NGN' => 'Nigerian Naira',
            'KES' => 'Kenyan Shilling',
            'PHP' => 'Philippine Peso',
            'SGD' => 'Singapore Dollar',
            'HKD' => 'Hong Kong Dollar',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'PLN' => 'Polish Zloty',
        ];
    }
}
