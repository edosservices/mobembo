<?php

namespace App\Console\Commands;

use App\Services\WalletService;
use Illuminate\Console\Command;

class ReconcileWallets extends Command
{
    protected $signature = 'zelvora:reconcile';

    protected $description = 'Compare chaque solde disponible avec la somme du ledger';

    public function handle(WalletService $wallets): int
    {
        $drift = $wallets->findDrift();

        if ($drift === []) {
            $this->info('Tous les portefeuilles correspondent au ledger.');

            return self::SUCCESS;
        }

        foreach ($drift as $row) {
            $this->error("Utilisateur {$row['user_id']} : portefeuille {$row['wallet']} / ledger {$row['ledger']}");
        }

        return self::FAILURE;
    }
}
