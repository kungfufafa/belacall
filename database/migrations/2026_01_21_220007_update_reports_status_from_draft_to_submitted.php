<?php

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Report::query()
            ->where('status', 'DRAFT')
            ->update(['status' => ReportStatus::SUBMITTED->value]);
    }

    public function down(): void
    {
        Report::query()
            ->where('status', ReportStatus::SUBMITTED->value)
            ->update(['status' => 'DRAFT']);
    }
};
