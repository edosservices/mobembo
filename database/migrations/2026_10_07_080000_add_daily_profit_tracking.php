<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_profits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->date('profit_date');
            $table->decimal('amount', 18, 2);
            $table->string('type', 40)->default('daily_profit');
            $table->string('status', 20)->default('credited');
            $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['investment_id', 'profit_date']);
            $table->index(['user_id', 'profit_date']);
            $table->index(['profit_date', 'status']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500);
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 40)->default('aesgcm');
            $table->timestamps();

            $table->unique('endpoint');
            $table->index('user_id');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->index(['status', 'ends_at']);
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'status']);
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')->update([
                'withdrawal_min' => 3.50,
                'withdrawal_fee_percent' => 12,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('investment_profits');
    }
};
