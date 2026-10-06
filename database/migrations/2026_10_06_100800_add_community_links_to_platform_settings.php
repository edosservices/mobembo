<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_settings', 'whatsapp_url')) {
                $table->string('whatsapp_url')->nullable();
            }
            if (! Schema::hasColumn('platform_settings', 'telegram_url')) {
                $table->string('telegram_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['whatsapp_url', 'telegram_url'],
                fn (string $column) => Schema::hasColumn('platform_settings', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
