<?php

namespace App\Enums;

enum LedgerType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Investment = 'investment';
    case InvestmentReturn = 'investment_return';
    case CapitalReturn = 'capital_return';
    case Bonus = 'bonus';
    case ReferralCommission = 'referral_commission';
    case WithdrawalFee = 'withdrawal_fee';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Dépôt',
            self::Withdrawal => 'Retrait',
            self::Investment => 'Investissement',
            self::InvestmentReturn => 'Revenu distribué',
            self::CapitalReturn => 'Restitution du capital',
            self::Bonus => 'Bonus',
            self::ReferralCommission => 'Commission de parrainage',
            self::WithdrawalFee => 'Frais de retrait',
            self::AdminAdjustment => 'Ajustement administrateur',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Deposit, self::InvestmentReturn, self::CapitalReturn, self::Bonus, self::ReferralCommission => 'ok',
            self::Withdrawal, self::Investment, self::WithdrawalFee => 'info',
            self::AdminAdjustment => 'warn',
        };
    }
}
