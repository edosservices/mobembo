<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        foreach (DB::table('projects')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('projects')->where('id', $id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        }

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('mpesa_number', 32)->nullable()->after('legal_disclaimer');
            $table->string('airtel_number', 32)->nullable()->after('mpesa_number');
            $table->string('orange_number', 32)->nullable()->after('airtel_number');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['mpesa_number', 'airtel_number', 'orange_number']);
        });
    }
};
