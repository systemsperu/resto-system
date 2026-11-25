<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Table;
use App\Models\TableOrderRequest;
use App\Services\Restaurant\OrderWorkflowService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicTableOrderController extends Controller
{
    public function show(string $token): View
    {
        $table = Table::where('qr_token', $token)->whereNull('qr_revoked_at')->where('is_active', true)->firstOrFail();
        $products = Product::with('category')->where('is_active', true)->where('is_saleable', true)->orderBy('name')->get();

        return view('table-order.show', compact('table', 'products'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $table = Table::where('qr_token', $token)->whereNull('qr_revoked_at')->where('is_active', true)->firstOrFail();
        $request->merge(['items' => collect($request->input('items', []))->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)->values()->all()]);
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:30'], 'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->where('is_active', true)->where('is_saleable', true)->get()->keyBy('id');
        abort_unless($products->count() === count($data['items']), 422);
        $items = collect($data['items'])->map(fn ($item) => ['product_id' => (int) $item['product_id'], 'name' => $products[$item['product_id']]->name, 'quantity' => (int) $item['quantity'], 'price' => (string) $products[$item['product_id']]->price])->all();
        $orderRequest = TableOrderRequest::create([...$data, 'items' => $items, 'table_id' => $table->id, 'public_id' => (string) Str::uuid(), 'status' => 'pending']);

        return redirect()->route('table-order.status', $orderRequest->public_id)->with('success', 'Solicitud enviada al mozo. Cocina la recibirá después de su confirmación.');
    }

    public function status(string $publicId): View
    {
        return view('table-order.status', ['orderRequest' => TableOrderRequest::where('public_id', $publicId)->with('table')->firstOrFail()]);
    }

    public function pending(): View
    {
        return view('table-order.pending', ['requests' => TableOrderRequest::where('status', 'pending')->with('table.area')->oldest()->get()]);
    }

    public function confirm(TableOrderRequest $orderRequest, Request $request, OrderWorkflowService $workflow): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->role === 'admin' || $orderRequest->table->assigned_employee_id === $actor->employee?->id, 403);
        DB::transaction(function () use ($orderRequest, $actor, $workflow): void {
            $locked = TableOrderRequest::whereKey($orderRequest)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 409);
            $order = null;
            foreach ($locked->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                for ($quantity = 0; $quantity < $item['quantity']; $quantity++) {
                    $order = $workflow->addProduct($locked->table, $product, $actor);
                }
            }
            $locked->update(['status' => 'confirmed', 'confirmed_by' => $actor->id, 'confirmed_at' => now(), 'order_id' => $order?->id]);
        });

        return back()->with('success', 'Solicitud confirmada y enviada a cocina.');
    }

    public function cancel(TableOrderRequest $orderRequest, Request $request): RedirectResponse
    {
        abort_unless($orderRequest->status === 'pending', 409);
        $orderRequest->update(['status' => 'cancelled', 'cancelled_at' => now(), 'confirmed_by' => $request->user()->id]);

        return back()->with('success', 'Solicitud cancelada.');
    }

    public function rotate(Table $table): RedirectResponse
    {
        $table->update(['qr_token' => (string) Str::uuid(), 'qr_revoked_at' => null]);

        return back()->with('success', 'QR de mesa generado.');
    }

    public function qr(Table $table): Response
    {
        abort_unless($table->qr_token && ! $table->qr_revoked_at, 404);
        $result = (new PngWriter)->write(new QrCode(route('table-order.show', $table->qr_token), size: 360, margin: 16));

        return response($result->getString(), 200, ['Content-Type' => $result->getMimeType(), 'Cache-Control' => 'private, max-age=3600']);
    }
}
