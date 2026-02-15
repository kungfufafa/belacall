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
        Schema::table('reports', function (Blueprint $table) {
            $table->timestamp('response_deadline')->nullable()->after('status');
            $table->timestamp('resolution_deadline')->nullable()->after('response_deadline');
            $table->timestamp('responded_at')->nullable()->after('resolution_deadline');
            $table->timestamp('resolved_at')->nullable()->after('responded_at');

            $table->index('response_deadline');
            $table->index('resolution_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['response_deadline']);
            $table->dropIndex(['resolution_deadline']);
            $table->dropColumn(['response_deadline', 'resolution_deadline', 'responded_at', 'resolved_at']);
        });
    }
};
