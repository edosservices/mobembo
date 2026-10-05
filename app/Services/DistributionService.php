<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Exceptions\FinancialException;
use App\Models\Investment;
use App\Models\Project;
use App\Models\ProjectDistribution;
use App\Models\User;
use App\Support\Money;

/**
 * Seul point d'écriture des revenus d'investissement.
 * Le montant distribué est déclaré par un administrateur à partir d'un événement réel du projet.
 * Les estimations affichées ne passent jamais par cette classe automatiquement.
 */
class DistributionService
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit,
        private Notifier $notifier,
    ) {}

    public function distribute(Project $project, string $total, string $reason, User $admin): ProjectDistribution
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw new FinancialException('Décrivez l’origine économique de cette distribution (10 caractères minimum).');
        }

        $total = Money::of($total);

        if (Money::cmp($total, '0.01') < 0) {
            throw new FinancialException('Le montant à distribuer doit être positif.');
        }

        return Finance::run(function () use ($project, $total, $reason, $admin) {
            $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();

            if (in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Suspended], true)) {
                throw new FinancialException('Ce projet ne peut pas distribuer de revenus dans son statut actuel.');
            }

            $investments = Investment::query()
                ->where('project_id', $project->id)
                ->where('status', InvestmentStatus::Active)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($investments->isEmpty()) {
                throw new FinancialException('Aucun investissement actif à rémunérer.');
            }

            $weights = [];
            foreach ($investments as $investment) {
                $weights[$investment->id] = (string) $investment->amount;
            }

            $shares = Money::allocate($total, $weights);

            $distribution = ProjectDistribution::query()->create([
                'project_id' => $project->id,
                'admin_id' => $admin->id,
                'total_amount' => $total,
                'reason' => $reason,
                'distributed_at' => now(),
            ]);

            foreach ($investments as $investment) {
                $share = $shares[$investment->id] ?? '0.00';

                if (Money::cmp($share, '0') <= 0) {
                    continue;
                }

                $user = User::query()->findOrFail($investment->user_id);
                $old = Money::of($user->wallet->available_balance);

                $this->wallets->credit($user, $share, LedgerType::InvestmentReturn, [
                    'description' => 'Distribution réelle — '.$project->name,
                    'reference' => 'DIST-'.$distribution->id,
                    'related_type' => ProjectDistribution::class,
                    'related_id' => $distribution->id,
                    'idempotency_key' => 'distribution-'.$distribution->id.'-investment-'.$investment->id,
                    'created_by' => $admin->id,
                    'metadata' => [
                        'investment_id' => $investment->id,
                        'reason' => $reason,
                        'estimated' => false,
                    ],
                ]);

                $investment->forceFill([
                    'returns_credited' => Money::add($investment->returns_credited, $share),
                ])->save();

                $user->wallet->refresh();

                $this->audit->record(
                    $admin,
                    $user,
                    'investment_return',
                    $old,
                    Money::of($user->wallet->available_balance),
                    $share,
                    $reason,
                    ['project_id' => $project->id, 'distribution_id' => $distribution->id, 'investment_id' => $investment->id],
                );

                $this->notifier->send(
                    $user,
                    'return_credited',
                    'Revenu crédité',
                    Money::format($share).' ont été crédités pour '.$project->name.'. Motif : '.$reason,
                );
            }

            return $distribution;
        });
    }
}
