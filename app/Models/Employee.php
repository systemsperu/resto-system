<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'full_name', 'document_type', 'document_number', 'phone',
        'position', 'hired_at', 'is_active', 'notes',
    ];

    protected $casts = ['hired_at' => 'date', 'is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(WorkShift::class);
    }

    public function assignedTables(): HasMany
    {
        return $this->hasMany(Table::class, 'assigned_employee_id');
    }
}
