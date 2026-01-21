<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type'); // image, video, document
            $table->string('uploaded_by')->nullable(); // user_id or 'bot'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_evidences');
    }
};
