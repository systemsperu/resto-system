# Fragmentos de código comprobados

Los extractos son breves y se limitan a los tres puntos solicitados. No constituyen el código fuente completo.

## 1. Autenticación y control de sesión

Fuente privada: `app/Http/Controllers/LoginController.php:20-35`, método `login()`.

```php
$credentials = $request->validate([
    'email' => ['required', 'email'],
    'password' => ['required'],
]);

if (Auth::attempt($credentials)) {
    $request->session()->regenerate();

    return $this->redirectUser();
}

return back()->withErrors([
    'email' => 'Las credenciales no coinciden con nuestros registros.',
])->onlyInput('email');
```

Valida los campos mínimos, intenta autenticar con Laravel y regenera el identificador de sesión para evitar fijación de sesión. La existencia del código no sustituye una prueba ejecutada.

## 2. Validación y autorización durante el pedido

Fuente privada: `app/Services/Restaurant/OrderWorkflowService.php:19-29`, método `addProduct()`.

```php
return DB::transaction(function () use ($table, $product, $actor): Order {
    $lockedTable = Table::whereKey($table->id)->lockForUpdate()->firstOrFail();
    if (! $lockedTable->is_active) {
        throw ValidationException::withMessages([
            'table' => 'La mesa está fuera de servicio.',
        ]);
    }

    $order = Order::where('table_id', $lockedTable->id)
        ->where('status', 'pending')->lockForUpdate()->first();
    if ($order) {
        abort_unless($actor->can('update', $order), 403);
    }
```

Bloquea la mesa para evitar carreras, rechaza mesas inactivas y exige la política `update` si ya existe un pedido pendiente. En el alta por clic sigue pendiente una validación explícita de `product_id` y de producto activo/vendible.

## 3. Guardado del pedido y sus detalles

Fuente privada: `app/Services/Restaurant/OrderWorkflowService.php:29-46`, método `addProduct()`.

```php
$waiter = $this->resolveWaiter($lockedTable, $actor);
$order = Order::create([
    'table_id' => $lockedTable->id,
    'user_id' => $actor->id,
    'waiter_employee_id' => $waiter?->id,
    'status' => 'pending',
    'total' => 0,
]);

$detail = $order->details()
    ->where('product_id', $product->id)
    ->where('status', 'pending')->first();

$detail ? $detail->increment('quantity') : $order->details()->create([
    'product_id' => $product->id,
    'quantity' => 1,
    'price' => $product->price,
    'status' => 'pending',
]);

$lockedTable->update(['status' => 'occupied']);
$this->recalculate($order);
```

Crea la cabecera si no existe, incrementa o crea el detalle, conserva el precio unitario, ocupa la mesa y recalcula el total dentro de la misma transacción.

