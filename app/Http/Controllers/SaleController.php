<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Setting; // Importamos el modelo
use Carbon\Carbon;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // Filtro de rango de fechas
        $startDate = $request->input('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        // 1. Obtener Ventas
        $orders = Order::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->with(['user', 'payments'])
            ->get();

        // 2. Totales Ventas
        $payments = $orders->flatMap->payments->where('status', 'confirmed');
        $totalCash = $payments->where('method', 'cash')->sum('amount');
        $totalCard = $payments->where('method', 'card')->sum('amount');
        $totalSales = $payments->sum('amount');

        // 3. Obtener Gastos (Lista y Total)
        $expenses = Expense::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // 4. Balance Final
        $balance = $totalCash - $totalExpenses;

        return view('sales.index', compact(
            'orders', 'expenses', 'startDate', 'endDate',
            'totalCash', 'totalCard', 'totalSales', 'totalExpenses', 'balance'
        ));
    }

    public function ticket(Order $order)
    {
        $settings = Setting::publicValues();
        $settings['currency_symbol'] = $settings['currency_symbol'] ?? 'S/';

        return view('sales.ticket', compact('order', 'settings'));
    }

    public function dailyReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $orders = Order::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->with('payments')
            ->get();

        $payments = $orders->flatMap->payments->where('status', 'confirmed');

        $stats = [
            'start_date' => Carbon::parse($startDate),
            'end_date' => Carbon::parse($endDate),
            'cash' => $payments->where('method', 'cash')->sum('amount'),
            'card' => $payments->where('method', 'card')->sum('amount'),
            'digital' => $payments->whereIn('method', ['transfer', 'yape', 'plin'])->sum('amount'),
            'orders_count' => $orders->count(),
            'expenses' => 0,
        ];

        if (class_exists('\App\Models\Expense')) {
            $stats['expenses'] = Expense::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->sum('amount');
        }

        $stats['total'] = $payments->sum('amount');
        $stats['balance'] = $stats['cash'] - $stats['expenses'];

        $settings = Setting::publicValues();

        return view('sales.daily_report', compact('stats', 'settings'));
    }
}
