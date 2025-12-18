<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrencyConversion;
use App\Models\CurrencyWallet;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * GLO-001: Multi-Currency Support - Currency Wallet Controller
 *
 * API controller for managing user currency wallets, conversions, and exchange rates.
 */
class CurrencyWalletController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Get all wallets for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $wallets = $this->currencyService->getUserWallets($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'wallets' => $wallets->map(function ($wallet) {
                        return [
                            'id' => $wallet->id,
                            'currency_code' => $wallet->currency_code,
                            'currency_name' => $wallet->currency_name,
                            'currency_symbol' => $wallet->currency_symbol,
                            'balance' => (float) $wallet->balance,
                            'pending_balance' => (float) $wallet->pending_balance,
                            'total_balance' => $wallet->total_balance,
                            'formatted_balance' => $wallet->formatted_balance,
                            'formatted_pending' => $wallet->formatted_pending_balance,
                            'formatted_total' => $wallet->formatted_total_balance,
                            'is_primary' => $wallet->is_primary,
                        ];
                    }),
                    'primary_wallet' => $this->currencyService->getPrimaryWallet($user)?->currency_code,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@index error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallets.',
            ], 500);
        }
    }

    /**
     * Get a specific wallet by currency code.
     */
    public function show(string $currency): JsonResponse
    {
        try {
            $user = Auth::user();
            $currency = strtoupper($currency);

            if (! $this->currencyService->isSupportedCurrency($currency)) {
                return response()->json([
                    'success' => false,
                    'message' => "Currency {$currency} is not supported.",
                ], 400);
            }

            $wallet = CurrencyWallet::where('user_id', $user->id)
                ->where('currency_code', $currency)
                ->first();

            if (! $wallet) {
                return response()->json([
                    'success' => false,
                    'message' => "No wallet found for {$currency}.",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $wallet->id,
                    'currency_code' => $wallet->currency_code,
                    'currency_name' => $wallet->currency_name,
                    'currency_symbol' => $wallet->currency_symbol,
                    'balance' => (float) $wallet->balance,
                    'pending_balance' => (float) $wallet->pending_balance,
                    'total_balance' => $wallet->total_balance,
                    'formatted_balance' => $wallet->formatted_balance,
                    'formatted_pending' => $wallet->formatted_pending_balance,
                    'formatted_total' => $wallet->formatted_total_balance,
                    'is_primary' => $wallet->is_primary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@show error', [
                'user_id' => Auth::id(),
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet.',
            ], 500);
        }
    }

    /**
     * Create a new wallet for a currency.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in($this->currencyService->getSupportedCurrencies()),
            ],
        ]);

        try {
            $user = Auth::user();
            $currency = strtoupper($request->currency_code);

            // Check if wallet already exists
            $existingWallet = CurrencyWallet::where('user_id', $user->id)
                ->where('currency_code', $currency)
                ->first();

            if ($existingWallet) {
                return response()->json([
                    'success' => false,
                    'message' => "Wallet for {$currency} already exists.",
                ], 400);
            }

            $wallet = $this->currencyService->getOrCreateWallet($user, $currency);

            return response()->json([
                'success' => true,
                'message' => "Wallet created for {$currency}.",
                'data' => [
                    'id' => $wallet->id,
                    'currency_code' => $wallet->currency_code,
                    'currency_name' => $wallet->currency_name,
                    'currency_symbol' => $wallet->currency_symbol,
                    'balance' => (float) $wallet->balance,
                    'is_primary' => $wallet->is_primary,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@store error', [
                'user_id' => Auth::id(),
                'currency' => $request->currency_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create wallet.',
            ], 500);
        }
    }

    /**
     * Set a wallet as primary.
     */
    public function setPrimary(string $currency): JsonResponse
    {
        try {
            $user = Auth::user();
            $currency = strtoupper($currency);

            $wallet = CurrencyWallet::where('user_id', $user->id)
                ->where('currency_code', $currency)
                ->first();

            if (! $wallet) {
                return response()->json([
                    'success' => false,
                    'message' => "No wallet found for {$currency}.",
                ], 404);
            }

            $wallet->setAsPrimary();

            return response()->json([
                'success' => true,
                'message' => "{$currency} is now your primary wallet.",
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@setPrimary error', [
                'user_id' => Auth::id(),
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary wallet.',
            ], 500);
        }
    }

    /**
     * Get a conversion preview (without executing).
     */
    public function previewConversion(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => [
                'required',
                'string',
                'size:3',
                Rule::in($this->currencyService->getSupportedCurrencies()),
            ],
            'to_currency' => [
                'required',
                'string',
                'size:3',
                'different:from_currency',
                Rule::in($this->currencyService->getSupportedCurrencies()),
            ],
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $preview = $this->currencyService->previewConversion(
                $request->from_currency,
                $request->to_currency,
                (float) $request->amount
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@previewConversion error', [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute a currency conversion.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => [
                'required',
                'string',
                'size:3',
                Rule::in($this->currencyService->getSupportedCurrencies()),
            ],
            'to_currency' => [
                'required',
                'string',
                'size:3',
                'different:from_currency',
                Rule::in($this->currencyService->getSupportedCurrencies()),
            ],
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $user = Auth::user();

            $conversion = $this->currencyService->convert(
                $user,
                $request->from_currency,
                $request->to_currency,
                (float) $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversion completed successfully.',
                'data' => [
                    'conversion_id' => $conversion->id,
                    'from_currency' => $conversion->from_currency,
                    'to_currency' => $conversion->to_currency,
                    'from_amount' => (float) $conversion->from_amount,
                    'to_amount' => (float) $conversion->to_amount,
                    'exchange_rate' => (float) $conversion->exchange_rate,
                    'fee_amount' => (float) $conversion->fee_amount,
                    'formatted_from' => $conversion->formatted_from_amount,
                    'formatted_to' => $conversion->formatted_to_amount,
                    'status' => $conversion->status,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@convert error', [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete conversion.',
            ], 500);
        }
    }

    /**
     * Get conversion history for the user.
     */
    public function conversionHistory(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'nullable|string|size:3',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $user = Auth::user();
            $limit = $request->input('limit', 20);

            $query = CurrencyConversion::where('user_id', $user->id)
                ->recent();

            if ($request->filled('currency')) {
                $currency = strtoupper($request->currency);
                $query->where(function ($q) use ($currency) {
                    $q->where('from_currency', $currency)
                        ->orWhere('to_currency', $currency);
                });
            }

            $conversions = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $conversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'from_currency' => $conversion->from_currency,
                        'to_currency' => $conversion->to_currency,
                        'from_amount' => (float) $conversion->from_amount,
                        'to_amount' => (float) $conversion->to_amount,
                        'exchange_rate' => (float) $conversion->exchange_rate,
                        'fee_amount' => (float) $conversion->fee_amount,
                        'formatted_from' => $conversion->formatted_from_amount,
                        'formatted_to' => $conversion->formatted_to_amount,
                        'status' => $conversion->status,
                        'created_at' => $conversion->created_at->toIso8601String(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@conversionHistory error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversion history.',
            ], 500);
        }
    }

    /**
     * Get current exchange rates.
     */
    public function exchangeRates(Request $request): JsonResponse
    {
        $request->validate([
            'base' => 'nullable|string|size:3',
        ]);

        try {
            $baseCurrency = $request->input('base', config('currencies.default', 'EUR'));
            $baseCurrency = strtoupper($baseCurrency);

            if (! $this->currencyService->isSupportedCurrency($baseCurrency)) {
                return response()->json([
                    'success' => false,
                    'message' => "Currency {$baseCurrency} is not supported.",
                ], 400);
            }

            $supportedCurrencies = $this->currencyService->getSupportedCurrencies();
            $rates = [];

            foreach ($supportedCurrencies as $currency) {
                if ($currency === $baseCurrency) {
                    continue;
                }

                $rates[$currency] = [
                    'rate' => $this->currencyService->getExchangeRate($baseCurrency, $currency),
                    'inverse' => $this->currencyService->getExchangeRate($currency, $baseCurrency),
                ];
            }

            $status = $this->exchangeRateService->getRateStatus();

            return response()->json([
                'success' => true,
                'data' => [
                    'base_currency' => $baseCurrency,
                    'rates' => $rates,
                    'last_updated' => $status['last_updated']?->toIso8601String(),
                    'source' => $status['source'],
                    'is_stale' => $status['is_stale'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@exchangeRates error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exchange rates.',
            ], 500);
        }
    }

    /**
     * Get supported currencies list.
     */
    public function supportedCurrencies(): JsonResponse
    {
        try {
            $currencies = $this->currencyService->getAllCurrencyDetails();

            return response()->json([
                'success' => true,
                'data' => [
                    'currencies' => $currencies,
                    'default' => config('currencies.default', 'EUR'),
                    'conversion_fee_percent' => config('currencies.conversion_fee_percent', 1.5),
                    'minimum_conversion_amount' => config('currencies.minimum_conversion_amount', 10.00),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@supportedCurrencies error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supported currencies.',
            ], 500);
        }
    }

    /**
     * Get total balance across all wallets in a target currency.
     */
    public function totalBalance(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            $user = Auth::user();
            $targetCurrency = $request->input(
                'currency',
                $this->currencyService->getPrimaryWallet($user)?->currency_code ?? config('currencies.default', 'EUR')
            );
            $targetCurrency = strtoupper($targetCurrency);

            if (! $this->currencyService->isSupportedCurrency($targetCurrency)) {
                return response()->json([
                    'success' => false,
                    'message' => "Currency {$targetCurrency} is not supported.",
                ], 400);
            }

            $totalBalance = $this->currencyService->getTotalBalanceInCurrency($user, $targetCurrency);

            return response()->json([
                'success' => true,
                'data' => [
                    'currency' => $targetCurrency,
                    'total_balance' => $totalBalance,
                    'formatted_balance' => $this->currencyService->formatCurrency($totalBalance, $targetCurrency),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CurrencyWalletController@totalBalance error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate total balance.',
            ], 500);
        }
    }
}
