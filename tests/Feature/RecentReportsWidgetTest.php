<?php

namespace Tests\Feature;

use App\Filament\Widgets\RecentReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecentReportsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_reports_widget_renders(): void
    {
        Livewire::test(RecentReports::class)
            ->assertOk();
    }
}
