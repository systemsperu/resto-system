<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Employee;
use App\Models\Order;
use App\Models\SystemAudit;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiningRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_table_with_capacity_and_assigns_active_waiter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'waiter']);
        $waiter = Employee::create(['user_id' => $account->id, 'full_name' => 'Mozo Uno', 'position' => 'waiter', 'is_active' => true]);
        $area = Area::create(['name' => 'Terraza']);

        $this->actingAs($admin)->post(route('tables.storeTable'), ['name' => 'Mesa 8', 'area_id' => $area->id, 'seats' => 6])->assertRedirect();
        $table = Table::firstOrFail();
        $this->actingAs($admin)->patch(route('tables.assignWaiter', $table), ['assigned_employee_id' => $waiter->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tables', ['id' => $table->id, 'seats' => 6, 'assigned_employee_id' => $waiter->id]);
        $this->assertDatabaseHas('system_audits', ['action' => 'table.waiter_assigned', 'target' => 'table:'.$table->id]);
    }

    public function test_rejects_inactive_or_inconsistent_waiter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'cashier']);
        $employee = Employee::create(['user_id' => $account->id, 'full_name' => 'No elegible', 'position' => 'waiter', 'is_active' => true]);
        $area = Area::create(['name' => 'Salón']);
        $table = Table::create(['name' => 'Mesa 1', 'area_id' => $area->id, 'seats' => 4]);

        $this->actingAs($admin)->patch(route('tables.assignWaiter', $table), ['assigned_employee_id' => $employee->id])
            ->assertSessionHasErrors('assigned_employee_id');
        $this->assertNull($table->fresh()->assigned_employee_id);
    }

    public function test_preserves_table_with_order_history_and_area_with_tables(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $area = Area::create(['name' => 'Jardín']);
        $table = Table::create(['name' => 'Mesa 2', 'area_id' => $area->id, 'seats' => 4]);
        Order::create(['table_id' => $table->id, 'user_id' => $admin->id, 'status' => 'pending', 'total' => 0]);

        $this->actingAs($admin)->delete(route('tables.destroyTable', $table))->assertSessionHasErrors('table');
        $this->actingAs($admin)->delete(route('tables.destroyArea', $area))->assertSessionHasErrors('area');

        $this->assertDatabaseHas('tables', ['id' => $table->id]);
        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }

    public function test_position_update_is_validated_and_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $area = Area::create(['name' => 'Piscina']);
        $table = Table::create(['name' => 'Mesa 3', 'area_id' => $area->id, 'seats' => 2]);

        $this->actingAs($admin)->postJson(route('tables.updatePositions'), ['positions' => [['id' => $table->id, 'x' => 140, 'y' => 90]]])->assertOk();
        $this->assertDatabaseHas('tables', ['id' => $table->id, 'x_pos' => 140, 'y_pos' => 90]);
        $this->assertTrue(SystemAudit::where('action', 'table.positions_updated')->exists());

        $this->actingAs($admin)->postJson(route('tables.updatePositions'), ['positions' => [['id' => $table->id, 'x' => -1, 'y' => 90]]])->assertUnprocessable();
    }

    public function test_waiter_cannot_administer_dining_room(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)->get(route('tables.index'))->assertForbidden();
    }
}
