<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    protected $fillable = ['accommodation_unit_id', 'lodging_reservation_id', 'assigned_employee_id', 'completed_by', 'task_type', 'status', 'scheduled_for', 'notes', 'completed_at'];

    protected $casts = ['scheduled_for' => 'date', 'completed_at' => 'datetime'];

    public function unit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'accommodation_unit_id');
    }

    public function reservation()
    {
        return $this->belongsTo(LodgingReservation::class, 'lodging_reservation_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}
