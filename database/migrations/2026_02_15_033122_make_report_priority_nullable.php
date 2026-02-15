<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('priority')->nullable()->default(null)->change();
        });

        DB::table('reports')
            ->whereNull('assignee_id')
            ->whereIn('status', ['SUBMITTED', 'NEEDS_REVISION'])
            ->update(['priority' => null]);
    }

    public function down(): void
    {
        DB::table('reports')
            ->whereNull('priority')
            ->update(['priority' => 'Medium']);

        Schema::table('reports', function (Blueprint $table) {
            $table->string('priority')->default('Medium')->nullable(false)->change();
        });
    }
};
