<?php

namespace App\Models;

use App\Enums\ReferralTrigger;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'withdrawal_fee_percent' => 'decimal:4',
            'withdrawal_fee_fixed' => 'decimal:2',
            'withdrawal_min' => 'decimal:2',
            'withdrawal_max' => 'decimal:2',
            'referral_enabled' => 'boolean',
            'referral_trigger' => ReferralTrigger::class,
            'referral_rate_percent' => 'decimal:4',
            'otp_enabled' => 'boolean',
            'kyc_required_for_withdrawal' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'currency' => 'USD',
            'currency_symbol' => '$',
            'withdrawal_fee_percent' => '5.0000',
            'withdrawal_fee_fixed' => '0.00',
            'withdrawal_min' => '5.00',
            'withdrawal_max' => '10000.00',
            'referral_enabled' => true,
            'referral_trigger' => ReferralTrigger::ApprovedDeposit->value,
            'referral_rate_percent' => '2.0000',
            'otp_enabled' => false,
            'kyc_required_for_withdrawal' => false,
            'legal_disclaimer' => 'ZELVORA présente des rendements prévus, estimés et non garantis. Aucun revenu n’est crédité tant qu’une distribution réelle, rattachée aux conditions du projet, n’a pas été enregistrée. La plateforme ne doit pas être ouverte au public avant mise en conformité juridique, KYC et agréments financiers applicables en RDC.',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create(static::defaults());
    }
}
