<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_letter_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cover_letter_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('source')->default('manual');
            $table->longText('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letter_versions');
    }
};
