<?php

namespace App\Services;

use App\Exceptions\FinancialException;
use App\Models\PhoneVerificationCode;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Point d'extension SMS. Aucun code n'est envoyé tant qu'un fournisseur n'est pas branché.
 */
class OtpService
{
    public function enabled(): bool
    {
        return (bool) PlatformSetting::current()->otp_enabled && filled(config('services.sms.driver'));
    }

    public function send(User $user, string $purpose): void
    {
        if (! PlatformSetting::current()->otp_enabled) {
            throw new FinancialException('La vérification SMS n’est pas activée.');
        }

        if (! filled(config('services.sms.driver'))) {
            throw new FinancialException('Aucun fournisseur SMS n’est configuré. Branchez SMS_DRIVER avant d’exiger l’OTP.');
        }

        $code = (string) random_int(100000, 999999);

        PhoneVerificationCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        // Le code en clair ne doit être remis qu'au fournisseur SMS, jamais au journal.
        throw new FinancialException('Le fournisseur SMS « '.config('services.sms.driver').' » n’est pas encore implémenté.');
    }
}
