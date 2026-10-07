<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->date('profit_effective_from')->nullable()->after('starts_at');
        });

        DB::table('investments')
            ->whereNull('profit_effective_from')
            ->update(['profit_effective_from' => now()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn('profit_effective_from');
        });
    }
};
