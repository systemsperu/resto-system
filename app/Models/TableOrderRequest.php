<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableOrderRequest extends Model
{
    protected $fillable = ['table_id', 'confirmed_by', 'order_id', 'public_id', 'customer_name', 'items', 'notes', 'status', 'confirmed_at', 'cancelled_at'];

    protected $casts = ['items' => 'array', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
