<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Exceptions\FinancialException;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money;
use App\Support\PhoneNumber;

class TransferService
{
    public const FEE_PERCENT = '2';

    public function __construct(
        private WalletService $wallets,
        private Notifier $notifier,
        private AuditService $audit,
    ) {}

    /**
     * @return array{amount: string, fee: string, total: string, received: string, percent: string}
     */
    public function quote(string $amount): array
    {
        $amount = Money::of($amount);

        if (Money::cmp($amount, '0') <= 0) {
            throw new FinancialException('Le montant à transférer doit être positif.');
        }

        $fee = Money::percent($amount, self::FEE_PERCENT);

        return [
            'amount' => $amount,
            'fee' => $fee,
            'total' => Money::add($amount, $fee),
            'received' => $amount,
            'percent' => Money::of(self::FEE_PERCENT),
        ];
    }

    public function resolveRecipient(User $sender, string $name, string $phone): User
    {
        $normalized = PhoneNumber::normalize($phone);

        if (! $normalized) {
            throw new FinancialException('Indiquez un numéro mobile RDC valide, par exemple 0812345678.');
        }

        $recipient = User::query()->where('phone', $normalized)->first();

        if (! $recipient) {
            throw new FinancialException('Aucun compte ZELVORA ne correspond à ce numéro.');
        }

        if (! $this->sameName($name, $recipient->name)) {
            throw new FinancialException('Le nom ne correspond pas à ce numéro.');
        }

        if ($recipient->id === $sender->id) {
            throw new FinancialException('Vous ne pouvez pas vous transférer de l\'argent.');
        }

        if ($recipient->status !== AccountStatus::Active) {
            throw new FinancialException('Ce compte ne peut pas recevoir de transfert.');
        }

        if ($sender->status !== AccountStatus::Active) {
            throw new FinancialException('Votre compte ne peut pas envoyer de transfert.');
        }

        return $recipient;
    }

    public function send(User $sender, string $name, string $phone, string $amount, string $idempotencyKey): LedgerEntry
    {
        return Finance::run(function () use ($sender, $name, $phone, $amount, $idempotencyKey) {
            return $this->perform($sender, $name, $phone, $amount, $idempotencyKey);
        });
    }

    private function perform(User $sender, string $name, string $phone, string $amount, string $idempotencyKey): LedgerEntry
    {
        $recipient = $this->resolveRecipient($sender, $name, $phone);
        $quote = $this->quote($amount);

        $entries = $this->wallets->transfer($sender, $recipient, $quote['amount'], $quote['fee'], [
            'idempotency_key' => $idempotencyKey,
            'out_description' => 'Transfert vers '.$recipient->name,
            'fee_description' => 'Frais de transfert '.self::FEE_PERCENT.' %',
            'in_description' => 'Transfert reçu de '.$sender->name,
            'metadata' => [
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->name,
                'recipient_phone' => $recipient->phone,
                'amount' => $quote['amount'],
                'fee' => $quote['fee'],
                'total' => $quote['total'],
            ],
        ]);

        if ($entries['replayed']) {
            return $entries['out'];
        }

        $reference = (string) $entries['out']->reference;
        $when = $entries['out']->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');

        $this->notifier->send(
            $sender,
            'transfer_sent',
            'Transfert effectué',
            'Transfert de '.Money::format($quote['amount']).' effectué vers '.$recipient->name.'. Frais '.Money::format($quote['fee']).'. Référence '.$reference.'.'.($when ? ' '.$when.'.' : ''),
            route('transactions.index', ['type' => 'transfer']),
        );

        $this->notifier->send(
            $recipient,
            'transfer_received',
            'Transfert reçu',
            'Vous avez reçu '.Money::format($quote['amount']).' de '.$sender->name.'. Référence '.$reference.'.'.($when ? ' '.$when.'.' : ''),
            route('transactions.index', ['type' => 'transfer']),
        );

        $senderWallet = $sender->wallet()->first();
        $this->audit->record(
            null,
            $sender,
            'transfer_sent',
            null,
            $senderWallet ? Money::of($senderWallet->available_balance) : null,
            Money::sub('0', $quote['total']),
            'Transfert '.$reference.' vers '.$recipient->name,
            [
                'reference' => $reference,
                'recipient_id' => $recipient->id,
                'amount' => $quote['amount'],
                'fee' => $quote['fee'],
            ],
        );

        return $entries['out'];
    }

    private function sameName(string $given, string $actual): bool
    {
        $normalize = function (string $name): string {
            $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

            return mb_strtolower($name);
        };

        return $normalize($given) !== '' && $normalize($given) === $normalize($actual);
    }
}
