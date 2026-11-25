<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'area_id', 'assigned_employee_id', 'qr_token', 'qr_revoked_at', 'seats', 'x_pos', 'y_pos', 'status', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'qr_revoked_at' => 'datetime'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function assignedWaiter()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    // ESTA ES LA FUNCIÓN QUE FALTABA
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
