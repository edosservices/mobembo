<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slogan')->nullable()->after('name');
            $table->decimal('max_investment', 18, 2)->nullable()->after('min_investment');
            $table->boolean('is_active')->default(true)->after('status');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('planned_return', 18, 2)->nullable()->after('duration_days');
            $table->unsignedInteger('profit_days')->nullable()->after('planned_return');
            $table->decimal('daily_return', 18, 2)->nullable()->after('profit_days');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['slogan', 'max_investment', 'is_active']);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['planned_return', 'profit_days', 'daily_return']);
        });
    }
};
