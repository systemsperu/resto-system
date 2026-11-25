<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\Restaurant\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index(): View
    {
        $orders = Order::where('status', 'pending')
            ->whereHas('details', fn ($query) => $query->whereIn('status', ['pending', 'cooking']))
            ->with(['table', 'waiter', 'details' => fn ($query) => $query->whereIn('status', ['pending', 'cooking'])->with('product')])
            ->orderBy('created_at')
            ->get();

        return view('kitchen.index', compact('orders'));
    }

    public function updateStatus(Request $request, OrderDetail $detail): RedirectResponse
    {
        $expected = $request->validate(['expected_status' => ['required', 'in:pending,cooking']])['expected_status'];
        if ($detail->status !== $expected) {
            return back()->withErrors(['detail' => 'El estado cambió en otro dispositivo. Actualiza el monitor.']);
        }

        $this->workflow->advanceKitchen($detail, $request->user());

        return redirect()->route('kitchen.index')->with('success', 'Estado de cocina actualizado.');
    }
}
