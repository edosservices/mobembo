<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\LedgerType;
use App\Enums\ReferralTrigger;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Support\Money;

class ReferralService
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit,
        private Notifier $notifier,
    ) {}

    public function reward(ReferralTrigger $trigger, User $referred, string $baseAmount, string $sourceType, int $sourceId, ?User $admin = null): ?ReferralCommission
    {
        $settings = PlatformSetting::current();

        if (! $settings->referral_enabled || $settings->referral_trigger !== $trigger) {
            return null;
        }

        if (! $referred->referred_by_id) {
            return null;
        }

        $referrer = User::query()->find($referred->referred_by_id);

        if (! $referrer || $referrer->status !== AccountStatus::Active) {
            return null;
        }

        if (ReferralCommission::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('referrer_id', $referrer->id)
            ->exists()) {
            return null;
        }

        $amount = Money::percent($baseAmount, $settings->referral_rate_percent);

        if (Money::cmp($amount, '0') <= 0) {
            return null;
        }

        $wallet = $this->wallets->ensure($referrer);
        $old = Money::of($wallet->available_balance);
        $entry = $this->wallets->credit($referrer, $amount, LedgerType::ReferralCommission, [
            'description' => 'Commission de parrainage — '.$referred->name,
            'reference' => 'REF-'.$sourceId,
            'related_type' => $sourceType,
            'related_id' => $sourceId,
            'idempotency_key' => 'referral-'.$referrer->id.'-'.$sourceType.'-'.$sourceId,
            'created_by' => $admin?->id,
            'metadata' => [
                'referred_user_id' => $referred->id,
                'trigger' => $trigger->value,
                'base_amount' => Money::of($baseAmount),
            ],
        ]);

        $new = Money::of($wallet->refresh()->available_balance);

        $commission = ReferralCommission::query()->create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'trigger' => $trigger,
            'rate_percent' => $settings->referral_rate_percent,
            'base_amount' => Money::of($baseAmount),
            'amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'ledger_entry_id' => $entry->id,
        ]);

        $this->audit->record(
            $admin,
            $referrer,
            'referral_commission',
            $old,
            $new,
            $amount,
            'Commission liée à une opération réelle du filleul '.$referred->name,
            ['referred_user_id' => $referred->id, 'source_type' => $sourceType, 'source_id' => $sourceId],
        );

        $this->notifier->send(
            $referrer,
            'commission_received',
            'Commission de parrainage',
            'Vous avez reçu '.Money::format($amount).' de commission. L’inscription seule ne génère aucun revenu.',
        );

        return $commission;
    }
}
