<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('whatsapp_url')->nullable()->after('legal_disclaimer');
            $table->string('telegram_url')->nullable()->after('whatsapp_url');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_url', 'telegram_url']);
        });
    }
};
