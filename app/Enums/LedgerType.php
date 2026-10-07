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
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case TransferFee = 'transfer_fee';

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
            self::TransferOut => 'Transfert envoyé',
            self::TransferIn => 'Transfert reçu',
            self::TransferFee => 'Frais de transfert',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Deposit, self::InvestmentReturn, self::CapitalReturn, self::Bonus, self::ReferralCommission, self::TransferIn => 'ok',
            self::Withdrawal, self::Investment, self::WithdrawalFee, self::TransferOut, self::TransferFee => 'info',
            self::AdminAdjustment => 'warn',
        };
    }
}
