<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $this->operate($user, $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $order->status === 'pending' && $this->operate($user, $order);
    }

    private function operate(User $user, Order $order): bool
    {
        if (in_array($user->role, ['admin', 'cashier'], true)) {
            return true;
        }

        if ($user->role !== 'waiter') {
            return false;
        }

        if ($order->waiter_employee_id) {
            return $user->employee?->id === $order->waiter_employee_id
                && (bool) $user->employee?->is_active;
        }

        // Compatibilidad temporal para pedidos anteriores a F3.3.
        return $order->user_id === $user->id;
    }
}
