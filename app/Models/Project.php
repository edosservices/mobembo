<?php

namespace App\Models;

use App\Enums\DistributionFrequency;
use App\Enums\ProjectStatus;
use App\Support\BusinessCalendar;
use App\Support\InvestmentQuote;
use App\Support\Money;
use App\Support\PlanMath;
use App\Support\ReturnEstimator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'slogan',
    'slug',
    'image_path',
    'description',
    'location',
    'category',
    'currency',
    'target_amount',
    'funded_amount',
    'min_investment',
    'max_investment',
    'duration_days',
    'expected_return_percent',
    'distribution_frequency',
    'next_distribution_on',
    'economic_terms',
    'status',
    'is_active',
    'is_demo',
    'starts_at',
    'ends_at',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'funded_amount' => 'decimal:2',
            'min_investment' => 'decimal:2',
            'max_investment' => 'decimal:2',
            'expected_return_percent' => 'decimal:4',
            'distribution_frequency' => DistributionFrequency::class,
            'status' => ProjectStatus::class,
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'next_distribution_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (! $project->uuid) {
                $project->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query->whereIn('status', ProjectStatus::publicCases());
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(ProjectDistribution::class);
    }

    public function remainingAmount(): string
    {
        return Money::sub($this->target_amount, $this->funded_amount);
    }

    public function progressPercent(): string
    {
        if (Money::cmp($this->target_amount, '0') <= 0) {
            return '0.00';
        }

        $raw = bcdiv(bcmul((string) $this->funded_amount, '100', 8), (string) $this->target_amount, 8);

        return Money::of($raw);
    }

    public function dailyReturnPercent(): string
    {
        return ReturnEstimator::dailyPercent($this->expected_return_percent, (int) $this->duration_days);
    }

    public function quote(?string $amount = null): InvestmentQuote
    {
        return InvestmentQuote::for(
            $amount ?? $this->min_investment,
            $this->expected_return_percent,
            (int) $this->duration_days,
            $this->scheduledProfitDays(),
        );
    }

    public function exampleCapital(): string
    {
        $example = '100.00';

        if (Money::cmp($example, $this->min_investment) < 0) {
            $example = Money::of($this->min_investment);
        }

        if ($this->max_investment !== null && Money::cmp($example, $this->max_investment) > 0) {
            $example = Money::of($this->max_investment);
        }

        return $example;
    }

    public function cycleStart(): \Illuminate\Support\Carbon
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return $this->starts_at->copy()->startOfDay();
        }

        return now()->startOfDay();
    }

    public function cycleEnd(): \Illuminate\Support\Carbon
    {
        return $this->cycleStart()->copy()->addDays(max(1, (int) $this->duration_days));
    }

    public function scheduledProfitDays(): int
    {
        return BusinessCalendar::scheduledProfitDays($this->cycleStart(), $this->cycleEnd());
    }

    public function acceptsNewPosition(): bool
    {
        return $this->is_active !== false && $this->status->acceptsInvestment();
    }

    public function isInvestable(): bool
    {
        return $this->acceptsNewPosition() && Money::cmp($this->remainingAmount(), $this->min_investment) >= 0;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
