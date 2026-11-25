<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    protected $fillable = ['name', 'min_selections', 'max_selections', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function options()
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
