<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('job_title')->nullable();
            $table->string('company')->nullable();
            $table->text('offer_excerpt')->nullable();
            $table->string('tone')->default('professionnel');
            $table->string('length')->default('moyenne');
            $table->longText('content');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};
