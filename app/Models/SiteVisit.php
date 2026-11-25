<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = ['site_page_id', 'location_id', 'path', 'visitor_hash', 'referrer_host', 'device_type', 'visited_on'];

    protected $casts = ['visited_on' => 'date'];
}
