<?php

namespace App\Http\Controllers;

use App\Branding\BrandSettings;
use App\Models\SitePage;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(BrandSettings $branding, Request $request): View
    {
        $page = SitePage::published()->where('is_home', true)->with(['sections' => fn ($query) => $query->where('is_visible', true)])->firstOrFail();

        return $this->render($page, $branding, $request);
    }

    public function show(SitePage $page, BrandSettings $branding, Request $request): View
    {
        abort_unless($page->published_at?->isPast(), 404);
        $page->load(['sections' => fn ($query) => $query->where('is_visible', true)]);

        return $this->render($page, $branding, $request);
    }

    private function render(SitePage $page, BrandSettings $branding, Request $request): View
    {
        $navigation = SitePage::published()->where('show_in_navigation', true)->orderByDesc('is_home')->orderBy('title')->get();
        if ($request->cookie('analytics_consent') === 'yes') {
            SiteVisit::create(['site_page_id' => $page->id, 'location_id' => $page->location_id, 'path' => '/'.$request->path(), 'visitor_hash' => hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), config('app.key')), 'referrer_host' => parse_url((string) $request->headers->get('referer'), PHP_URL_HOST), 'device_type' => preg_match('/Mobile|Android|iPhone/i', (string) $request->userAgent()) ? 'mobile' : 'desktop', 'visited_on' => today()]);
        }

        return view('site.show', ['page' => $page, 'navigation' => $navigation, 'brand' => $branding->get()]);
    }
}
