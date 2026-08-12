<?php

namespace App\Services;

use InvalidArgumentException;

class XlmAmount
{
    public const DECIMALS = 7;
    public const SCALE = 10_000_000;

    public function decimalToAtomic(string $amount): string
    {
        $amount = trim($amount);

        if (!preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,7}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('Enter a valid XLM amount with up to 7 decimal places.');
        }

        $whole = $matches[1];
        $fraction = str_pad($matches[2] ?? '', self::DECIMALS, '0');
        $atomic = ((int) $whole * self::SCALE) + (int) $fraction;

        if ($atomic <= 0) {
            throw new InvalidArgumentException('Amount too small.');
        }

        return (string) $atomic;
    }

    public function atomicToDecimal(string|int $atomic): string
    {
        $atomic = $this->normalizeAtomic($atomic);
        $units = (int) $atomic;
        $whole = intdiv($units, self::SCALE);
        $fraction = str_pad((string) ($units % self::SCALE), self::DECIMALS, '0', STR_PAD_LEFT);

        return "{$whole}.{$fraction}";
    }

    public function validateWithinConfiguredLimits(string $atomic): void
    {
        $min = $this->decimalToAtomic((string) config('yolixa.min_payment_amount', '0.0000001'));
        $max = $this->decimalToAtomic((string) config('yolixa.max_payment_amount', '1000'));

        if ($this->compareAtomic($atomic, $min) < 0) {
            throw new InvalidArgumentException('Amount too small.');
        }

        if ($this->compareAtomic($atomic, $max) > 0) {
            throw new InvalidArgumentException('Amount too large.');
        }
    }

    public function splitFee(string $amountAtomic, int $feeBps): array
    {
        $amount = (int) $this->normalizeAtomic($amountAtomic);
        $platformFee = intdiv($amount * $feeBps, 10_000);
        $creatorAmount = $amount - $platformFee;

        return [
            'platform_fee_atomic' => (string) $platformFee,
            'creator_amount_atomic' => (string) $creatorAmount,
            'platform_fee' => $this->atomicToDecimal($platformFee),
            'creator_amount' => $this->atomicToDecimal($creatorAmount),
        ];
    }

    public function compareAtomic(string|int $left, string|int $right): int
    {
        $left = $this->normalizeAtomic($left);
        $right = $this->normalizeAtomic($right);

        if (strlen($left) !== strlen($right)) {
            return strlen($left) <=> strlen($right);
        }

        return $left <=> $right;
    }

    private function normalizeAtomic(string|int $atomic): string
    {
        $atomic = trim((string) $atomic);

        if (!preg_match('/^\d+$/', $atomic)) {
            throw new InvalidArgumentException('Atomic amount must be an unsigned integer.');
        }

        return ltrim($atomic, '0') ?: '0';
    }
}
