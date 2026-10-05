<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('currency', 8)->default('USD');
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('locked_balance', 18, 2)->default(0);
            $table->decimal('invested_balance', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 8)->default('USD');
            $table->string('currency_symbol', 8)->default('$');
            $table->decimal('withdrawal_fee_percent', 8, 4)->default(5);
            $table->decimal('withdrawal_fee_fixed', 18, 2)->default(0);
            $table->decimal('withdrawal_min', 18, 2)->default(5);
            $table->decimal('withdrawal_max', 18, 2)->default(10000);
            $table->boolean('referral_enabled')->default(true);
            $table->string('referral_trigger', 40)->default('approved_deposit');
            $table->decimal('referral_rate_percent', 8, 4)->default(2);
            $table->boolean('otp_enabled')->default(false);
            $table->boolean('kyc_required_for_withdrawal')->default(false);
            $table->text('legal_disclaimer')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_path')->nullable();
            $table->text('description');
            $table->string('location');
            $table->string('category');
            $table->string('currency', 8)->default('USD');
            $table->decimal('target_amount', 18, 2);
            $table->decimal('funded_amount', 18, 2)->default(0);
            $table->decimal('min_investment', 18, 2);
            $table->unsignedInteger('duration_days');
            $table->decimal('expected_return_percent', 8, 4);
            $table->string('distribution_frequency', 30)->default('at_maturity');
            $table->date('next_distribution_on')->nullable();
            $table->text('economic_terms');
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_demo')->default(false);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 8)->default('USD');
            $table->decimal('expected_return_percent', 8, 4);
            $table->unsignedInteger('duration_days');
            $table->decimal('returns_credited', 18, 2)->default(0);
            $table->decimal('capital_returned', 18, 2)->default(0);
            $table->timestamp('invested_at');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status', 20)->default('active')->index();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('method', 30);
            $table->string('reference', 64);
            $table->string('reference_lock')->nullable()->unique();
            $table->string('proof_path');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('idempotency_key')->unique();
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('fee', 18, 2);
            $table->decimal('net_amount', 18, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('method', 30);
            $table->string('phone', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('type', 40)->index();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 20)->index();
            $table->string('reference')->nullable();
            $table->string('description');
            $table->decimal('balance_after', 18, 2)->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->restrictOnDelete();
            $table->string('trigger', 40);
            $table->decimal('rate_percent', 8, 4);
            $table->decimal('base_amount', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'referrer_id'], 'referral_commission_once');
            $table->index(['referrer_id', 'created_at']);
        });

        Schema::create('project_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_amount', 18, 2);
            $table->text('reason');
            $table->timestamp('distributed_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->decimal('old_amount', 18, 2)->nullable();
            $table->decimal('new_amount', 18, 2)->nullable();
            $table->decimal('delta_amount', 18, 2)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['admin_id', 'created_at']);
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->string('path');
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 30);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verification_codes');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('project_distributions');
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('wallets');
    }
};
