<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_crea_ficha_separada_y_vincula_una_cuenta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($admin)->post(route('employees.store'), [
            'user_id' => $account->id,
            'full_name' => 'María Pérez',
            'phone' => '+51 999 111 222',
            'position' => 'waiter',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'user_id' => $account->id,
            'full_name' => 'María Pérez',
            'position' => 'waiter',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('system_audits', ['action' => 'employee.created']);
    }

    public function test_usuario_sin_permiso_no_administra_personal(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)->post(route('employees.store'), [
            'full_name' => 'Sin permiso', 'position' => 'waiter',
        ])->assertForbidden();
    }

    public function test_rechaza_turnos_superpuestos_y_personal_inactivo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['full_name' => 'Cocina Uno', 'position' => 'kitchen']);

        $payload = ['starts_at' => '2026-10-16 08:00:00', 'ends_at' => '2026-10-16 16:00:00', 'status' => 'scheduled'];
        $this->actingAs($admin)->post(route('employees.shifts.store', $employee), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('employees.shifts.store', $employee), [
            'starts_at' => '2026-10-16 15:00:00', 'ends_at' => '2026-10-16 18:00:00', 'status' => 'scheduled',
        ])->assertSessionHasErrors('starts_at');

        $employee->update(['is_active' => false]);
        $this->actingAs($admin)->post(route('employees.shifts.store', $employee), [
            'starts_at' => '2026-10-17 08:00:00', 'ends_at' => '2026-10-17 16:00:00', 'status' => 'scheduled',
        ])->assertSessionHasErrors('employee');

        $this->assertSame(1, $employee->shifts()->count());
    }

    public function test_desactivar_personal_conserva_cuenta_y_turnos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'cashier']);
        $employee = Employee::create(['user_id' => $account->id, 'full_name' => 'Caja Uno', 'position' => 'cashier']);
        $shift = $employee->shifts()->create([
            'starts_at' => '2026-10-16 08:00:00', 'ends_at' => '2026-10-16 16:00:00', 'status' => 'scheduled',
        ]);

        $this->actingAs($admin)->patch(route('employees.toggle', $employee))->assertRedirect();

        $this->assertFalse($employee->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $account->id]);
        $this->assertDatabaseHas('work_shifts', ['id' => $shift->id]);
    }
}
