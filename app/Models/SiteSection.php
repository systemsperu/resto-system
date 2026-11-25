<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSection extends Model
{
    public const TYPES = ['hero', 'text', 'features', 'gallery', 'menu', 'contact'];

    protected $fillable = ['site_page_id', 'type', 'heading', 'body', 'image_path', 'action_label', 'action_url', 'options', 'position', 'is_visible'];

    protected $casts = ['options' => 'array', 'is_visible' => 'boolean'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }
}
