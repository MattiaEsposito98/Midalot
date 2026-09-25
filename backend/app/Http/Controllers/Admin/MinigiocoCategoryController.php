<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinigiocoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinigiocoCategoryController extends Controller
{
    public function index()
    {
        $categories = MinigiocoCategory::withCount('minigiochi')
            ->orderBy('name')
            ->get();

        return view('admin.minigiochi.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:minigioco_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $validated['slug'] = $this->uniqueSlug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('covers', 'public');
        }

        MinigiocoCategory::create($validated);

        return back()->with('success', 'Categoria minigiochi creata.');
    }

    public function update(Request $request, MinigiocoCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:minigioco_categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($category->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);
        }

        if ($request->has('remove_image') && $category->image_path) {
            Storage::disk('public')->delete($category->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('covers', 'public');
        }

        $category->update($validated);

        return back()->with('success', 'Categoria minigiochi aggiornata.');
    }

    public function destroy(MinigiocoCategory $category)
    {
        if ($category->minigiochi()->exists()) {
            return back()->withErrors([
                'category' => 'Non puoi eliminare una categoria che contiene minigiochi.',
            ]);
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return back()->with('success', 'Categoria minigiochi eliminata.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            MinigiocoCategory::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
