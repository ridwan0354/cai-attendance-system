<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Groups (Regional)
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region_code', 20)->unique();
            $table->string('pembina_name');
            $table->string('pembina_phone', 20); // WhatsApp number
            $table->string('color', 7)->default('#0052cc'); // hex color for UI
            $table->timestamps();
        });

        // 2. Participants
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->string('name');
            $table->string('nik', 20)->nullable()->unique();
            $table->string('photo_path')->nullable();
            $table->boolean('face_registered')->default(false);
            $table->string('rfid_code', 50)->nullable()->unique();
            $table->string('qr_code', 100)->nullable()->unique();
            $table->timestamps();
        });

        // 3. Event Sessions (renamed from 'sessions' to avoid conflict with Laravel sessions)
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('day_number'); // 1, 2, 3
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 4. Attendance records
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('event_sessions')->onDelete('cascade');
            $table->timestamp('check_in_time');
            $table->enum('method', ['face', 'rfid', 'qr', 'manual'])->default('face');
            $table->decimal('confidence_score', 5, 2)->nullable(); // for face method
            $table->string('notes')->nullable();
            $table->unique(['participant_id', 'session_id']); // 1 attendance per session
            $table->timestamps();
        });

        // 5. WhatsApp notification logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('session_id')->constrained('event_sessions');
            $table->string('phone_number', 20);
            $table->text('message');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->string('fonnte_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('groups');
    }
};
