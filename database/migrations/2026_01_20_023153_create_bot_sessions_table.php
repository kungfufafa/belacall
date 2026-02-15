<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->string('state')->default('IDLE');
            // States: IDLE, WAITING_REPORT_TITLE, WAITING_REPORT_PHOTO, WAITING_REPORT_LOCATION

            $table->json('temp_data')->nullable(); // Simpan data sementara (draft title, photo path)
            $table->timestamp('last_interaction_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_sessions');
    }
};
