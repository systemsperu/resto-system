<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_keeps_creator_and_waiter_snapshot(): void
    {
        [$waiterUser, $waiter, $source, , $product] = $this->fixture();

        $this->actingAs($waiterUser)->post(route('pos.add', $source), ['product_id' => $product->id])->assertOk();

        $order = Order::firstOrFail();
        $this->assertSame($waiterUser->id, $order->user_id);
        $this->assertSame($waiter->id, $order->waiter_employee_id);
    }

    public function test_move_is_atomic_for_table_states_and_audited(): void
    {
        [$waiterUser, $waiter, $source, $target, $product] = $this->fixture();
        $order = $this->order($waiterUser, $waiter, $source, $product);

        $this->actingAs($waiterUser)->post(route('pos.move', $order), ['target_table_id' => $target->id])
            ->assertRedirect(route('pos.order', $target));

        $this->assertSame($target->id, $order->fresh()->table_id);
        $this->assertSame('available', $source->fresh()->status);
        $this->assertSame('occupied', $target->fresh()->status);
        $this->assertDatabaseHas('system_audits', ['action' => 'order.table_moved', 'target' => 'order:'.$order->id]);
    }

    public function test_move_rejects_occupied_or_inactive_destination_without_partial_changes(): void
    {
        [$waiterUser, $waiter, $source, $target, $product] = $this->fixture();
        $order = $this->order($waiterUser, $waiter, $source, $product);
        $target->update(['is_active' => false]);

        $this->actingAs($waiterUser)->from(route('pos.order', $source))->post(route('pos.move', $order), ['target_table_id' => $target->id])
            ->assertSessionHasErrors('target_table_id');

        $this->assertSame($source->id, $order->fresh()->table_id);
        $this->assertSame('occupied', $source->fresh()->status);
    }

    public function test_removing_last_pending_item_cancels_order_and_releases_table(): void
    {
        [$waiterUser, $waiter, $source, , $product] = $this->fixture();
        $order = $this->order($waiterUser, $waiter, $source, $product);
        $detail = $order->details()->firstOrFail();

        $this->actingAs($waiterUser)->delete(route('pos.remove', $detail))->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('available', $source->fresh()->status);
        $this->assertDatabaseHas('system_audits', ['action' => 'order.cancelled_empty', 'target' => 'order:'.$order->id]);
    }

    public function test_kitchen_advances_one_expected_step_and_rejects_replay(): void
    {
        [$waiterUser, $waiter, $source, , $product] = $this->fixture();
        $order = $this->order($waiterUser, $waiter, $source, $product);
        $detail = $order->details()->firstOrFail();
        $kitchen = User::factory()->create(['role' => 'kitchen']);

        $this->actingAs($kitchen)->post(route('kitchen.update', $detail), ['expected_status' => 'pending'])
            ->assertRedirect(route('kitchen.index'));
        $this->assertSame('cooking', $detail->fresh()->status);

        $this->actingAs($kitchen)->post(route('kitchen.update', $detail), ['expected_status' => 'pending'])
            ->assertSessionHasErrors('detail');
        $this->assertSame('cooking', $detail->fresh()->status);

        $this->actingAs($kitchen)->post(route('kitchen.update', $detail), ['expected_status' => 'cooking']);
        $this->assertSame('served', $detail->fresh()->status);
    }

    private function fixture(): array
    {
        $waiterUser = User::factory()->create(['role' => 'waiter']);
        $waiter = Employee::create(['user_id' => $waiterUser->id, 'full_name' => 'Mozo responsable', 'position' => 'waiter', 'is_active' => true]);
        $area = Area::create(['name' => 'Salón']);
        $source = Table::create(['area_id' => $area->id, 'assigned_employee_id' => $waiter->id, 'name' => 'Mesa 1', 'seats' => 4, 'status' => 'available']);
        $target = Table::create(['area_id' => $area->id, 'assigned_employee_id' => $waiter->id, 'name' => 'Mesa 2', 'seats' => 4, 'status' => 'available']);
        $category = Category::create(['name' => 'Carta', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Plato', 'price' => 20, 'stock' => 10, 'is_active' => true, 'is_saleable' => true]);

        return [$waiterUser, $waiter, $source, $target, $product];
    }

    private function order(User $user, Employee $waiter, Table $table, Product $product): Order
    {
        $table->update(['status' => 'occupied']);
        $order = Order::create(['table_id' => $table->id, 'user_id' => $user->id, 'waiter_employee_id' => $waiter->id, 'status' => 'pending', 'total' => 20]);
        $order->details()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 20, 'status' => 'pending']);

        return $order;
    }
}
