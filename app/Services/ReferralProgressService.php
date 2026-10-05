<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ReviewStatus;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferralProgressService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $settings = PlatformSetting::current();
        $rules = $this->rules($settings);
        $ids = $user->referrals()->pluck('id');
        $referred = $ids->count();
        $active = $this->activeCount($user);
        $deposits = $ids->isEmpty()
            ? '0.00'
            : Money::of(Deposit::query()->whereIn('user_id', $ids)->where('status', ReviewStatus::Approved)->sum('amount'));
        $investments = $ids->isEmpty()
            ? '0.00'
            : Money::of(Investment::query()->whereIn('user_id', $ids)->sum('amount'));
        $commissions = Money::of(ReferralCommission::query()->where('referrer_id', $user->id)->sum('amount'));
        $level = $this->currentLevel($rules, $active);
        $next = $this->nextLevel($rules, $level);
        $target = $next['min_active'] ?? $level['min_active'];
        $remaining = $next === null ? 0 : max(0, $next['min_active'] - $active);
        $progress = $next === null || $target <= 0
            ? 100
            : (int) min(100, floor(($active / $target) * 100));

        return [
            'referred' => $referred,
            'active' => $active,
            'inactive' => max(0, $referred - $active),
            'deposits' => $deposits,
            'investments' => $investments,
            'commissions' => $commissions,
            'rate' => (string) $settings->referral_rate_percent,
            'code' => $user->referral_code,
            'link' => filled($user->referral_code) ? url('/register?ref='.$user->referral_code) : null,
            'level' => $level,
            'next' => $next,
            'remaining' => $remaining,
            'target' => $target,
            'progress' => $progress,
            'benefits' => config('zelvora.level_benefits.'.$level['key'], []),
            'investments_count' => $user->investments()->count(),
            'active_investments' => $user->investments()->where('status', 'active')->count(),
        ];
    }

    public function members(User $user): LengthAwarePaginator
    {
        return $user->referrals()
            ->withSum(['deposits as approved_deposits' => fn ($query) => $query->where('status', ReviewStatus::Approved)], 'amount')
            ->withSum('investments as invested_total', 'amount')
            ->withSum(['commissionsAsReferred as commission_total' => fn ($query) => $query->where('referrer_id', $user->id)], 'amount')
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function activeCount(User $user): int
    {
        return $user->referrals()
            ->where('status', AccountStatus::Active)
            ->whereHas('deposits', fn ($query) => $query->where('status', ReviewStatus::Approved))
            ->count();
    }

    /**
     * @return list<array{key: string, name: string, min_active: int}>
     */
    public function rules(?PlatformSetting $settings = null): array
    {
        $settings ??= PlatformSetting::current();
        $stored = $settings->referral_levels;
        $source = is_array($stored) && $stored !== [] ? $stored : config('zelvora.levels');
        $rules = [];

        foreach ($source as $rule) {
            if (! is_array($rule) || ! isset($rule['key'], $rule['name'], $rule['min_active'])) {
                continue;
            }
            $rules[] = [
                'key' => (string) $rule['key'],
                'name' => (string) $rule['name'],
                'min_active' => (int) $rule['min_active'],
            ];
        }

        if ($rules === []) {
            $rules = config('zelvora.levels');
        }

        usort($rules, fn (array $left, array $right) => $left['min_active'] <=> $right['min_active']);

        return $rules;
    }

    /**
     * @param  list<array{key: string, name: string, min_active: int}>  $rules
     * @return array{key: string, name: string, min_active: int}
     */
    public function currentLevel(array $rules, int $active): array
    {
        $current = $rules[0];
        foreach ($rules as $rule) {
            if ($active >= $rule['min_active']) {
                $current = $rule;
            }
        }

        return $current;
    }

    /**
     * @param  list<array{key: string, name: string, min_active: int}>  $rules
     * @param  array{key: string, name: string, min_active: int}  $level
     * @return array{key: string, name: string, min_active: int}|null
     */
    public function nextLevel(array $rules, array $level): ?array
    {
        foreach ($rules as $rule) {
            if ($rule['min_active'] > $level['min_active']) {
                return $rule;
            }
        }

        return null;
    }
}
