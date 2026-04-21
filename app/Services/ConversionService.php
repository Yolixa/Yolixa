<?php

namespace App\Services;

class ConversionService
{
    /**
     * Get the conversion rate for an asset to YLX.
     */
    public function getConversionRate(string $asset): float
    {
        if ($asset === 'YLX') {
            return 1.0;
        }
        
        $price = config("yolixa.ylx_price.{$asset}", null);
        
        if (!$price || $price <= 0) {
            throw new \Exception("Conversion price for {$asset} is not configured correctly.");
        }

        // Returning the amount of YLX you get per 1 unit of input asset
        return 1 / $price; 
    }

    /**
     * Convert an amount of given asset to YLX.
     */
    public function convertToYlx(string $asset, float $amount): array
    {
        $rate = $this->getConversionRate($asset);
        $converted = $amount * $rate;

        return [
            'rate' => $rate,
            'converted_amount' => $converted
        ];
    }

    /**
     * Calculates the platform fee in YLX and the net payout to the creator.
     */
    public function calculateFees(float $grossYlxAmount, string $asset = 'USDC'): array
    {
        if ($asset === 'YLX') {
             // 0% fee for YLX natively based on previous logic
             return [
                 'fee_percentage' => 0,
                 'fee_amount' => 0,
                 'net_payout' => $grossYlxAmount
             ];
        }

        $percentage = config('yolixa.fee_percentage', 0.05);
        $feeAmount = $grossYlxAmount * $percentage;
        $netPayout = $grossYlxAmount - $feeAmount;

        return [
            'fee_percentage' => $percentage,
            'fee_amount' => $feeAmount,
            'net_payout' => $netPayout
        ];
    }
}
