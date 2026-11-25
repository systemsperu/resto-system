<?php

namespace App\Http\Controllers;

use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModifierGroupController extends Controller
{
    public function index(): View
    {
        $groups = ModifierGroup::with(['options', 'products'])->orderBy('name')->get();
        $products = Product::where('is_active', true)->where('is_saleable', true)->orderBy('name')->get();

        return view('modifiers.index', compact('groups', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_selections' => ['required', 'integer', 'min:0', 'max:20'],
            'max_selections' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_selections'],
            'products' => ['nullable', 'array'], 'products.*' => ['integer', 'exists:products,id'],
        ]);
        $group = ModifierGroup::create($data);
        $group->products()->sync($data['products'] ?? []);

        return back()->with('success', 'Grupo de modificadores creado.');
    }

    public function update(Request $request, ModifierGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_selections' => ['required', 'integer', 'min:0', 'max:20'],
            'max_selections' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_selections'],
            'products' => ['nullable', 'array'], 'products.*' => ['integer', 'exists:products,id'],
        ]);
        $group->update($data);
        $group->products()->sync($data['products'] ?? []);

        return back()->with('success', 'Grupo actualizado.');
    }

    public function storeOption(Request $request, ModifierGroup $group): RedirectResponse
    {
        $group->options()->create($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price_delta' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ]));

        return back()->with('success', 'Opción agregada.');
    }

    public function destroyOption(ModifierOption $option): RedirectResponse
    {
        $option->delete();

        return back()->with('success', 'Opción eliminada.');
    }

    public function destroy(ModifierGroup $group): RedirectResponse
    {
        $group->delete();

        return back()->with('success', 'Grupo eliminado.');
    }
}
