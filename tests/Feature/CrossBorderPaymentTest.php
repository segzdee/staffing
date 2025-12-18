<?php

use App\Models\BankAccount;
use App\Models\CrossBorderTransfer;
use App\Models\PaymentCorridor;
use App\Models\User;
use App\Services\CrossBorderPaymentService;
use App\Support\IBANValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CrossBorderPaymentService;
});

// =========================================
// IBAN Validator Tests
// =========================================

test('validates correct german iban', function () {
    expect(IBANValidator::validate('DE89370400440532013000'))->toBeTrue();
});

test('validates correct french iban', function () {
    expect(IBANValidator::validate('FR1420041010050500013M02606'))->toBeTrue();
});

test('rejects invalid iban checksum', function () {
    // Changed last digit
    expect(IBANValidator::validate('DE89370400440532013001'))->toBeFalse();
});

test('rejects iban with wrong length', function () {
    // German IBAN should be 22 characters
    expect(IBANValidator::validate('DE8937040044053201'))->toBeFalse();
});

test('normalizes iban with spaces', function () {
    expect(IBANValidator::normalize('DE89 3704 0044 0532 0130 00'))
        ->toBe('DE89370400440532013000');
});

test('formats iban for display', function () {
    $formatted = IBANValidator::format('DE89370400440532013000');
    expect($formatted)->toBe('DE89 3704 0044 0532 0130 00');
});

test('extracts country code from iban', function () {
    expect(IBANValidator::getCountryCode('DE89370400440532013000'))->toBe('DE');
    expect(IBANValidator::getCountryCode('FR1420041010050500013M02606'))->toBe('FR');
});

test('returns validation error message', function () {
    $error = IBANValidator::getValidationError('DE8937040044');
    expect($error)->not->toBeNull();
    expect($error)->toContain('22 characters');
});

// =========================================
// Routing Number Validator Tests
// =========================================

test('validates correct routing number', function () {
    // Bank of America routing number
    expect($this->service->validateRoutingNumber('021000021'))->toBeTrue();
});

test('rejects invalid routing number checksum', function () {
    expect($this->service->validateRoutingNumber('123456789'))->toBeFalse();
});

test('rejects routing number with wrong length', function () {
    expect($this->service->validateRoutingNumber('12345678'))->toBeFalse();
    expect($this->service->validateRoutingNumber('1234567890'))->toBeFalse();
});

// =========================================
// Sort Code Validator Tests
// =========================================

test('validates correct sort code', function () {
    expect($this->service->validateSortCode('123456'))->toBeTrue();
    expect($this->service->validateSortCode('12-34-56'))->toBeTrue();
});

test('rejects sort code with wrong length', function () {
    expect($this->service->validateSortCode('12345'))->toBeFalse();
    expect($this->service->validateSortCode('1234567'))->toBeFalse();
});

// =========================================
// SWIFT/BIC Validator Tests
// =========================================

test('validates correct swift bic 8 char', function () {
    expect($this->service->validateSwiftBic('DEUTDEFF'))->toBeTrue();
});

test('validates correct swift bic 11 char', function () {
    expect($this->service->validateSwiftBic('DEUTDEFFXXX'))->toBeTrue();
});

test('rejects invalid swift bic', function () {
    expect($this->service->validateSwiftBic('DEUT'))->toBeFalse();
    expect($this->service->validateSwiftBic('12345678'))->toBeFalse();
});

// =========================================
// Payment Corridor Tests
// =========================================

test('creates payment corridor', function () {
    $corridor = PaymentCorridor::factory()->sepa()->create();

    expect($corridor)->toBeInstanceOf(PaymentCorridor::class);
    expect($corridor->payment_method)->toBe(PaymentCorridor::METHOD_SEPA);
});

test('calculates corridor fee', function () {
    $corridor = PaymentCorridor::factory()->create([
        'fee_fixed' => 5.00,
        'fee_percent' => 1.00,
    ]);

    $fee = $corridor->calculateFee(1000);

    // 5.00 + (1000 * 0.01) = 5.00 + 10.00 = 15.00
    expect($fee)->toBe(15.00);
});

test('checks corridor amount limits', function () {
    $corridor = PaymentCorridor::factory()->create([
        'min_amount' => 100,
        'max_amount' => 10000,
    ]);

    expect($corridor->supportsAmount(50))->toBeFalse();
    expect($corridor->supportsAmount(500))->toBeTrue();
    expect($corridor->supportsAmount(15000))->toBeFalse();
});

