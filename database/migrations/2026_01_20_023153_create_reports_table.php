<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // T-20231001-001
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pelapor
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null'); // Operator/Petugas
            
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->default('General'); // Infrastruktur, Sampah, dll
            
            $table->string('location_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status Flow: DRAFT -> SUBMITTED -> VERIFIED -> IN_PROGRESS -> RESOLVED -> CLOSED
            // REJECTED / NEEDS_REVISION
            $table->string('status')->default('DRAFT');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
