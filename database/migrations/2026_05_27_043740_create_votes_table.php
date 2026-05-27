<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('poll_uuid')->constrained('polls')->cascadeOnDelete();
            $table->foreignid('option_id')->constrained('options')->cascadeOnDelete();
            $table->string('ip_hash');
            $table->unique(['poll_uuid', 'ip_hash']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
