<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\InvestmentStatus;
use App\Enums\ProjectStatus;
use App\Enums\ReferralTrigger;
use App\Exceptions\FinancialException;
use App\Models\Investment;
use App\Models\Project;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

class InvestmentService
{
    public function __construct(
        private WalletService $wallets,
        private ReferralService $referrals,
        private Notifier $notifier,
        private AuditService $audit,
    ) {}

    public function invest(User $user, Project $project, string $amount, string $idempotencyKey): Investment
    {
        if ($user->status !== AccountStatus::Active) {
            throw new FinancialException('Ce compte est bloqué.');
        }

        $amount = Money::of($amount);

        return Finance::run(function () use ($user, $project, $amount, $idempotencyKey) {
            $existing = Investment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ($existing->user_id !== $user->id) {
                    throw new FinancialException('Cette opération a déjà été enregistrée pour un autre compte.');
                }

                return $existing;
            }

            $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();

            if (! $project->status->acceptsInvestment()) {
                throw new FinancialException('Ce projet n’accepte pas de nouvel investissement.');
            }

            if (Money::cmp($amount, $project->min_investment) < 0) {
                throw new FinancialException('L’investissement minimum est de '.Money::format($project->min_investment).'.');
            }

            $remaining = $project->remainingAmount();

            if (Money::cmp($amount, $remaining) > 0) {
                throw new FinancialException('Il reste '.Money::format($remaining).' à financer sur ce projet.');
            }

            [$starts, $ends] = $this->term($project);

            $investment = Investment::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'project_id' => $project->id,
                'amount' => $amount,
                'currency' => $project->currency,
                'expected_return_percent' => $project->expected_return_percent,
                'duration_days' => $project->duration_days,
                'returns_credited' => '0.00',
                'capital_returned' => '0.00',
                'invested_at' => now(),
                'starts_at' => $starts->toDateString(),
                'ends_at' => $ends->toDateString(),
                'status' => InvestmentStatus::Active,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->wallets->invest($user, $amount, [
                'description' => 'Investissement — '.$project->name,
                'reference' => 'INV-'.$investment->id,
                'related_type' => Investment::class,
                'related_id' => $investment->id,
                'idempotency_key' => 'investment-'.$investment->id,
            ]);

            $funded = Money::add($project->funded_amount, $amount);
            $status = $project->status;

            if (Money::cmp($funded, $project->target_amount) >= 0) {
                $status = ProjectStatus::Funded;
            } elseif ($project->status->acceptsInvestment() && Money::cmp($project->target_amount, '0') > 0) {
                $percent = bcdiv(bcmul($funded, '100', 8), (string) $project->target_amount, 2);

                if (Money::cmp($percent, '80') >= 0) {
                    $status = ProjectStatus::AlmostComplete;
                }
            }

            $project->forceFill([
                'funded_amount' => $funded,
                'status' => $status,
            ])->save();

            $this->referrals->reward(ReferralTrigger::Investment, $user, $amount, Investment::class, $investment->id);

            $this->notifier->send(
                $user,
                'investment_created',
                'Investissement enregistré',
                'Vous avez investi '.Money::format($amount).' dans '.$project->name.'. Le rendement affiché est une estimation, il n’est pas encore crédité.',
            );

            return $investment->load('project');
        });
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    public function term(Project $project): array
    {
        $starts = $project->starts_at && $project->starts_at->isFuture()
            ? $project->starts_at->copy()
            : now()->startOfDay();

        return [$starts, $starts->copy()->addDays((int) $project->duration_days)];
    }

    public function setStatus(Investment $investment, InvestmentStatus $status, User $admin, string $reason): Investment
    {
        if (! in_array($status, [InvestmentStatus::Suspended, InvestmentStatus::Active, InvestmentStatus::Cancelled], true)) {
            throw new FinancialException('Statut d’investissement non autorisé.');
        }

        return Finance::run(function () use ($investment, $status, $admin, $reason) {
            $investment = Investment::query()->whereKey($investment->id)->lockForUpdate()->firstOrFail();

            if ($investment->status === InvestmentStatus::Completed || $investment->status === InvestmentStatus::Cancelled) {
                throw new FinancialException('Cet investissement est déjà clos.');
            }

            if ($status === InvestmentStatus::Active && $investment->status !== InvestmentStatus::Suspended) {
                throw new FinancialException('Seul un investissement suspendu peut être réactivé.');
            }

            if ($status === InvestmentStatus::Cancelled) {
                $this->returnPrincipal($investment, $admin, $reason);
                $investment->refresh();
                $investment->status = InvestmentStatus::Cancelled;
            } else {
                $investment->status = $status;
            }

            $investment->save();

            $this->audit->record($admin, $investment->user, 'investment_'.$status->value, null, null, null, $reason, [
                'investment_id' => $investment->id,
            ]);

            return $investment;
        });
    }

    public function closeProject(Project $project, User $admin, string $reason): void
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new FinancialException('Indiquez le motif de clôture.');
        }

        Finance::run(function () use ($project, $admin, $reason) {
            $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $investments = Investment::query()
                ->where('project_id', $project->id)
                ->whereIn('status', [InvestmentStatus::Active, InvestmentStatus::Suspended])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($investments as $investment) {
                $this->returnPrincipal($investment, $admin, $reason);
                $investment->refresh();
                $investment->forceFill(['status' => InvestmentStatus::Completed])->save();
                $this->notifier->send(
                    $investment->user,
                    'capital_returned',
                    'Capital restitué',
                    'Le capital de votre investissement dans '.$project->name.' a été rendu disponible.',
                );
            }

            $project->forceFill(['status' => ProjectStatus::Closed])->save();
            $this->audit->record($admin, null, 'project_closed', null, null, null, $reason, [
                'project_id' => $project->id,
            ]);
        });
    }

    public function returnPrincipal(Investment $investment, User $admin, string $reason): void
    {
        $investment->loadMissing(['user.wallet', 'project']);
        $remaining = Money::sub($investment->amount, $investment->capital_returned);

        if (Money::cmp($remaining, '0') <= 0) {
            return;
        }

        $user = $investment->user()->firstOrFail();
        $wallet = $this->wallets->ensure($user);
        $old = Money::of($wallet->available_balance);

        $this->wallets->returnCapital($user, $remaining, [
            'description' => 'Restitution du capital — '.$investment->project->name,
            'reference' => 'CAP-'.$investment->id,
            'related_type' => Investment::class,
            'related_id' => $investment->id,
            'idempotency_key' => 'capital-'.$investment->id,
            'created_by' => $admin->id,
        ]);

        $investment->forceFill([
            'capital_returned' => $investment->amount,
        ])->save();

        $wallet->refresh();

        $this->audit->record(
            $admin,
            $user,
            'capital_returned',
            $old,
            Money::of($wallet->available_balance),
            $remaining,
            $reason,
            ['investment_id' => $investment->id],
        );
    }
}
