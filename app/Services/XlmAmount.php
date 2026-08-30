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

        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,7}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('Enter a valid XLM amount with up to 7 decimal places.');
        }

        $whole = $matches[1];
        $fraction = str_pad($matches[2] ?? '', self::DECIMALS, '0');
        $atomic = $this->addAtomic($this->multiplySmall($whole, self::SCALE), ltrim($fraction, '0') ?: '0');

        if ($this->compareAtomic($atomic, '0') <= 0) {
            throw new InvalidArgumentException('Amount too small.');
        }

        return $atomic;
    }

    public function atomicToDecimal(string|int $atomic): string
    {
        $atomic = $this->normalizeAtomic($atomic);
        [$whole, $remainder] = $this->divideSmallWithRemainder($atomic, self::SCALE);
        $fraction = str_pad((string) $remainder, self::DECIMALS, '0', STR_PAD_LEFT);

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
        if ($feeBps < 0 || $feeBps > 10_000) {
            throw new InvalidArgumentException('Fee basis points must be between 0 and 10000.');
        }

        $amount = $this->normalizeAtomic($amountAtomic);
        $platformFee = $this->divideSmall($this->multiplySmall($amount, $feeBps), 10_000);
        $creatorAmount = $this->subtractAtomic($amount, $platformFee);

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

        if (! preg_match('/^\d+$/', $atomic)) {
            throw new InvalidArgumentException('Atomic amount must be an unsigned integer.');
        }

        return ltrim($atomic, '0') ?: '0';
    }

    private function multiplySmall(string $atomic, int $multiplier): string
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier must be non-negative.');
        }

        $atomic = $this->normalizeAtomic($atomic);
        if ($atomic === '0' || $multiplier === 0) {
            return '0';
        }

        $carry = 0;
        $result = '';
        for ($i = strlen($atomic) - 1; $i >= 0; $i--) {
            $product = ((int) $atomic[$i] * $multiplier) + $carry;
            $result = (string) ($product % 10).$result;
            $carry = intdiv($product, 10);
        }

        if ($carry > 0) {
            $result = (string) $carry.$result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function divideSmall(string $atomic, int $divisor): string
    {
        return $this->divideSmallWithRemainder($atomic, $divisor)[0];
    }

    private function divideSmallWithRemainder(string $atomic, int $divisor): array
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor must be positive.');
        }

        $atomic = $this->normalizeAtomic($atomic);
        $remainder = 0;
        $result = '';
        for ($i = 0, $length = strlen($atomic); $i < $length; $i++) {
            $value = ($remainder * 10) + (int) $atomic[$i];
            $result .= (string) intdiv($value, $divisor);
            $remainder = $value % $divisor;
        }

        return [ltrim($result, '0') ?: '0', $remainder];
    }

    private function addAtomic(string $left, string $right): string
    {
        $left = $this->normalizeAtomic($left);
        $right = $this->normalizeAtomic($right);
        $carry = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $sum = $carry;
            if ($leftIndex >= 0) {
                $sum += (int) $left[$leftIndex--];
            }
            if ($rightIndex >= 0) {
                $sum += (int) $right[$rightIndex--];
            }

            $result = (string) ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return ltrim($result, '0') ?: '0';
    }

    private function subtractAtomic(string $left, string $right): string
    {
        $left = $this->normalizeAtomic($left);
        $right = $this->normalizeAtomic($right);

        if ($this->compareAtomic($left, $right) < 0) {
            throw new InvalidArgumentException('Atomic subtraction cannot produce a negative value.');
        }

        $borrow = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0) {
            $digit = (int) $left[$leftIndex--] - $borrow;
            $subtrahend = $rightIndex >= 0 ? (int) $right[$rightIndex--] : 0;
            if ($digit < $subtrahend) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result = (string) ($digit - $subtrahend).$result;
        }

        return ltrim($result, '0') ?: '0';
    }
}
