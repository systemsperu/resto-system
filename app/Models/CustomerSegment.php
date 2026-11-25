<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    protected $fillable = ['name', 'description', 'rules', 'is_active'];

    protected $casts = ['rules' => 'array', 'is_active' => 'boolean'];

    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }
}
