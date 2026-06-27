<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Supplies Table
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 2. Participant Supply Pivot Table
        Schema::create('participant_supply', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('supply_id')->constrained('supplies')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Add registration columns to participants table
        Schema::table('participants', function (Blueprint $table) {
            $table->text('registration_notes')->nullable();
            $table->timestamp('registered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['registration_notes', 'registered_at']);
        });
        Schema::dropIfExists('participant_supply');
        Schema::dropIfExists('supplies');
    }
};
