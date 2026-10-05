<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Models\PaymentDestination;
use App\Models\PlatformSetting;

class PaymentDirectory
{
    /**
     * @return array<string, list<array{name: string, phone: string}>>
     */
    public static function grouped(): array
    {
        $settings = PlatformSetting::current();
        $groups = [];

        foreach (PaymentMethod::cases() as $method) {
            $groups[$method->value] = [];
        }

        $builtin = [
            PaymentMethod::Mpesa->value => [$settings->mpesa_holder, $settings->mpesa_number],
            PaymentMethod::AirtelMoney->value => [$settings->airtel_holder, $settings->airtel_number],
            PaymentMethod::OrangeMoney->value => [$settings->orange_holder, $settings->orange_number],
        ];

        foreach ($builtin as $method => [$name, $phone]) {
            if (filled($phone)) {
                $groups[$method][] = [
                    'name' => filled($name) ? (string) $name : '',
                    'phone' => (string) $phone,
                ];
            }
        }

        PaymentDestination::query()
            ->where('active', true)
            ->orderBy('id')
            ->each(function (PaymentDestination $destination) use (&$groups): void {
                $groups[$destination->method->value][] = [
                    'name' => $destination->holder_name,
                    'phone' => $destination->phone,
                ];
            });

        return $groups;
    }
}
