<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Filament\Pages\DashboardOperator;
use App\Filament\Pages\DashboardPimpinan;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => Role::OPERATOR]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(DashboardOperator::getUrl());
    }

    public function test_pimpinan_can_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => Role::PIMPINAN]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(DashboardPimpinan::getUrl());
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => Role::ADMIN]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(DashboardOperator::getUrl());
    }

    public function test_warga_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => Role::WARGA]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_resource_icons_are_set(): void
    {
        $this->assertSame(Heroicon::OutlinedDocumentText, ReportResource::getNavigationIcon());
        $this->assertSame(Heroicon::OutlinedUsers, UserResource::getNavigationIcon());
    }
}
