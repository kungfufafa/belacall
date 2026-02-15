<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sessions', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('phone_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('bot_sessions', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
    }
};
