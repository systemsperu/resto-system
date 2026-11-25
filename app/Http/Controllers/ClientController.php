<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientConsent;
use App\Models\SystemAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        // Listamos clientes con conteo de órdenes
        $clients = Client::withCount('orders')->orderBy('name')->get();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Cliente registrado.');
    }

    // --- NUEVA FUNCIÓN: PERFIL 360 ---
    public function show(Client $client)
    {
        // 1. Historial de Órdenes (Completadas)
        $orders = $client->orders()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Estadísticas Financieras
        $totalSpent = $orders->sum('total');
        $visitCount = $orders->count();
        $lastVisit = $orders->first() ? $orders->first()->created_at : null;

        // 3. Calcular Nivel VIP
        $rank = 'Nuevo';
        $badgeColor = 'secondary';
        if ($totalSpent > 1000) {
            $rank = 'Oro (VIP)';
            $badgeColor = 'warning';
        } elseif ($totalSpent > 500) {
            $rank = 'Plata';
            $badgeColor = 'secondary';
        } elseif ($totalSpent > 100) {
            $rank = 'Bronce';
            $badgeColor = 'danger';
        }

        // 4. Plato Favorito (Query avanzada)
        $favoriteDish = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_details.quantity) as total_qty'))
            ->where('orders.client_id', $client->id)
            ->groupBy('products.name')
            ->orderByDesc('total_qty')
            ->first();

        $favoriteProduct = $favoriteDish ? $favoriteDish->name.' ('.$favoriteDish->total_qty.' veces)' : 'Aún sin datos';

        return view('clients.show', compact('client', 'orders', 'totalSpent', 'visitCount', 'lastVisit', 'rank', 'badgeColor', 'favoriteProduct'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request, $client));

        return redirect()->route('clients.index')->with('success', 'Datos actualizados.');
    }

    public function destroy(Client $client)
    {
        if ($client->orders()->exists()) {
            return back()->with('error', 'El cliente tiene historial y no puede eliminarse; puede retirarse su consentimiento.');
        }
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }

    public function consent(Request $request, Client $client)
    {
        $data = $request->validate(['channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])], 'granted' => ['required', 'boolean']]);
        ClientConsent::create([...$data, 'client_id' => $client->id, 'source' => 'dashboard', 'recorded_at' => now(), 'recorded_by' => $request->user()->id]);
        SystemAudit::create(['user_id' => $request->user()->id, 'action' => 'client.consent', 'target' => 'client:'.$client->id, 'status' => $data['granted'] ? 'granted' : 'revoked', 'metadata' => ['channel' => $data['channel']]]);

        return back()->with('success', 'Preferencia de contacto actualizada.');
    }

    private function validated(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', Rule::in(['DNI', 'RUC', 'CE', 'OTRO'])],
            'document_number' => ['nullable', 'string', 'max:20', Rule::unique('clients')->ignore($client?->id)],
            'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'], 'birth_date' => ['nullable', 'date', 'before:today'],
            'source' => ['nullable', Rule::in(['manual', 'pos', 'delivery', 'reservation', 'web'])],
            'preferences' => ['nullable', 'array'],
        ]);
    }
}
