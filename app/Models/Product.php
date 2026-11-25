<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'barcode', // <--- NUEVO CAMPO AGREGADO
        'description',
        'price',
        'cost',
        'image',
        'category_id',
        'stock',
        'unit',
        'track_stock',
        'low_stock_threshold',
        'is_active',
        'is_saleable',
        'promotional_price',
        'is_chef_recommendation',
        'is_new',
    ];

    protected $casts = [
        'stock' => 'decimal:3', 'low_stock_threshold' => 'decimal:3',
        'track_stock' => 'boolean', 'is_active' => 'boolean', 'is_saleable' => 'boolean',
    ];

    // Relación con Categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relación con Ingredientes (Para el descuento de inventario)
    public function ingredients()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients', 'product_id', 'ingredient_id')
            ->withPivot('quantity');
    }

    // Relación con Detalles de Orden
    // Relación con Detalles de Orden
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class)->withPivot(['price', 'promotional_price', 'is_available']);
    }

    // Atributo dinámico: Costo de Receta (Escandallo)
    public function getRecipeCostAttribute()
    {
        if ($this->ingredients->isEmpty()) {
            return $this->cost; // Si no tiene receta, su costo es el costo asignado manualmente
        }

        return $this->ingredients->sum(function ($ingredient) {
            return $ingredient->cost * $ingredient->pivot->quantity;
        });
    }
}
