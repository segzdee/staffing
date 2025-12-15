<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Money\Money as BaseMoney;

class MoneyCast implements CastsAttributes
{
    public function __construct(
        protected string $currency = 'USD'
    ) {}

    public function get($model, string $key, $value, array $attributes): ?BaseMoney
    {
        if ($value === null) {
            return null;
        }

        return Money::fromCents((int) $value, $this->currency);
    }

    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BaseMoney) {
            return Money::toCents($value);
        }

        // If it's already cents (int)
        if (is_int($value)) {
            return $value;
        }

        // If it's a decimal (float/string)
        return Money::toCents(Money::fromDecimal($value, $this->currency));
    }
}