test('finds best corridor', function () {
    // Create two corridors for same route
    PaymentCorridor::factory()->create([
        'source_country' => 'US',
        'destination_country' => 'DE',
        'payment_method' => PaymentCorridor::METHOD_SWIFT,
        'fee_fixed' => 25.00,
        'fee_percent' => 1.00,
    ]);

    PaymentCorridor::factory()->create([
        'source_country' => 'US',
        'destination_country' => 'DE',
        'payment_method' => PaymentCorridor::METHOD_SEPA,
        'fee_fixed' => 2.00,
        'fee_percent' => 0.50,
    ]);

    $best = $this->service->getBestCorridor('US', 'DE', 1000);

    // SEPA should be cheaper
    expect($best->payment_method)->toBe(PaymentCorridor::METHOD_SEPA);
});

// =========================================
// Bank Account Tests
// =========================================

test('creates us bank account', function () {
    $user = User::factory()->create(['user_type' => 'worker']);
    $account = BankAccount::factory()->us()->create(['user_id' => $user->id]);

    expect($account->country_code)->toBe('US');
    expect($account->currency_code)->toBe('USD');
    expect($account->routing_number)->not->toBeNull();
    expect($account->account_number)->not->toBeNull();
});

test('creates uk bank account', function () {
    $user = User::factory()->create(['user_type' => 'worker']);
    $account = BankAccount::factory()->uk()->create(['user_id' => $user->id]);

    expect($account->country_code)->toBe('GB');
    expect($account->currency_code)->toBe('GBP');
    expect($account->sort_code)->not->toBeNull();
});

test('creates sepa bank account', function () {
    $user = User::factory()->create(['user_type' => 'worker']);
    $account = BankAccount::factory()->sepa()->create(['user_id' => $user->id]);

    expect($account->currency_code)->toBe('EUR');
    expect($account->iban)->not->toBeNull();
    expect($account->isSepaCountry())->toBeTrue();
});

test('marks bank account as primary', function () {
    $user = User::factory()->create(['user_type' => 'worker']);

    $account1 = BankAccount::factory()->create([
        'user_id' => $user->id,
        'is_primary' => true,
    ]);

    $account2 = BankAccount::factory()->create([
        'user_id' => $user->id,
        'is_primary' => false,
    ]);

    // Mark account2 as primary
    $account2->markAsPrimary();

    expect($account1->fresh()->is_primary)->toBeFalse();
    expect($account2->fresh()->is_primary)->toBeTrue();
});

test('masks account number', function () {
    $account = BankAccount::factory()->create([
        'account_number' => '12345678',
        'iban' => null,
    ]);

    $masked = $account->getMaskedAccountNumber();

    expect($masked)->toContain('5678');
    expect($masked)->toContain('*');
});

test('suggests payment method based on country', function () {
    $usAccount = BankAccount::factory()->us()->make();
    expect($usAccount->getSuggestedPaymentMethod())->toBe(PaymentCorridor::METHOD_ACH);

    $ukAccount = BankAccount::factory()->uk()->make();
    expect($ukAccount->getSuggestedPaymentMethod())->toBe(PaymentCorridor::METHOD_FASTER_PAYMENTS);

    $sepaAccount = BankAccount::factory()->sepa()->make();
    expect($sepaAccount->getSuggestedPaymentMethod())->toBe(PaymentCorridor::METHOD_SEPA);
});

// =========================================
// Cross Border Transfer Tests
// =========================================

test('generates unique transfer reference', function () {
    $transfer = CrossBorderTransfer::factory()->create();

    expect($transfer->transfer_reference)->not->toBeNull();
    expect($transfer->transfer_reference)->toStartWith('CBT-');
});

test('calculates total deduction', function () {
    $transfer = CrossBorderTransfer::factory()->create([
        'source_amount' => 1000.00,
        'fee_amount' => 15.00,
    ]);

    expect($transfer->getTotalDeduction())->toBe(1015.00);
});

test('transfer status transitions', function () {
    $transfer = CrossBorderTransfer::factory()->pending()->create();

    expect($transfer->isPending())->toBeTrue();
    expect($transfer->isProcessing())->toBeFalse();

    $transfer->markAsProcessing();
    expect($transfer->fresh()->isProcessing())->toBeTrue();

    $transfer->markAsSent('PROVIDER-123');
    expect($transfer->fresh()->isSent())->toBeTrue();
    expect($transfer->fresh()->provider_reference)->toBe('PROVIDER-123');
    expect($transfer->fresh()->sent_at)->not->toBeNull();

    $transfer->markAsCompleted();
    expect($transfer->fresh()->isCompleted())->toBeTrue();
    expect($transfer->fresh()->completed_at)->not->toBeNull();
});

