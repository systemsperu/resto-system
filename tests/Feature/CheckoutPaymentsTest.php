<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Client;
use App\Models\DocumentSeries;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_button_opens_payment_modal_with_bootstrap_data_api(): void
    {
        [$cashier, $order, $table] = $this->fixture();

        $this->actingAs($cashier)
            ->get(route('pos.order', $table))
            ->assertOk()
            ->assertSee('data-bs-toggle="modal"', false)
            ->assertSee('data-bs-target="#checkoutModal"', false)
            ->assertSee('id="checkoutModal"', false)
            ->assertDontSee('new bootstrap.Modal(m)', false);
    }

    public function test_combined_payment_completes_order_and_records_each_method(): void
    {
        [$cashier, $order] = $this->fixture();

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Ticket',
            'payments' => [
                ['method' => 'cash', 'amount' => 10, 'received_amount' => 20],
                ['method' => 'card', 'amount' => 15, 'reference' => 'POS-001'],
            ],
        ])->assertRedirect(route('sales.ticket', $order));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('mixed', $order->fresh()->payment_method);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'method' => 'cash', 'amount' => 10, 'change_amount' => 10]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'method' => 'card', 'amount' => 15, 'reference' => 'POS-001']);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_electronic_receipt_redirects_to_its_printable_document(): void
    {
        [$cashier, $order] = $this->fixture();
        DocumentSeries::create([
            'document_code' => '03',
            'document_type' => 'boleta',
            'serie' => 'B001',
            'last_number' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Boleta',
            'payments' => [['method' => 'cash', 'amount' => 25, 'received_amount' => 25]],
        ])->assertRedirect(route('billing.pdf', $order));

        $order->refresh();
        $this->assertSame('Boleta', $order->document_type);
        $this->assertSame('B001', $order->serie);
        $this->assertSame(1, $order->correlativo);
        $this->assertSame('PENDING', $order->sunat_status);
        $this->assertDatabaseHas('sunat_submissions', ['order_id' => $order->id, 'status' => 'pending']);
        $this->actingAs($cashier)->get(route('billing.pdf', $order))->assertOk()->assertSee('BOLETA');
    }

    public function test_invoice_uses_registered_client_fiscal_data_and_redirects_to_pdf(): void
    {
        [$cashier, $order] = $this->fixture();
        $client = Client::create([
            'name' => 'CLIENTE DE PRUEBA SAC',
            'document_type' => 'RUC',
            'document_number' => '20123456789',
        ]);
        DocumentSeries::create([
            'document_code' => '01',
            'document_type' => 'factura',
            'serie' => 'F001',
            'last_number' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Factura',
            'client_id' => $client->id,
            'client_document' => '99999999999',
            'payments' => [['method' => 'card', 'amount' => 25]],
        ])->assertRedirect(route('billing.pdf', $order));

        $order->refresh();
        $this->assertSame('Factura', $order->document_type);
        $this->assertSame($client->name, $order->client_name);
        $this->assertSame($client->document_number, $order->client_document);
        $this->assertSame('F001', $order->serie);
    }

    public function test_checkout_explains_that_pending_kitchen_items_cannot_be_charged(): void
    {
        [$cashier, $order, $table, $product] = $this->fixture();
        $order->details()->update(['status' => 'pending']);

        $this->actingAs($cashier)
            ->get(route('pos.order', $table))
            ->assertOk()
            ->assertSee('No se puede confirmar el pago: 1 partida aún no está servida.')
            ->assertSee('PENDIENTE DE COCINA');

        $this->actingAs($cashier)
            ->from(route('pos.order', $table))
            ->post(route('pos.checkout', $order), [
                'document_type' => 'Ticket',
                'payments' => [['method' => 'cash', 'amount' => 25, 'received_amount' => 25]],
            ])
            ->assertRedirect(route('pos.order', $table))
            ->assertSessionHas('error', 'No se puede cobrar mientras existan productos pendientes en cocina.');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('occupied', $table->fresh()->status);
        $this->assertSame('10.000', $product->fresh()->stock);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cart_uses_the_adjusted_order_total_only_once(): void
    {
        [$cashier, $order, $table] = $this->fixture();
        $order->update(['discount' => 5, 'tip' => 3, 'total' => 23]);

        $this->actingAs($cashier)
            ->get(route('pos.order', $table))
            ->assertOk()
            ->assertSee('id="cartTotalValue" value="23.00"', false)
            ->assertSee('S/23.00', false);
    }

    public function test_payment_sum_must_match_order_total_without_side_effects(): void
    {
        [$cashier, $order, $table, $product] = $this->fixture();

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Ticket',
            'payments' => [['method' => 'card', 'amount' => 24]],
        ])->assertSessionHasErrors('payments');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('occupied', $table->fresh()->status);
        $this->assertSame('10.000', $product->fresh()->stock);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cash_closing_uses_applied_cash_not_tendered_or_card(): void
    {
        [$cashier, $order] = $this->fixture(openingAmount: 100);

        $this->actingAs($cashier)->post(route('pos.checkout', $order), [
            'document_type' => 'Ticket',
            'payments' => [
                ['method' => 'cash', 'amount' => 10, 'received_amount' => 50],
                ['method' => 'card', 'amount' => 15],
            ],
        ]);

        $this->actingAs($cashier)->post(route('cash_registers.processClose'), ['closing_amount' => 110, 'notes' => 'Arqueo exacto'])
            ->assertSessionHas('success');

        $register = CashRegister::firstOrFail();
        $this->assertSame('closed', $register->status);
        $this->assertSame('110.00', $register->expected_amount);
        $this->assertSame('0.00', $register->difference);
        $this->assertDatabaseHas('system_audits', ['action' => 'cash_register.closed', 'target' => 'cash_register:'.$register->id]);
    }

    private function fixture(float $openingAmount = 100): array
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $register = CashRegister::create(['user_id' => $cashier->id, 'opening_time' => now(), 'opening_amount' => $openingAmount, 'status' => 'open']);
        $area = Area::create(['name' => 'Caja QA']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa caja', 'seats' => 4, 'status' => 'occupied']);
        $category = Category::create(['name' => 'Carta', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Consumo', 'price' => 25, 'stock' => 10, 'is_active' => true, 'is_saleable' => true]);
        $order = Order::create(['table_id' => $table->id, 'user_id' => $cashier->id, 'cash_register_id' => $register->id, 'status' => 'pending', 'total' => 25]);
        $order->details()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 25, 'status' => 'served']);

        return [$cashier, $order, $table, $product];
    }
}
