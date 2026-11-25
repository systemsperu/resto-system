<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_waste_supports_decimal_units_and_is_audited_in_kardex(): void
    {
        [$admin, $product] = $this->productFixture(stock: 5.5);

        $this->actingAs($admin)->post(route('products.adjust', $product), [
            'type' => 'waste', 'quantity' => 0.375, 'note' => 'Merma de preparación',
        ])->assertSessionHasNoErrors();

        $this->assertSame('5.125', $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id, 'type' => 'waste', 'quantity' => -0.375,
            'note' => 'Merma de preparación',
        ]);
    }

    public function test_adjustment_cannot_leave_negative_stock(): void
    {
        [$admin, $product] = $this->productFixture(stock: 1);

        $this->actingAs($admin)->post(route('products.adjust', $product), [
            'type' => 'sub', 'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame('1.000', $product->fresh()->stock);
        $this->assertDatabaseCount('inventory_logs', 0);
    }

    public function test_modifier_group_is_assigned_to_product_with_options(): void
    {
        [$admin, $product] = $this->productFixture();

        $this->actingAs($admin)->post(route('modifiers.store'), [
            'name' => 'Término de cocción', 'min_selections' => 1, 'max_selections' => 1,
            'products' => [$product->id],
        ])->assertSessionHasNoErrors();
        $group = $product->modifierGroups()->firstOrFail();
        $this->actingAs($admin)->post(route('modifiers.options.store', $group), [
            'name' => 'Término medio', 'price_delta' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('modifier_group_product', ['modifier_group_id' => $group->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('modifier_options', ['modifier_group_id' => $group->id, 'name' => 'Término medio']);
    }

    public function test_checkout_rolls_back_when_stock_is_insufficient(): void
    {
        [$cashier, $product] = $this->productFixture(role: 'cashier', stock: 0.5);
        $register = CashRegister::create(['user_id' => $cashier->id, 'opening_time' => now(), 'opening_amount' => 50, 'status' => 'open']);
        $area = Area::create(['name' => 'Salón']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa 1', 'seats' => 2, 'status' => 'occupied']);
        $order = Order::create(['table_id' => $table->id, 'user_id' => $cashier->id, 'cash_register_id' => $register->id, 'status' => 'pending', 'total' => 20]);
        $order->details()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 20, 'status' => 'served']);

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Ticket', 'payment_method' => 'card',
        ])->assertSessionHasErrors('stock');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('0.500', $product->fresh()->stock);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_logs', 0);
    }

    private function productFixture(string $role = 'admin', float $stock = 10): array
    {
        $user = User::factory()->create(['role' => $role]);
        $category = Category::create(['name' => 'Pruebas', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Insumo', 'price' => 20,
            'cost' => 5, 'stock' => $stock, 'unit' => 'kg', 'track_stock' => true,
            'low_stock_threshold' => 1, 'is_active' => true, 'is_saleable' => true,
        ]);

        return [$user, $product];
    }
}
