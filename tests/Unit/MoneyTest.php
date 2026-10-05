<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_rounds_half_up_and_formats_french_amounts(): void
    {
        $this->assertSame('10.25', Money::of('10.245'));
        $this->assertSame('10.24', Money::of('10.244'));
        $this->assertSame('5.00', Money::percent('100', '5'));
        $this->assertSame('100 000,00 $', Money::format('100000'));
    }

    public function test_allocation_sums_back_to_the_total(): void
    {
        $shares = Money::allocate('10.00', [1 => '1', 2 => '1', 3 => '1']);
        $this->assertSame('10.00', Money::add(Money::add($shares[1], $shares[2]), $shares[3]));

        $tiny = Money::allocate('0.05', [1 => '1', 2 => '1', 3 => '1', 4 => '1', 5 => '1', 6 => '1', 7 => '1', 8 => '1', 9 => '1', 10 => '1']);
        $sum = '0.00';
        foreach ($tiny as $share) {
            $sum = Money::add($sum, $share);
        }
        $this->assertSame('0.05', $sum);
    }
}
