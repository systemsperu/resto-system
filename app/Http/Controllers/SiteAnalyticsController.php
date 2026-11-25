<?php

namespace App\Http\Controllers;

use App\Models\SiteVisit;
use Illuminate\View\View;

class SiteAnalyticsController extends Controller
{
    public function index(): View
    {
        $daily = SiteVisit::selectRaw('visited_on, count(*) as views, count(distinct visitor_hash) as visitors')->where('visited_on', '>=', now()->subDays(30)->toDateString())->groupBy('visited_on')->orderBy('visited_on')->get();
        $pages = SiteVisit::selectRaw('path, count(*) as views')->groupBy('path')->orderByDesc('views')->limit(20)->get();

        return view('site.analytics', compact('daily', 'pages'));
    }
}
