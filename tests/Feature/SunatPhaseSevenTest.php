<?php

namespace Tests\Feature;

use App\Jobs\SendSunatCreditNote;
use App\Jobs\SendSunatDailySummary;
use App\Jobs\SendSunatInvoice;
use App\Models\Area;
use App\Models\Category;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\SunatSubmission;
use App\Models\Table;
use App\Models\User;
use App\Services\Sunat\DailySummaryBuilder;
use App\Services\Sunat\InvoiceBuilder;
use App\Services\Sunat\SunatConfig;
use App\Services\Sunat\SunatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SunatPhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_programacion_es_idempotente_aunque_se_solicite_dos_veces(): void
    {
        $order = $this->invoiceOrder();
        SunatSubmission::schedule($order);
        SunatSubmission::schedule($order);

        $this->assertDatabaseCount('sunat_submissions', 1);
        $this->assertDatabaseHas('sunat_submissions', ['idempotency_key' => 'Factura-F001-1', 'status' => 'pending']);
    }

    public function test_transporte_simulado_acepta_y_persiste_evidencia_sin_red(): void
    {
        Storage::fake('local');
        config(['sunat.send_enabled' => true, 'sunat.mode' => 'fake']);
        $order = $this->invoiceOrder();
        $submission = SunatSubmission::create(['order_id' => $order->id, 'document_kind' => 'invoice', 'idempotency_key' => 'Factura-F001-1', 'status' => 'queued']);

        (new SendSunatInvoice($submission->id))->handle(new SunatService(new SunatConfig([])));

        $order->refresh();
        $this->assertSame('ACCEPTED', $order->sunat_status);
        $this->assertSame('accepted', $submission->fresh()->status);
        Storage::disk('local')->assertExists($order->xml_path);
        Storage::disk('local')->assertExists($order->cdr_path);
        $this->assertDatabaseHas('system_audits', ['action' => 'sunat.send', 'status' => 'accepted']);
    }

    public function test_produccion_exige_doble_habilitacion_y_entorno_productivo(): void
    {
        config(['sunat.send_enabled' => true, 'sunat.mode' => 'production', 'sunat.production_enabled' => true]);

        $this->expectExceptionMessage('doble habilitación');
        (new SunatService(new SunatConfig([])))->sendInvoice($this->invoiceOrder());
    }

    public function test_nota_de_credito_usa_cola_y_transporte_simulado(): void
    {
        Storage::fake('local');
        config(['sunat.send_enabled' => true, 'sunat.mode' => 'fake']);
        $order = $this->invoiceOrder(['sunat_status' => 'ACCEPTED']);
        $note = CreditNote::create([
            'order_id' => $order->id, 'serie' => 'FC01', 'correlativo' => 1, 'document_type' => 'nota_credito',
            'reason_code' => '01', 'reason_description' => 'ANULACIÓN DE PRUEBA', 'subtotal' => 100, 'igv' => 18,
            'total' => 118, 'sunat_status' => 'PENDING', 'user_id' => $order->user_id,
        ]);
        $submission = SunatSubmission::create(['credit_note_id' => $note->id, 'document_kind' => 'credit_note', 'idempotency_key' => 'NotaCredito-FC01-1', 'status' => 'queued']);

        (new SendSunatCreditNote($submission->id))->handle(new SunatService(new SunatConfig([])));

        $this->assertSame('ACCEPTED', $note->fresh()->sunat_status);
        $this->assertSame('accepted', $submission->fresh()->status);
    }

    public function test_resumen_diario_obtiene_ticket_simulado_desde_cola(): void
    {
        config(['sunat.send_enabled' => true, 'sunat.mode' => 'fake']);
        $order = $this->invoiceOrder(['document_type' => 'Boleta', 'client_document' => '12345678', 'serie' => 'B001']);
        $built = (new DailySummaryBuilder(new SunatConfig([])))->build($order->created_at, $order->user_id);
        $summary = $built['model'];
        $submission = SunatSubmission::create(['daily_summary_id' => $summary->id, 'document_kind' => 'daily_summary', 'idempotency_key' => 'Resumen-'.$summary->identifier, 'status' => 'queued']);

        (new SendSunatDailySummary($submission->id))->handle(new SunatService(new SunatConfig([])));

        $this->assertSame('TICKET', $summary->fresh()->sunat_status);
        $this->assertNotEmpty($summary->fresh()->ticket);
        $this->assertSame('accepted', $submission->fresh()->status);
    }

    public function test_factura_rechaza_ruc_invalido_antes_de_firmar(): void
    {
        $order = $this->invoiceOrder(['client_document' => '12345678']);

        $this->expectExceptionMessage('RUC de 11 dígitos');
        (new InvoiceBuilder(new SunatConfig([])))->build($order->load('details.product'));
    }

    private function invoiceOrder(array $overrides = []): Order
    {
        $user = User::factory()->create(['role' => 'admin']);
        $area = Area::create(['name' => 'Fiscal']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa fiscal', 'status' => 'available']);
        $category = Category::create(['name' => 'Pruebas']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Producto prueba', 'price' => 118, 'stock' => 10]);
        $order = Order::create(array_merge([
            'table_id' => $table->id, 'user_id' => $user->id, 'status' => 'completed', 'total' => 118,
            'document_type' => 'Factura', 'client_name' => 'CLIENTE DE PRUEBA SAC', 'client_document' => '20123456789',
            'serie' => 'F001', 'correlativo' => 1, 'subtotal' => 100, 'igv' => 18, 'total_gravada' => 100, 'sunat_status' => 'PENDING',
        ], $overrides));
        OrderDetail::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 118, 'status' => 'served']);

        return $order->load('details.product');
    }
}
