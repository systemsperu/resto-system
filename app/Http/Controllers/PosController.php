<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Client;
use App\Models\DocumentSeries;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SunatSubmission;
use App\Models\Table;
use App\Modules\ModuleManager;
use App\Services\Marketing\LoyaltyService;
use App\Services\Restaurant\OrderWorkflowService;
use App\Services\Sunat\SunatConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index()
    {
        $areas = Area::with(['tables' => function ($q) {
            $q->where('is_active', true)->with(['assignedWaiter', 'orders' => function ($q) {
                $q->where('status', 'pending');
            }, 'reservations' => function ($q) {
                $q->where('status', 'confirmed')
                    ->whereDate('reservation_time', Carbon::today())
                    ->where('reservation_time', '>=', Carbon::now()->subHours(2))
                    ->orderBy('reservation_time', 'asc');
            }]);
        }])->get();

        $currency = Setting::where('key', 'currency_symbol')->value('value') ?? 'S/';

        return view('pos.index', compact('areas', 'currency'));
    }

    public function order(Table $table)
    {
        // Filtro: Solo productos activos y vendibles
        $categories = Category::with(['products' => function ($q) {
            $q->where('is_active', true)
                ->where('is_saleable', true);
        }])->where('is_active', true)->get();

        $order = Order::where('table_id', $table->id)->where('status', 'pending')->with(['details.product', 'waiter'])->first();
        if ($order) {
            $this->authorize('view', $order);
        }
        $occupiedTableIds = Order::where('status', 'pending')->pluck('table_id');
        $freeTables = Table::where('is_active', true)->whereNotIn('id', $occupiedTableIds)->where('id', '!=', $table->id)->with(['area', 'assignedWaiter'])->get();
        $clients = Client::select('id', 'name', 'document_number')->orderBy('name')->get();
        $currency = Setting::where('key', 'currency_symbol')->value('value') ?? 'S/';

        return view('pos.order', compact('table', 'categories', 'order', 'freeTables', 'clients', 'currency'));
    }

    // --- AGREGAR POR CLIC (Normal) ---
    public function addToOrder(Request $request, Table $table)
    {
        $this->authorizePendingOrderForTable($table);
        $product = Product::findOrFail($request->product_id);
        $this->addItemToTable($table, $product);

        return $this->getCartHtml($table);
    }

    // --- AGREGAR POR CÓDIGO DE BARRAS (Nuevo) ---
    public function addByBarcode(Request $request, Table $table)
    {
        $this->authorizePendingOrderForTable($table);
        $request->validate(['barcode' => 'required']);

        $product = Product::where('barcode', $request->barcode)
            ->where('is_active', true)
            ->where('is_saleable', true)
            ->first();

        if (! $product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $this->addItemToTable($table, $product);

        // Devolvemos el HTML actualizado
        return $this->getCartHtml($table);
    }

    // Lógica auxiliar para no repetir código al agregar
    private function addItemToTable(Table $table, Product $product)
    {
        $this->workflow->addProduct($table, $product, Auth::user());
    }

    // --- ACTUALIZAR CANTIDAD (Corregido para devolver HTML) ---
    public function updateQuantity(Request $request, OrderDetail $detail)
    {
        $this->authorize('update', $detail->order);
        $newQty = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:999']])['quantity'];
        $order = $detail->order;

        if ($detail->status !== 'pending') {
            throw ValidationException::withMessages(['detail' => 'Solo se puede cambiar una partida pendiente.']);
        }

        if ($newQty < 1) {
            $this->workflow->removeDetail($detail, $request->user());
        } else {
            $detail->update(['quantity' => $newQty]);
            $this->workflow->recalculate($order);
        }

        return $this->getCartHtml($order->table);
    }

    // --- ACTUALIZAR NOTA (Corregido para devolver HTML) ---
    public function updateNote(Request $request, OrderDetail $detail)
    {
        $this->authorize('update', $detail->order);
        if ($detail->status !== 'pending') {
            throw ValidationException::withMessages(['detail' => 'La nota ya no puede cambiarse porque cocina inició la preparación.']);
        }
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $detail->update($validated);

        return $this->getCartHtml($detail->order->table);
    }

    // --- ELIMINAR ITEM (Corregido para devolver HTML) ---
    public function removeItem(OrderDetail $detail)
    {
        $this->authorize('update', $detail->order);
        $order = $detail->order;
        $this->workflow->removeDetail($detail, Auth::user());

        return $this->getCartHtml($order->table);
    }

    // --- APLICAR DESCUENTO (Corregido para devolver HTML) ---
    public function applyDiscount(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $order->discount = $request->input('discount', 0);
        $order->tip = $request->input('tip', 0);
        $order->save();
        $this->recalculateTotal($order);

        return $this->getCartHtml($order->table);
    }

    public function moveTable(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $targetId = $request->validate(['target_table_id' => ['required', 'integer', 'exists:tables,id']])['target_table_id'];
        $moved = $this->workflow->move($order, Table::findOrFail($targetId), $request->user());

        return redirect()->route('pos.order', $moved->table_id)->with('success', 'Pedido trasladado.');
    }

    public function getSplitContent(Order $order)
    {
        $this->authorize('view', $order);

        return view('pos.partials.split_content', compact('order'));
    }

    public function processSplit(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        return redirect()->back();
    }

    public function precheck(Order $order)
    {
        $this->authorize('view', $order);
        $settings = Setting::publicValues();

        return view('sales.ticket', compact('order', 'settings'));
    }

    public function kitchenTicket(Order $order)
    {
        $this->authorize('view', $order);

        return view('sales.kitchen_ticket', compact('order'));
    }

    public function checkout(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        if ($order->status !== 'pending') {
            return redirect()->route('pos.index')->with('error', 'Orden cerrada.');
        }

        $request->validate([
            'payment_method' => ['required_without:payments', 'in:cash,card,transfer,yape,plin'],
            'document_type' => ['required', 'in:Ticket,Boleta,Factura'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array', 'min:1', 'max:5'],
            'payments.*.method' => ['required_with:payments', 'in:cash,card,transfer,yape,plin'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.received_amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_document' => ['nullable', 'string', 'max:20'],
        ]);

        $payments = $this->normalizePayments($request, $order);
        if (! $request->filled('payments') && $payments[0]['method'] === 'cash' && $payments[0]['received_amount'] < $payments[0]['amount']) {
            return back()->with('error', 'El monto recibido no cubre el total de la venta.');
        }
        $this->validatePaymentTotals($payments, (float) $order->total);
        $method = count($payments) === 1 ? $payments[0]['method'] : 'mixed';
        $received = collect($payments)->where('method', 'cash')->sum('received_amount');
        $change = collect($payments)->sum('change_amount');

        if ($order->details()->where('status', '!=', 'served')->exists()) {
            return redirect()->back()->with('error', 'No se puede cobrar mientras existan productos pendientes en cocina.');
        }

        $clientId = $request->input('client_id');
        $client = $clientId ? Client::findOrFail($clientId) : null;
        $clientName = $client?->name ?? ($request->input('client_name') ?: 'Público');
        $documentType = $request->input('document_type', 'Ticket');
        $clientDocument = $client?->document_number ?? $request->input('client_document');

        // Validación específica para Factura (Perú): requiere RUC (11 dígitos) y razón social
        if ($documentType === 'Factura') {
            $doc = preg_replace('/\D/', '', (string) $clientDocument);
            if (strlen($doc) !== 11) {
                return redirect()->back()->with('error', 'Para emitir Factura el cliente debe tener RUC de 11 dígitos.');
            }
            if (empty(trim((string) $clientName)) || $clientName === 'Público') {
                return redirect()->back()->with('error', 'Para emitir Factura debe indicar la razón social del cliente.');
            }
        }

        DB::transaction(function () use ($order, $payments, $method, $received, $change, $request, $clientId, $clientName, $documentType, $clientDocument) {
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->refresh();

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'La orden ya fue cerrada por otro usuario.']);
            }

            if ($order->details()->where('status', '!=', 'served')->exists()) {
                throw ValidationException::withMessages(['order' => 'La cocina aún no terminó todos los productos.']);
            }

            // 1. Calcular IGV (Perú 18%) si es comprobante electrónico
            $this->validatePaymentTotals($payments, (float) $order->total);
            $cashRegister = $request->user()->activeCashRegister()->lockForUpdate()->first();
            if (! $cashRegister || $cashRegister->status !== 'open') {
                throw ValidationException::withMessages(['cash_register' => 'La caja ya no está abierta.']);
            }

            $config = new SunatConfig;
            $igvFactor = $config->igvFactor();
            $isElectronic = in_array($documentType, ['Boleta', 'Factura'], true);

            $totalGravada = 0;
            $igv = 0;
            if ($isElectronic) {
                $totalGravada = round((float) $order->total / (1 + $igvFactor), 2);
                $igv = round((float) $order->total - $totalGravada, 2);
            }

            // 2. Asignar serie y correlativo si es electrónico
            $serie = null;
            $correlativo = null;
            if ($isElectronic) {
                $tipo = $documentType === 'Factura' ? 'factura' : 'boleta';
                $next = DocumentSeries::next($tipo);
                $serie = $next['serie'];
                $correlativo = $next['correlativo'];
            }

            $order->update([
                'status' => 'completed',
                'payment_method' => $method,
                'received_amount' => $received,
                'change_amount' => $change,
                'document_type' => $documentType,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'client_document' => $clientDocument,
                'cash_register_id' => $cashRegister->id,

                // SUNAT
                'serie' => $serie,
                'correlativo' => $correlativo,
                'subtotal' => $totalGravada,
                'igv' => $igv,
                'total_gravada' => $totalGravada,
                'sunat_status' => $isElectronic ? 'PENDING' : 'NOT_APPLICABLE',
            ]);

            foreach ($payments as $paymentData) {
                Payment::create([
                    ...$paymentData,
                    'order_id' => $order->id,
                    'cash_register_id' => $cashRegister->id,
                    'user_id' => $request->user()->id,
                    'status' => 'confirmed',
                    'paid_at' => now(),
                ]);
            }

            foreach ($order->details as $detail) {
                $product = $detail->product;
                $ingredients = $product->ingredients;

                if ($ingredients->count() > 0) {
                    foreach ($ingredients as $ingredient) {
                        $qtyToDeduct = $ingredient->pivot->quantity * $detail->quantity;
                        $ingredient = Product::whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
                        if (! $ingredient->track_stock || $ingredient->stock === null) {
                            continue;
                        }
                        $oldStock = $ingredient->stock;
                        if ($ingredient->track_stock && $oldStock !== null && (float) $oldStock < (float) $qtyToDeduct) {
                            throw ValidationException::withMessages(['stock' => "Stock insuficiente de {$ingredient->name}."]);
                        }
                        $ingredient->decrement('stock', $qtyToDeduct);
                        InventoryLog::create([
                            'product_id' => $ingredient->id,
                            'user_id' => Auth::id(),
                            'type' => 'sale',
                            'quantity' => -$qtyToDeduct,
                            'old_stock' => $oldStock,
                            'new_stock' => $oldStock - $qtyToDeduct,
                            'note' => 'Venta: '.$product->name.' (Orden #'.$order->id.')',
                        ]);
                    }
                } else {
                    if ($product->track_stock && ! is_null($product->stock)) {
                        $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                        $oldStock = $product->stock;
                        if ($product->track_stock && (float) $oldStock < (float) $detail->quantity) {
                            throw ValidationException::withMessages(['stock' => "Stock insuficiente de {$product->name}."]);
                        }
                        $product->decrement('stock', $detail->quantity);
                        InventoryLog::create([
                            'product_id' => $product->id,
                            'user_id' => Auth::id(),
                            'type' => 'sale',
                            'quantity' => -($detail->quantity),
                            'old_stock' => $oldStock,
                            'new_stock' => $oldStock - $detail->quantity,
                            'note' => 'Venta POS #'.$order->id,
                        ]);
                    }
                }
            }

            $order->table?->update(['status' => 'available']);
        });

        if ($order->client_id) {
            $client = Client::find($order->client_id);
            $client?->update(['last_visit_at' => now()]);
            if ($client && app(ModuleManager::class)->isEnabled('marketing')) {
                app(LoyaltyService::class)->apply($client, max(1, (int) floor((float) $order->total)), 'earn', 'order-'.$order->id, $order->id, $request->user()->id);
            }
        }

        // 3. Envío asíncrono e idempotente; nunca bloquea el cobro.
        if ($order->isElectronic()) {
            try {
                SunatSubmission::schedule($order);
            } catch (\Throwable $e) {
                Log::error('Error al enviar a SUNAT', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                // No interrumpimos la venta; queda PENDING/ERROR para reintento manual.
            }
        }

        $msg = 'Venta registrada.';
        if ($order->isElectronic()) {
            $order->refresh();
            $msg .= ' Comprobante '.$order->full_number.' - '.($order->sunat_description ?? $order->sunat_status);
        }

        $receiptRoute = $order->document_type === 'Ticket' ? 'sales.ticket' : 'billing.pdf';

        return redirect()->route($receiptRoute, $order)->with('success', $msg);
    }

    private function recalculateTotal(Order $order)
    {
        $subtotal = $order->details->sum(fn ($d) => $d->price * $d->quantity);
        $total = ($subtotal - ($order->discount ?? 0)) + ($order->tip ?? 0);
        $order->update(['total' => max(0, $total)]);
    }

    private function getCartHtml(Table $table)
    {
        $order = Order::where('table_id', $table->id)->where('status', 'pending')->with('details.product')->first();
        $clients = Client::select('id', 'name', 'document_number')->orderBy('name')->get();
        $currency = Setting::where('key', 'currency_symbol')->value('value') ?? 'S/';

        return view('pos.partials.cart', compact('order', 'clients', 'currency'))->render();
    }

    private function authorizePendingOrderForTable(Table $table): void
    {
        $order = Order::where('table_id', $table->id)->where('status', 'pending')->first();

        if ($order) {
            $this->authorize('update', $order);
        }
    }

    private function normalizePayments(Request $request, Order $order): array
    {
        $rows = $request->input('payments');
        if (! is_array($rows) || $rows === []) {
            $method = $request->input('payment_method', 'cash');
            $amount = round((float) $order->total, 2);
            $received = $method === 'cash' ? round((float) $request->input('received_amount', 0), 2) : $amount;
            $rows = [['method' => $method, 'amount' => $amount, 'received_amount' => $received]];
        }

        return collect($rows)->map(function (array $row): array {
            $amount = round((float) ($row['amount'] ?? 0), 2);
            $received = ($row['method'] ?? null) === 'cash'
                ? round((float) ($row['received_amount'] ?? 0), 2)
                : $amount;

            return [
                'method' => $row['method'] ?? '',
                'amount' => $amount,
                'received_amount' => $received,
                'change_amount' => max(0, round($received - $amount, 2)),
                'reference' => filled($row['reference'] ?? null) ? trim($row['reference']) : null,
            ];
        })->all();
    }

    private function validatePaymentTotals(array $payments, float $orderTotal): void
    {
        $paidCents = collect($payments)->sum(fn (array $payment) => (int) round($payment['amount'] * 100));
        $totalCents = (int) round($orderTotal * 100);
        if ($paidCents !== $totalCents) {
            throw ValidationException::withMessages(['payments' => 'La suma de los pagos debe coincidir exactamente con el total del pedido.']);
        }

        foreach ($payments as $index => $payment) {
            if ($payment['method'] === 'cash' && $payment['received_amount'] < $payment['amount']) {
                throw ValidationException::withMessages(["payments.$index.received_amount" => 'El efectivo recibido no cubre este pago.']);
            }
        }
    }
}
