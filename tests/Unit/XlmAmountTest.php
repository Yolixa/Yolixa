<?php

namespace Tests\Unit;

use App\Services\XlmAmount;
use InvalidArgumentException;
use Tests\TestCase;

class XlmAmountTest extends TestCase
{
    public function test_decimal_amounts_convert_to_exact_atomic_units(): void
    {
        $amounts = new XlmAmount();

        $this->assertSame('10000000', $amounts->decimalToAtomic('1.0000000'));
        $this->assertSame('1', $amounts->decimalToAtomic('0.0000001'));
        $this->assertSame('12345678', $amounts->decimalToAtomic('1.2345678'));
        $this->assertSame('1.2345678', $amounts->atomicToDecimal('12345678'));
    }

    public function test_more_than_seven_decimal_places_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new XlmAmount())->decimalToAtomic('1.00000001');
    }

    public function test_fee_split_uses_integer_basis_point_math(): void
    {
        $split = (new XlmAmount())->splitFee('10000000', 150);

        $this->assertSame('150000', $split['platform_fee_atomic']);
        $this->assertSame('9850000', $split['creator_amount_atomic']);
        $this->assertSame('0.0150000', $split['platform_fee']);
        $this->assertSame('0.9850000', $split['creator_amount']);
    }
}
