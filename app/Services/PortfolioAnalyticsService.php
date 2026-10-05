<?php

namespace App\Services;

use App\Enums\LedgerStatus;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money;

class PortfolioAnalyticsService
{
    /**
     * @return array{empty: bool, range: string, polyline: string, dots: list<array{x: float, y: float}>, labels: list<string>, min: string, max: string}
     */
    public function series(User $user, string $range): array
    {
        $range = in_array($range, ['7d', '30d', '90d', '1y'], true) ? $range : '7d';
        $days = match ($range) {
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 7,
        };
        $from = now()->startOfDay()->subDays($days - 1);

        $entries = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [LedgerStatus::Completed, LedgerStatus::Pending])
            ->where('created_at', '>=', $from)
            ->whereNotNull('balance_after')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['created_at', 'balance_after']);

        if ($entries->isEmpty()) {
            return [
                'empty' => true,
                'range' => $range,
                'polyline' => '',
                'dots' => [],
                'labels' => [],
                'min' => '0.00',
                'max' => '0.00',
            ];
        }

        $byDay = [];
        foreach ($entries as $entry) {
            $byDay[$entry->created_at->toDateString()] = Money::of($entry->balance_after);
        }

        $values = array_values($byDay);
        $min = $values[0];
        $max = $values[0];
        foreach ($values as $value) {
            if (Money::cmp($value, $min) < 0) {
                $min = $value;
            }
            if (Money::cmp($value, $max) > 0) {
                $max = $value;
            }
        }

        $span = Money::sub($max, $min);
        $count = count($values);
        $dots = [];
        $labels = [];
        $index = 0;
        foreach ($byDay as $date => $value) {
            $x = $count === 1 ? 160 : ($index / ($count - 1)) * 320;
            $ratio = Money::cmp($span, '0') === 0 ? 0.5 : (float) bcdiv(Money::sub($value, $min), $span, 6);
            $y = 100 - ($ratio * 80);
            $dots[] = ['x' => round($x, 2), 'y' => round($y, 2)];
            $labels[] = \Illuminate\Support\Carbon::parse($date)->format('d/m');
            $index++;
        }

        $polyline = implode(' ', array_map(fn (array $dot) => $dot['x'].','.$dot['y'], $dots));

        return [
            'empty' => false,
            'range' => $range,
            'polyline' => $polyline,
            'dots' => $dots,
            'labels' => $labels,
            'min' => $min,
            'max' => $max,
        ];
    }
}
