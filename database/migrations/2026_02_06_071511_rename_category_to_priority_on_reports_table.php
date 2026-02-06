<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reports', 'category') || Schema::hasColumn('reports', 'priority')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table): void {
            $table->string('priority')->default('Medium');
        });

        DB::table('reports')->where('category', 'General')->update(['priority' => 'Medium']);
        DB::table('reports')->where('category', 'Web Report')->update(['priority' => 'Medium']);
        DB::table('reports')->where('category', 'Infrastruktur')->update(['priority' => 'Medium']);
        DB::table('reports')->where('category', 'Pelayanan')->update(['priority' => 'Medium']);
        DB::table('reports')->where('category', 'Sampah')->update(['priority' => 'High']);
        DB::table('reports')->where('category', 'Keamanan')->update(['priority' => 'Urgent']);
        DB::table('reports')->where('category', 'Lainnya')->update(['priority' => 'Low']);

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reports', 'priority') || Schema::hasColumn('reports', 'category')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table): void {
            $table->string('category')->default('General');
        });

        DB::table('reports')->where('priority', 'Urgent')->update(['category' => 'Keamanan']);
        DB::table('reports')->where('priority', 'High')->update(['category' => 'Sampah']);
        DB::table('reports')->where('priority', 'Medium')->update(['category' => 'General']);
        DB::table('reports')->where('priority', 'Low')->update(['category' => 'Lainnya']);

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('priority');
        });
    }
};
