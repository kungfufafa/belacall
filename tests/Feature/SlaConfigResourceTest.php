<?php

namespace Tests\Feature;

use App\Enums\ReportPriority;
use App\Enums\Role;
use App\Filament\Resources\SlaConfigs\Pages\ListSlaConfigs;
use App\Filament\Resources\SlaConfigs\SlaConfigResource;
use App\Models\SlaConfig;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SlaConfigResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_admin_can_view_sla_config_list(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        SlaConfig::factory()->create(['priority' => ReportPriority::URGENT]);

        $this->actingAs($admin);

        $this->get(SlaConfigResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Konfigurasi SLA');
    }

    public function test_admin_can_edit_sla_config(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $config = SlaConfig::factory()->create([
            'priority' => ReportPriority::HIGH,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListSlaConfigs::class)
            ->callTableAction('edit', $config, [
                'response_time_minutes' => 30,
                'resolution_time_minutes' => 360,
            ]);

        $this->assertDatabaseHas('sla_configs', [
            'id' => $config->id,
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 360,
        ]);
    }

    public function test_non_admin_cannot_access_sla_config(): void
    {
        $nonAdminRoles = [Role::OPERATOR, Role::PIMPINAN, Role::WARGA];

        foreach ($nonAdminRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);

            $this->get(SlaConfigResource::getUrl('index'))
                ->assertForbidden();
        }
    }

    public function test_sla_config_validation_rejects_zero_times(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $config = SlaConfig::factory()->create([
            'priority' => ReportPriority::MEDIUM,
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 2880,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListSlaConfigs::class)
            ->callTableAction('edit', $config, [
                'response_time_minutes' => 0,
                'resolution_time_minutes' => 0,
            ])
            ->assertHasTableActionErrors([
                'response_time_minutes' => 'min',
                'resolution_time_minutes' => 'min',
            ]);
    }

    public function test_sla_config_for_priority_returns_config(): void
    {
        $config = SlaConfig::factory()->create([
            'priority' => ReportPriority::URGENT,
            'response_time_minutes' => 10,
            'resolution_time_minutes' => 60,
        ]);

        $result = SlaConfig::forPriority(ReportPriority::URGENT);

        $this->assertTrue($result->exists);
        $this->assertSame(10, $result->response_time_minutes);
        $this->assertSame(60, $result->resolution_time_minutes);
    }

    public function test_sla_config_for_priority_falls_back_to_defaults(): void
    {
        $result = SlaConfig::forPriority(ReportPriority::URGENT);

        $this->assertFalse($result->exists);
        $this->assertSame(15, $result->response_time_minutes);
        $this->assertSame(120, $result->resolution_time_minutes);
    }

    public function test_sla_config_edit_uses_slideover_modal(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $config = SlaConfig::factory()->create(['priority' => ReportPriority::LOW]);

        $this->actingAs($admin);

        Livewire::test(ListSlaConfigs::class)
            ->assertTableActionExists('edit', fn (Action $action): bool => $action->isModalSlideOver(), $config)
            ->assertTableActionHasColor('edit', 'warning', $config);
    }

    public function test_editing_sla_config_invalidates_cached_priority_value(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $config = SlaConfig::factory()->create([
            'priority' => ReportPriority::URGENT,
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
        ]);

        $this->assertSame(15, SlaConfig::forPriority(ReportPriority::URGENT)->response_time_minutes);

        $this->actingAs($admin);

        Livewire::test(ListSlaConfigs::class)
            ->callTableAction('edit', $config, [
                'response_time_minutes' => 25,
                'resolution_time_minutes' => 140,
            ]);

        $this->assertSame(25, SlaConfig::forPriority(ReportPriority::URGENT)->response_time_minutes);
        $this->assertSame(140, SlaConfig::forPriority(ReportPriority::URGENT)->resolution_time_minutes);
    }
}
