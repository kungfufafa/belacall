<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\EmergencyShortcuts\EmergencyShortcutResource;
use App\Filament\Resources\EmergencyShortcuts\Pages\ListEmergencyShortcuts;
use App\Models\EmergencyShortcut;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmergencyShortcutResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_emergency_shortcuts(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $shortcuts = EmergencyShortcut::factory()->count(3)->create();

        $this->actingAs($admin);

        $this->get(EmergencyShortcutResource::getUrl('index'))
            ->assertOk();

        Livewire::test(ListEmergencyShortcuts::class)
            ->assertCanSeeTableRecords($shortcuts);
    }

    public function test_admin_can_create_emergency_shortcut(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);

        $this->actingAs($admin);

        Livewire::test(ListEmergencyShortcuts::class)
            ->callAction('create', [
                'name' => 'Ambulans',
                'phone_number' => '118',
                'description' => 'Layanan ambulans darurat',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('emergency_shortcuts', [
            'name' => 'Ambulans',
            'phone_number' => '118',
            'description' => 'Layanan ambulans darurat',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_emergency_shortcut(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $shortcut = EmergencyShortcut::factory()->create([
            'name' => 'Old Name',
            'phone_number' => '999',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListEmergencyShortcuts::class)
            ->callTableAction('edit', $shortcut, [
                'name' => 'Updated Name',
                'phone_number' => '112',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('emergency_shortcuts', [
            'id' => $shortcut->id,
            'name' => 'Updated Name',
            'phone_number' => '112',
        ]);
    }

    public function test_admin_can_delete_emergency_shortcut(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $shortcut = EmergencyShortcut::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListEmergencyShortcuts::class)
            ->callTableAction('delete', $shortcut);

        $this->assertDatabaseMissing('emergency_shortcuts', [
            'id' => $shortcut->id,
        ]);
    }

    public function test_non_admin_cannot_access_emergency_shortcuts(): void
    {
        $nonAdminRoles = [Role::OPERATOR, Role::PIMPINAN, Role::WARGA];

        foreach ($nonAdminRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);

            $this->get(EmergencyShortcutResource::getUrl('index'))
                ->assertForbidden();
        }
    }

    public function test_validation_requires_name_and_phone(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);

        $this->actingAs($admin);

        Livewire::test(ListEmergencyShortcuts::class)
            ->callAction('create', [
                'name' => null,
                'phone_number' => null,
                'sort_order' => 0,
            ])
            ->assertHasActionErrors([
                'name' => 'required',
                'phone_number' => 'required',
            ]);
    }

    public function test_active_scope_returns_only_active_ordered(): void
    {
        $active1 = EmergencyShortcut::factory()->create(['is_active' => true, 'sort_order' => 2]);
        $active2 = EmergencyShortcut::factory()->create(['is_active' => true, 'sort_order' => 1]);
        EmergencyShortcut::factory()->inactive()->create(['sort_order' => 0]);

        $results = EmergencyShortcut::query()->active()->get();

        $this->assertCount(2, $results);
        $this->assertEquals($active2->id, $results->first()->id);
        $this->assertEquals($active1->id, $results->last()->id);
    }

    public function test_emergency_shortcut_resource_uses_slideover_modals(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $shortcut = EmergencyShortcut::factory()->create();

        $this->actingAs($admin);

        $this->assertFalse(EmergencyShortcutResource::hasPage('create'));
        $this->assertFalse(EmergencyShortcutResource::hasPage('edit'));

        Livewire::test(ListEmergencyShortcuts::class)
            ->assertActionExists('create', fn (Action $action): bool => $action->isModalSlideOver())
            ->assertTableActionExists('edit', fn (Action $action): bool => $action->isModalSlideOver(), $shortcut)
            ->assertTableActionHasColor('edit', 'warning', $shortcut);
    }
}