test('transfer can be failed', function () {
    $transfer = CrossBorderTransfer::factory()->pending()->create();

    $transfer->markAsFailed('Invalid bank details');

    expect($transfer->fresh()->isFailed())->toBeTrue();
    expect($transfer->fresh()->failure_reason)->toBe('Invalid bank details');
});

// =========================================
// Service Integration Tests
// =========================================

test('calculates fees with exchange rate', function () {
    $corridor = PaymentCorridor::factory()->create([
        'source_currency' => 'USD',
        'destination_currency' => 'EUR',
        'fee_fixed' => 5.00,
        'fee_percent' => 0.50,
    ]);

    $fees = $this->service->calculateFees($corridor, 1000);

    expect($fees['source_amount'])->toBe(1000.0);
    expect($fees['total_fee'])->toBe(10.00); // 5.00 + 5.00
    expect($fees['total_deduction'])->toBe(1010.00);
    expect($fees['source_currency'])->toBe('USD');
    expect($fees['destination_currency'])->toBe('EUR');
    expect($fees['exchange_rate'])->toBeGreaterThan(0);
});

test('estimates arrival with business days', function () {
    $corridor = PaymentCorridor::factory()->create([
        'estimated_days_min' => 1,
        'estimated_days_max' => 3,
    ]);

    $estimate = $this->service->estimateArrival($corridor);

    expect($estimate)->toHaveKey('min_date');
    expect($estimate)->toHaveKey('max_date');
    expect($estimate)->toHaveKey('display');

    // Max should be at least min
    expect($estimate['max_date']->gte($estimate['min_date']))->toBeTrue();
});

test('initiates transfer successfully', function () {
    $user = User::factory()->create(['user_type' => 'worker']);

    // Create corridor
    PaymentCorridor::factory()->create([
        'source_country' => 'US',
        'destination_country' => 'DE',
        'source_currency' => 'USD',
        'destination_currency' => 'EUR',
        'payment_method' => PaymentCorridor::METHOD_SEPA,
        'fee_fixed' => 2.00,
        'fee_percent' => 0.35,
    ]);

    // Create bank account with valid IBAN
    $account = BankAccount::factory()->create([
        'user_id' => $user->id,
        'country_code' => 'DE',
        'currency_code' => 'EUR',
        'iban' => 'DE89370400440532013000',
        'swift_bic' => 'COBADEFFXXX',
    ]);

    $result = $this->service->initiateTransfer($user, $account, 1000);

    expect($result['success'])->toBeTrue();
    expect($result['transfer'])->not->toBeNull();
    expect($result['transfer'])->toBeInstanceOf(CrossBorderTransfer::class);
    expect($result['transfer']->status)->toBe(CrossBorderTransfer::STATUS_PENDING);
});

test('rejects transfer with invalid bank account', function () {
    $user = User::factory()->create(['user_type' => 'worker']);

    // Create corridor
    PaymentCorridor::factory()->create([
        'source_country' => 'US',
        'destination_country' => 'DE',
    ]);

    // Create bank account missing required IBAN
    $account = BankAccount::factory()->create([
        'user_id' => $user->id,
        'country_code' => 'DE',
        'currency_code' => 'EUR',
        'iban' => null,
        'account_number' => null,
    ]);

    $result = $this->service->initiateTransfer($user, $account, 1000);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('gets user transfer statistics', function () {
    $user = User::factory()->create(['user_type' => 'worker']);
    $account = BankAccount::factory()->create(['user_id' => $user->id]);

    // Create various transfers
    CrossBorderTransfer::factory()->pending()->create([
        'user_id' => $user->id,
        'bank_account_id' => $account->id,
        'source_amount' => 500,
    ]);

    CrossBorderTransfer::factory()->completed()->create([
        'user_id' => $user->id,
        'bank_account_id' => $account->id,
        'source_amount' => 1000,
    ]);

    CrossBorderTransfer::factory()->failed()->create([
        'user_id' => $user->id,
        'bank_account_id' => $account->id,
        'source_amount' => 200,
    ]);

    $stats = $this->service->getUserTransferStats($user);

    expect($stats['total_transfers'])->toBe(3);
    expect($stats['total_amount'])->toBe(1700.00);
    expect($stats['pending_count'])->toBe(1);
    expect($stats['completed_count'])->toBe(1);
    expect($stats['failed_count'])->toBe(1);
});
