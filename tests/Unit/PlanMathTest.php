<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\PlanMath;
use PHPUnit\Framework\TestCase;

class PlanMathTest extends TestCase
{
    public function test_repeating_divisions_sum_to_the_exact_total(): void
    {
        $cases = [
            '30.00' => 11,
            '100.00' => 11,
            '500.00' => 21,
            '10.00' => 7,
            '123.45' => 13,
        ];

        foreach ($cases as $total => $days) {
            $schedule = PlanMath::schedule($total, $days);
            $sum = '0.00';

            foreach ($schedule as $amount) {
                $sum = Money::add($sum, $amount);
            }

            $this->assertCount($days, $schedule);
            $this->assertSame(Money::of($total), $sum, $total.' / '.$days);
        }
    }

    public function test_eleven_day_schedule_rounds_up_then_adjusts_the_last_day(): void
    {
        $schedule = PlanMath::schedule('30.00', 11);

        $this->assertSame('2.73', $schedule[0]);
        $this->assertSame('2.73', $schedule[9]);
        $this->assertSame('2.70', $schedule[10]);
        $this->assertSame('30.00', array_reduce($schedule, fn (string $sum, string $amount) => Money::add($sum, $amount), '0.00'));
    }

    public function test_total_gain_uses_the_plan_percent_once(): void
    {
        $this->assertSame('30.00', PlanMath::totalGain('100.00', '30'));
        $this->assertSame('100.00', PlanMath::totalGain('500.00', '20'));
        $this->assertSame('130.00', PlanMath::economicTotal('100.00', '30.00'));
        $this->assertSame('600.00', PlanMath::economicTotal('500.00', '100.00'));
    }
}
