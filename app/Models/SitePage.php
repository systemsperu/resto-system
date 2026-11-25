<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitePage extends Model
{
    protected $fillable = ['location_id', 'title', 'slug', 'navigation_label', 'meta_title', 'meta_description', 'show_in_navigation', 'is_home', 'published_at'];

    protected $casts = ['show_in_navigation' => 'boolean', 'is_home' => 'boolean', 'published_at' => 'datetime'];

    public function sections(): HasMany
    {
        return $this->hasMany(SiteSection::class)->orderBy('position');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
