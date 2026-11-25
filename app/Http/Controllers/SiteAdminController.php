<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSitePageRequest;
use App\Http\Requests\StoreSiteSectionRequest;
use App\Models\Location;
use App\Models\Product;
use App\Models\SitePage;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteAdminController extends Controller
{
    public function index(): View
    {
        return view('site.admin', ['pages' => SitePage::with('sections')->orderByDesc('is_home')->orderBy('title')->get(), 'locations' => Location::with('products')->orderBy('name')->get(), 'products' => Product::where('is_saleable', true)->orderBy('name')->get()]);
    }

    public function storePage(StoreSitePageRequest $request): RedirectResponse
    {
        $data = $this->pageData($request);
        DB::transaction(function () use ($data): void {
            if ($data['is_home']) {
                SitePage::query()->update(['is_home' => false]);
            }
            SitePage::create($data);
        });

        return back()->with('success', 'Página creada correctamente.');
    }

    public function updatePage(StoreSitePageRequest $request, SitePage $page): RedirectResponse
    {
        $data = $this->pageData($request);
        DB::transaction(function () use ($data, $page): void {
            if ($data['is_home']) {
                SitePage::whereKeyNot($page->getKey())->update(['is_home' => false]);
            }
            $page->update($data);
        });

        return back()->with('success', 'Página actualizada correctamente.');
    }

    public function storeSection(StoreSiteSectionRequest $request, SitePage $page): RedirectResponse
    {
        $page->sections()->create([...$request->validated(), 'is_visible' => $request->boolean('is_visible')]);

        return back()->with('success', 'Sección agregada correctamente.');
    }

    public function destroySection(SiteSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Sección eliminada correctamente.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        Location::create($request->validate(['name' => ['required', 'string', 'max:120'], 'slug' => ['required', 'alpha_dash', 'max:120', 'unique:locations'], 'address' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'schedule' => ['nullable', 'string', 'max:255']]) + ['is_active' => true]);

        return back()->with('success', 'Sede creada correctamente.');
    }

    public function updateLocationMenu(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate(['products' => ['array'], 'products.*.enabled' => ['sometimes', 'boolean'], 'products.*.price' => ['nullable', 'numeric', 'min:0'], 'products.*.promotional_price' => ['nullable', 'numeric', 'min:0']]);
        $sync = collect($data['products'] ?? [])->filter(fn ($item) => ! empty($item['enabled']))->mapWithKeys(fn ($item, $id) => [(int) $id => ['price' => $item['price'] ?: null, 'promotional_price' => $item['promotional_price'] ?: null, 'is_available' => true]])->all();
        $location->products()->sync($sync);

        return back()->with('success', 'Carta de la sede actualizada.');
    }

    private function pageData(StoreSitePageRequest $request): array
    {
        return [...$request->validated(), 'show_in_navigation' => $request->boolean('show_in_navigation'), 'is_home' => $request->boolean('is_home'), 'published_at' => $request->boolean('published') ? now() : null];
    }
}
