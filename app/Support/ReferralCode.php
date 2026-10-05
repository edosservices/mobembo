<?php

namespace App\Support;

use App\Models\User;

class ReferralCode
{
    public static function generate(): string
    {
        do {
            $code = 'ZLV'.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
