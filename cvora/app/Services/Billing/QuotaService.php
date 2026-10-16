<?php

namespace App\Services\Billing;

use App\Enums\SubscriptionStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\AiUsageLog;
use App\Models\Plan;
use App\Models\ResumeTemplate;
use App\Models\Subscription;
use App\Models\User;

class QuotaService
{
    /**
     * @return array<string, mixed>
     */
    public function limits(User $user): array
    {
        $subscription = $this->currentSubscription($user);
        $planLimits = $subscription?->plan?->limits();

        if (! is_array($planLimits) || $planLimits === []) {
            return $this->fallbackLimits();
        }

        return $planLimits;
    }

    public function currentSubscription(User $user): ?Subscription
    {
        $subscription = $user->subscriptions()
            ->with('plan')
            ->where('status', SubscriptionStatus::Active)
            ->latest('id')
            ->first();

        if ($subscription === null || ! $subscription->isCurrent()) {
            return null;
        }

        return $subscription;
    }

    public function remainingCredits(User $user): int
    {
        $limits = $this->limits($user);
        $quota = (int) ($limits['ai_credits_monthly'] ?? 0);
        $bonus = (int) ($this->currentSubscription($user)?->bonus_credits ?? 0);
        $used = (int) AiUsageLog::query()
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('credits');

        return max(0, $quota + $bonus - $used);
    }

    public function assert(User $user, string $ability, mixed $context = null): void
    {
        $limits = $this->limits($user);

        $allowed = match ($ability) {
            'create_resume' => $this->withinCount($user->resumes()->count(), $limits['resumes'] ?? 1),
            'create_letter' => $this->withinCount($user->coverLetters()->count(), $limits['cover_letters'] ?? 0),
            'create_application' => $this->withinCount($user->applications()->count(), $limits['applications'] ?? 1),
            'import' => (bool) ($limits['import'] ?? false),
            'versions' => (bool) ($limits['versions'] ?? false),
            'adapt' => (bool) ($limits['job_adapt'] ?? false),
            'export' => in_array($context, $limits['exports'] ?? [], true),
            'template' => $this->templateAllowed($limits, $context),
            'ai' => $this->remainingCredits($user) > 0,
            default => false,
        };

        if ($allowed) {
            return;
        }

        throw new QuotaExceededException($this->message($ability));
    }

    public function templateAllowedFor(User $user, ResumeTemplate $template): bool
    {
        if (! $template->is_active) {
            return false;
        }

        return $this->templateAllowed($this->limits($user), $template->slug);
    }

    /**
     * @param  array<string, mixed>  $limits
     */
    private function templateAllowed(array $limits, mixed $slug): bool
    {
        if (! is_string($slug) || $slug === '') {
            return false;
        }

        $allowed = $limits['templates'] ?? [];

        if ($allowed === '*') {
            return true;
        }

        return is_array($allowed) && in_array($slug, $allowed, true);
    }

    private function withinCount(int $current, mixed $max): bool
    {
        if ($max === null || $max === '*') {
            return true;
        }

        return $current < (int) $max;
    }

    private function message(string $ability): string
    {
        return match ($ability) {
            'create_resume' => 'Vous ne pouvez pas créer davantage de CV avec votre offre actuelle.',
            'create_letter' => 'Les lettres de motivation ne sont pas incluses dans votre offre actuelle.',
            'create_application' => 'Vous avez atteint le nombre de candidatures prévu par votre offre.',
            'import' => 'L’importation de CV n’est pas incluse dans votre offre actuelle.',
            'versions' => 'L’historique des versions est réservé à une offre supérieure.',
            'adapt' => 'L’adaptation du CV à une offre est réservée à l’offre Pro.',
            'export' => 'Cette exportation n’est pas incluse dans votre offre actuelle.',
            'template' => 'Ce modèle n’est pas inclus dans votre offre actuelle.',
            'ai' => 'Vous avez atteint le quota d’intelligence artificielle de votre offre pour ce mois.',
            default => 'Cette action n’est pas disponible avec votre offre actuelle.',
        };
    }

    /**
     * Filet de sécurité lorsqu’aucune offre n’est en base. Ce n’est pas un tarif.
     *
     * @return array<string, mixed>
     */
    private function fallbackLimits(): array
    {
        return [
            'resumes' => 1,
            'cover_letters' => 0,
            'ai_credits_monthly' => 0,
            'templates' => ['moderne'],
            'exports' => [],
            'import' => false,
            'versions' => false,
            'job_adapt' => false,
            'applications' => 1,
        ];
    }

    public function planName(User $user): string
    {
        return $this->currentSubscription($user)?->plan?->name ?? 'Aucune offre';
    }

    public function findPlan(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->where('is_active', true)->first();
    }
}
