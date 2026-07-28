<?php

namespace App\Http\Controllers\domains;

use App\Http\Controllers\Controller;
use App\Models\DomainCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainCategoryController extends Controller
{
    public function index(): View
    {
        return view('content.domains.categories.index', [
            'categories' => DomainCategory::withCount('domains')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        DomainCategory::create($validated);

        return redirect()->route('domains.categories.index')->with('success', 'Categoría creada.');
    }

    public function update(Request $request, DomainCategory $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);

        $category->update($validated);

        return redirect()->route('domains.categories.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(DomainCategory $category): RedirectResponse
    {
        if ($category->domains()->exists()) {
            return redirect()->route('domains.categories.index')
                ->with('error', 'No se puede eliminar: hay dominios usando esta categoría.');
        }

        $category->delete();

        return redirect()->route('domains.categories.index')->with('success', 'Categoría eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?DomainCategory $category = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                'unique:domain_categories,name' . ($category ? ',' . $category->id : ''),
            ],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
