<?php

namespace App\Providers;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use App\Support\ReferralCode;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(((string) $request->input('phone')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $view->with('navUnread', $user ? $user->unreadNotifications()->count() : 0);
        });

        User::creating(function (User $user) {
            if (! $user->referral_code) {
                $user->referral_code = ReferralCode::generate();
            }

            $user->role ??= UserRole::User;
            $user->status ??= AccountStatus::Active;
            $user->kyc_status ??= KycStatus::NotSubmitted;
        });

        User::created(function (User $user) {
            Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => 'USD'],
            );
        });
    }
}
