<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        // Listamos productos con su categoría, ordenados por los más nuevos
        $products = Product::with(['category', 'ingredients'])->orderBy('created_at', 'desc')->paginate(10);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // 1. Validación (Incluye el barcode único)
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'promotional_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:50|unique:products,barcode', // <--- NUEVO
            'image' => 'nullable|image|max:2048',
            'stock' => 'nullable|numeric|min:0',
            'unit' => 'required|in:unit,kg,g,l,ml,portion',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();

        // 2. Manejo de Imagen
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // Checkbox de "Disponible en POS" (si no viene, es false)
        $data['is_saleable'] = $request->has('is_saleable');
        $data['track_stock'] = $request->has('track_stock');
        $data['is_chef_recommendation'] = $request->has('is_chef_recommendation');
        $data['is_new'] = $request->has('is_new');
        $data['is_active'] = true;
        $data['cost'] = $request->cost ?? 0;

        // 3. Crear Producto
        $product = Product::create($data);

        // 4. Registro inicial en Kardex si hay stock
        if ($request->stock > 0) {
            InventoryLog::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'entry',
                'quantity' => $request->stock,
                'old_stock' => 0,
                'new_stock' => $request->stock,
                'note' => 'Inventario Inicial',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        // Productos que pueden ser insumos (todos menos él mismo)
        $ingredients = Product::where('id', '!=', $product->id)->where('is_active', true)->get();

        return view('products.edit', compact('product', 'categories', 'ingredients'));
    }

    public function update(Request $request, Product $product)
    {
        // 1. Validación (Barcode único excepto para este producto)
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'promotional_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:50|unique:products,barcode,'.$product->id, // <--- NUEVO
            'image' => 'nullable|image|max:2048',
            'unit' => 'required|in:unit,kg,g,l,ml,portion',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();

        // 2. Manejo de Imagen
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['is_saleable'] = $request->has('is_saleable');
        $data['track_stock'] = $request->has('track_stock');
        $data['is_chef_recommendation'] = $request->has('is_chef_recommendation');
        $data['is_new'] = $request->has('is_new');
        $data['cost'] = $request->cost ?? 0;

        // 3. Actualizar
        $product->update($data);

        // Actualizar receta/insumos (Si enviaste array de ingredientes)
        if ($request->has('ingredients')) {
            $syncData = [];
            foreach ($request->ingredients as $id => $qty) {
                if ($qty > 0) {
                    $syncData[$id] = ['quantity' => $qty];
                }
            }
            $product->ingredients()->sync($syncData);
        }

        return redirect()->route('products.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        // Eliminado lógico (desactivar) en lugar de borrar para mantener historial
        $product->update(['is_active' => false]);

        return redirect()->route('products.index')->with('success', 'Producto eliminado (desactivado).');
    }

    // Funciones extra para ajustes rápidos
    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back();
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0'],
            'type' => ['required', 'in:add,sub,waste'],
            'note' => ['nullable', 'string', 'max:255', 'required_if:type,waste'],
        ]);

        DB::transaction(function () use ($product, $validated): void {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! $locked->track_stock || $locked->stock === null) {
                throw ValidationException::withMessages(['quantity' => 'Este producto no controla existencias.']);
            }
            $oldStock = (float) $locked->stock;
            $quantity = round((float) $validated['quantity'], 3);
            $isOutput = in_array($validated['type'], ['sub', 'waste'], true);
            $newStock = round($oldStock + ($isOutput ? -$quantity : $quantity), 3);
            if ($newStock < 0) {
                throw ValidationException::withMessages(['quantity' => 'El ajuste dejaría el stock en negativo.']);
            }

            $locked->update(['stock' => $newStock]);
            InventoryLog::create([
                'product_id' => $locked->id, 'user_id' => Auth::id(),
                'type' => $validated['type'] === 'waste' ? 'waste' : ($isOutput ? 'adjustment_out' : 'adjustment_in'),
                'quantity' => $isOutput ? -$quantity : $quantity,
                'old_stock' => $oldStock, 'new_stock' => $newStock,
                'note' => $validated['note'] ?? 'Ajuste manual desde panel',
            ]);
        });

        return back()->with('success', 'Stock ajustado.');
    }
}
