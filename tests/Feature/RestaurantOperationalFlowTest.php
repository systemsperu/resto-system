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

class RestaurantOperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_agregar_un_producto_ocupa_la_mesa_y_crea_el_pedido(): void
    {
        [$waiter, $table, $product] = $this->operationFixture('waiter');

        $this->actingAs($waiter)
            ->post(route('pos.add', $table), ['product_id' => $product->id])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'table_id' => $table->id,
            'user_id' => $waiter->id,
            'status' => 'pending',
            'total' => 25,
        ]);
        $this->assertDatabaseHas('order_details', [
            'product_id' => $product->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_rechaza_efectivo_insuficiente_y_pedido_sin_terminar(): void
    {
        [$cashier, $table, $product] = $this->operationFixture('cashier');
        $this->openRegister($cashier);
        $order = $this->pendingOrder($cashier, $table, $product, 'cooking');

        $this->actingAs($cashier)
            ->from(route('pos.order', $table))
            ->post(route('pos.checkout', $order), [
                'payment_method' => 'cash',
                'received_amount' => 20,
                'document_type' => 'Ticket',
            ])
            ->assertRedirect(route('pos.order', $table))
            ->assertSessionHas('error');

        $order->details()->update(['status' => 'served']);

        $this->actingAs($cashier)
            ->from(route('pos.order', $table))
            ->post(route('pos.checkout', $order), [
                'payment_method' => 'cash',
                'received_amount' => 25,
                'document_type' => 'Ticket',
            ])
            ->assertRedirect(route('sales.ticket', $order));

        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_no_cobra_un_pedido_que_cocina_no_termino(): void
    {
        [$cashier, $table, $product] = $this->operationFixture('cashier');
        $this->openRegister($cashier);
        $order = $this->pendingOrder($cashier, $table, $product, 'pending');

        $this->actingAs($cashier)
            ->from(route('pos.order', $table))
            ->post(route('pos.checkout', $order), [
                'payment_method' => 'card',
                'document_type' => 'Ticket',
            ])
            ->assertRedirect(route('pos.order', $table))
            ->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('10.000', $product->fresh()->stock);
    }

    public function test_cobro_correcto_libera_mesa_y_descuenta_stock(): void
    {
        [$cashier, $table, $product] = $this->operationFixture('cashier');
        $register = $this->openRegister($cashier);
        $order = $this->pendingOrder($cashier, $table, $product, 'served');

        $this->actingAs($cashier)
            ->post(route('pos.checkout', $order), [
                'payment_method' => 'cash',
                'received_amount' => 30,
                'document_type' => 'Ticket',
            ])
            ->assertRedirect(route('sales.ticket', $order));

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame($register->id, $order->cash_register_id);
        $this->assertSame('5.00', $order->change_amount);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertSame('9.000', $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'quantity' => -1,
            'type' => 'sale',
        ]);
    }

    public function test_un_mozo_no_puede_modificar_el_pedido_de_otro_mozo(): void
    {
        [$owner, $table, $product] = $this->operationFixture('waiter');
        $otherWaiter = User::factory()->create(['role' => 'waiter']);
        $order = $this->pendingOrder($owner, $table, $product, 'pending');
        $detail = $order->details()->firstOrFail();

        $this->actingAs($otherWaiter)
            ->post(route('pos.note', $detail), ['note' => 'Cambio no autorizado'])
            ->assertForbidden();

        $this->assertNull($detail->fresh()->note);
    }

    public function test_un_segundo_cobro_no_vuelve_a_descontar_inventario(): void
    {
        [$cashier, $table, $product] = $this->operationFixture('cashier');
        $this->openRegister($cashier);
        $order = $this->pendingOrder($cashier, $table, $product, 'served');
        $payload = [
            'payment_method' => 'cash',
            'received_amount' => 25,
            'document_type' => 'Ticket',
        ];

        $this->actingAs($cashier)
            ->post(route('pos.checkout', $order), $payload)
            ->assertRedirect(route('sales.ticket', $order));

        $this->actingAs($cashier)
            ->post(route('pos.checkout', $order), $payload)
            ->assertForbidden();

        $this->assertSame('9.000', $product->fresh()->stock);
        $this->assertDatabaseCount('inventory_logs', 1);
    }

    private function operationFixture(string $role): array
    {
        $user = User::factory()->create(['role' => $role]);
        $area = Area::create(['name' => 'Salón de prueba']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa QA', 'seats' => 4, 'status' => 'available']);
        if ($role === 'waiter') {
            $employee = Employee::create(['user_id' => $user->id, 'full_name' => $user->name, 'position' => 'waiter', 'is_active' => true]);
            $table->update(['assigned_employee_id' => $employee->id]);
        }
        $category = Category::create(['name' => 'Pruebas', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Plato de prueba',
            'price' => 25,
            'stock' => 10,
            'is_active' => true,
            'is_saleable' => true,
        ]);

        return [$user, $table, $product];
    }

    private function openRegister(User $user): CashRegister
    {
        return CashRegister::create([
            'user_id' => $user->id,
            'opening_time' => now(),
            'opening_amount' => 100,
            'status' => 'open',
        ]);
    }

    private function pendingOrder(User $user, Table $table, Product $product, string $detailStatus): Order
    {
        $table->update(['status' => 'occupied']);
        $order = Order::create(['table_id' => $table->id, 'user_id' => $user->id, 'status' => 'pending', 'total' => 25]);
        $order->details()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 25,
            'status' => $detailStatus,
        ]);

        return $order;
    }
}
