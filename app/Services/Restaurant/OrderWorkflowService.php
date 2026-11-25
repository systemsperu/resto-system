<?php

namespace App\Services\Restaurant;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\SystemAudit;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function addProduct(Table $table, Product $product, User $actor): Order
    {
        return DB::transaction(function () use ($table, $product, $actor): Order {
            $lockedTable = Table::whereKey($table->id)->lockForUpdate()->firstOrFail();
            if (! $lockedTable->is_active) {
                throw ValidationException::withMessages(['table' => 'La mesa está fuera de servicio.']);
            }

            $order = Order::where('table_id', $lockedTable->id)->where('status', 'pending')->lockForUpdate()->first();
            if ($order) {
                abort_unless($actor->can('update', $order), 403);
            } else {
                $waiter = $this->resolveWaiter($lockedTable, $actor);
                $order = Order::create([
                    'table_id' => $lockedTable->id,
                    'user_id' => $actor->id,
                    'waiter_employee_id' => $waiter?->id,
                    'status' => 'pending',
                    'total' => 0,
                ]);
            }

            $detail = $order->details()->where('product_id', $product->id)->where('status', 'pending')->first();
            $detail ? $detail->increment('quantity') : $order->details()->create([
                'product_id' => $product->id, 'quantity' => 1, 'price' => $product->price, 'status' => 'pending',
            ]);
            $lockedTable->update(['status' => 'occupied']);
            $this->recalculate($order);

            return $order->refresh();
        });
    }

    public function removeDetail(OrderDetail $detail, User $actor): ?Order
    {
        return DB::transaction(function () use ($detail, $actor): ?Order {
            $lockedDetail = OrderDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($lockedDetail->order_id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->can('update', $order), 403);
            if ($lockedDetail->status !== 'pending') {
                throw ValidationException::withMessages(['detail' => 'Solo se pueden retirar productos pendientes de preparación.']);
            }

            $lockedDetail->delete();
            if (! $order->details()->exists()) {
                $order->update(['status' => 'cancelled', 'total' => 0]);
                $order->table?->update(['status' => 'available']);
                $this->audit($actor, 'order.cancelled_empty', $order);

                return null;
            }

            $this->recalculate($order);

            return $order->refresh();
        });
    }

    public function move(Order $order, Table $target, User $actor): Order
    {
        return DB::transaction(function () use ($order, $target, $actor): Order {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->can('update', $lockedOrder), 403);
            $sourceId = $lockedOrder->table_id;
            if ($sourceId === $target->id) {
                throw ValidationException::withMessages(['target_table_id' => 'Selecciona una mesa diferente.']);
            }

            $tables = Table::whereIn('id', [$sourceId, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $tables->get($sourceId);
            $destination = $tables->get($target->id);
            if (! $destination?->is_active) {
                throw ValidationException::withMessages(['target_table_id' => 'La mesa de destino está fuera de servicio.']);
            }
            if (Order::where('table_id', $destination->id)->where('status', 'pending')->where('id', '!=', $lockedOrder->id)->exists()) {
                throw ValidationException::withMessages(['target_table_id' => 'La mesa de destino está ocupada.']);
            }

            $lockedOrder->update(['table_id' => $destination->id]);
            $source?->update(['status' => 'available']);
            $destination->update(['status' => 'occupied']);
            $this->audit($actor, 'order.table_moved', $lockedOrder, ['from' => $sourceId, 'to' => $destination->id]);

            return $lockedOrder->refresh();
        });
    }

    public function advanceKitchen(OrderDetail $detail, User $actor): OrderDetail
    {
        return DB::transaction(function () use ($detail, $actor): OrderDetail {
            $locked = OrderDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'La orden ya no está activa.']);
            }

            $next = match ($locked->status) {
                'pending' => 'cooking',
                'cooking' => 'served',
                default => throw ValidationException::withMessages(['detail' => 'El producto ya fue marcado como servido.']),
            };
            $previous = $locked->status;
            $locked->update(['status' => $next]);
            $this->audit($actor, 'order_detail.status_changed', $order, ['detail_id' => $locked->id, 'from' => $previous, 'to' => $next]);

            return $locked->refresh();
        });
    }

    public function recalculate(Order $order): void
    {
        $subtotal = $order->details()->get()->sum(fn (OrderDetail $detail) => $detail->price * $detail->quantity);
        $order->update(['total' => max(0, ($subtotal - ($order->discount ?? 0)) + ($order->tip ?? 0))]);
    }

    private function resolveWaiter(Table $table, User $actor): ?Employee
    {
        if ($actor->role !== 'waiter') {
            return $table->assignedWaiter;
        }

        $employee = $actor->employee;
        if (! $employee || ! $employee->is_active || $employee->position !== 'waiter') {
            throw ValidationException::withMessages(['waiter' => 'Tu cuenta necesita una ficha activa de mozo para abrir pedidos.']);
        }
        if ($table->assigned_employee_id && $table->assigned_employee_id !== $employee->id) {
            throw ValidationException::withMessages(['table' => 'La mesa está asignada a otro mozo.']);
        }
        if (! $table->assigned_employee_id) {
            $table->update(['assigned_employee_id' => $employee->id]);
        }

        return $employee;
    }

    private function audit(User $actor, string $action, Order $order, array $metadata = []): void
    {
        SystemAudit::create(['user_id' => $actor->id, 'action' => $action, 'target' => 'order:'.$order->id, 'status' => 'success', 'metadata' => $metadata]);
    }
}
