<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->resumeImports->each(function (ResumeImport $import): void {
                if ($import->path !== '') {
                    Storage::disk($import->disk ?: 'local')->delete($import->path);
                }
            });

            $user->paymentTransactions()->update(['payload' => null]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function resumeImports(): HasMany
    {
        return $this->hasMany(ResumeImport::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', SubscriptionStatus::Active)
            ->latestOfMany();
    }

    /**
     * @return array{percent: int, missing: list<string>}
     */
    public function progress(): array
    {
        $profile = $this->profile;
        $resume = $this->resumes()->latest('updated_at')->first();
        $content = is_array($resume?->content) ? $resume->content : [];
        $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];

        $checks = [
            'Votre nom' => filled($this->name),
            'Une ville' => filled($profile?->city) || filled($personal['city'] ?? null),
            'Un titre professionnel' => filled($profile?->headline) || filled($personal['title'] ?? null),
            'Un résumé' => filled($profile?->summary) || filled($content['summary'] ?? null),
            'Une expérience' => count($content['experiences'] ?? []) > 0,
            'Une formation' => count($content['education'] ?? []) > 0,
            'Au moins trois compétences' => count($content['skills'] ?? []) >= 3,
            'Un téléphone' => filled($this->phone) || filled($profile?->phone) || filled($personal['phone'] ?? null),
        ];

        $done = count(array_filter($checks));
        $missing = [];

        foreach ($checks as $label => $ok) {
            if (! $ok) {
                $missing[] = $label;
            }
        }

        return [
            'percent' => (int) round($done / count($checks) * 100),
            'missing' => $missing,
        ];
    }
}
