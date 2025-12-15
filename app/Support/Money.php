<?php

namespace App\Support;

use Money\Money as BaseMoney;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use NumberFormatter;

class Money
{
    /**
     * Create Money from cents (integer)
     */
    public static function fromCents(int $cents, string $currency = 'USD'): BaseMoney
    {
        return new BaseMoney($cents, new Currency($currency));
    }

    /**
     * Create Money from decimal (float)
     */
    public static function fromDecimal(float|string $amount, string $currency = 'USD'): BaseMoney
    {
        $cents = (int) round((float) $amount * 100);
        return new BaseMoney($cents, new Currency($currency));
    }

    /**
     * Format money for display
     */
    public static function format(BaseMoney $money, string $locale = 'en_US'): string
    {
        $formatter = new IntlMoneyFormatter(
            new NumberFormatter($locale, NumberFormatter::CURRENCY),
            new \Money\Currencies\ISOCurrencies()
        );
        return $formatter->format($money);
    }

    /**
     * Get cents as integer
     */
    public static function toCents(BaseMoney $money): int
    {
        return (int) $money->getAmount();
    }

    /**
     * Get as decimal float
     */
    public static function toDecimal(BaseMoney $money): float
    {
        return (float) $money->getAmount() / 100;
    }

    /**
     * Calculate platform fee (10%)
     */
    public static function calculatePlatformFee(BaseMoney $amount): BaseMoney
    {
        return $amount->multiply('0.10');
    }

    /**
     * Calculate agency commission
     */
    public static function calculateAgencyCommission(BaseMoney $amount, float $rate = 0.15): BaseMoney
    {
        return $amount->multiply((string) $rate);
    }

    /**
     * Calculate worker payout
     */
    public static function calculateWorkerPayout(
        BaseMoney $grossAmount,
        bool $hasAgency = false,
        float $agencyRate = 0.15
    ): array {
        $platformFee = self::calculatePlatformFee($grossAmount);
        $afterPlatform = $grossAmount->subtract($platformFee);

        if ($hasAgency) {
            $agencyFee = $afterPlatform->multiply((string) $agencyRate);
            $workerPayout = $afterPlatform->subtract($agencyFee);
        } else {
            $agencyFee = new BaseMoney(0, $grossAmount->getCurrency());
            $workerPayout = $afterPlatform;
        }

        return [
            'gross' => $grossAmount,
            'platform_fee' => $platformFee,
            'agency_fee' => $agencyFee,
            'worker_payout' => $workerPayout,
        ];
    }
}
