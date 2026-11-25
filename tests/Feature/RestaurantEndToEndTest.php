<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_restaurant_shift_from_table_to_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $waiterUser = User::factory()->create(['role' => 'waiter']);
        $kitchen = User::factory()->create(['role' => 'kitchen']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($admin)->post(route('employees.store'), [
            'user_id' => $waiterUser->id, 'full_name' => 'Mozo del turno',
            'position' => 'waiter', 'is_active' => true,
        ])->assertSessionHasNoErrors();
        $waiter = Employee::firstOrFail();

        $this->actingAs($admin)->post(route('tables.storeArea'), ['name' => 'Salón principal'])->assertSessionHasNoErrors();
        $area = Area::firstOrFail();
        $this->actingAs($admin)->post(route('tables.storeTable'), [
            'name' => 'Mesa 01', 'area_id' => $area->id, 'seats' => 4,
        ])->assertSessionHasNoErrors();
        $table = Table::firstOrFail();
        $this->actingAs($admin)->patch(route('tables.assignWaiter', $table), ['assigned_employee_id' => $waiter->id])
            ->assertSessionHasNoErrors();

        $category = Category::create(['name' => 'Cocina', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Plato integral', 'price' => 25,
            'cost' => 8, 'stock' => 10, 'unit' => 'unit', 'track_stock' => true,
            'low_stock_threshold' => 2, 'is_active' => true, 'is_saleable' => true,
        ]);

        $this->actingAs($waiterUser)->post(route('pos.add', $table), ['product_id' => $product->id])->assertOk();
        $order = Order::with('details')->firstOrFail();
        $detail = $order->details->first();
        $this->assertSame($waiter->id, $order->waiter_employee_id);
        $this->assertSame('occupied', $table->fresh()->status);

        $this->actingAs($kitchen)->post(route('kitchen.update', $detail), ['expected_status' => 'pending'])
            ->assertRedirect(route('kitchen.index'));
        $this->actingAs($kitchen)->post(route('kitchen.update', $detail->fresh()), ['expected_status' => 'cooking'])
            ->assertRedirect(route('kitchen.index'));
        $this->assertSame('served', $detail->fresh()->status);

        $register = CashRegister::create([
            'user_id' => $cashier->id, 'opening_time' => now(), 'opening_amount' => 100, 'status' => 'open',
        ]);
        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Ticket',
            'payments' => [
                ['method' => 'cash', 'amount' => 10, 'received_amount' => 20],
                ['method' => 'card', 'amount' => 15, 'reference' => 'E2E-001'],
            ],
        ])->assertRedirect(route('sales.ticket', $order));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertSame('9.000', $product->fresh()->stock);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('inventory_logs', ['product_id' => $product->id, 'type' => 'sale', 'quantity' => -1]);

        $this->actingAs($cashier)->post(route('cash_registers.processClose'), [
            'closing_amount' => 110, 'notes' => 'Cierre E2E',
        ])->assertSessionHas('success');
        $this->assertSame('closed', $register->fresh()->status);
        $this->assertSame('110.00', $register->fresh()->expected_amount);

        $today = now()->format('Y-m-d');
        $this->actingAs($admin)->get(route('sales.index', ['start_date' => $today, 'end_date' => $today]))
            ->assertOk()->assertViewHas('totalCash', 10.0)->assertViewHas('totalCard', 15.0)->assertViewHas('totalSales', 25.0);
        $this->actingAs($admin)->get(route('reports.index', ['start_date' => $today, 'end_date' => $today]))
            ->assertOk()->assertSee('Plato integral');

        $this->assertDatabaseHas('system_audits', ['action' => 'cash_register.closed', 'target' => 'cash_register:'.$register->id]);
        $this->assertDatabaseHas('system_audits', ['action' => 'order_detail.status_changed', 'target' => 'order:'.$order->id]);
    }
}
