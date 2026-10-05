<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('mpesa_holder', 80)->nullable()->after('mpesa_number');
            $table->string('airtel_holder', 80)->nullable()->after('airtel_number');
            $table->string('orange_holder', 80)->nullable()->after('orange_number');
        });

        Schema::create('payment_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('method', 30);
            $table->string('holder_name', 80);
            $table->string('phone', 32);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_destinations');

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['mpesa_holder', 'airtel_holder', 'orange_holder']);
        });
    }
};
