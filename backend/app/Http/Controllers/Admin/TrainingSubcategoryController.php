<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrainingSubcategoryController extends Controller
{
    public function store(Request $request, TrainingCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $validated['training_category_id'] = $category->id;
        $validated['slug'] = $this->uniqueSlug($category->id, $validated['name']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('covers', 'public');
        }

        TrainingSubcategory::create($validated);

        return back()->with('success', 'Sottocategoria creata.');
    }

    public function update(Request $request, TrainingSubcategory $subcategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($subcategory->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($subcategory->training_category_id, $validated['name'], $subcategory->id);
        }

        if ($request->has('remove_image') && $subcategory->image_path) {
            Storage::disk('public')->delete($subcategory->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($subcategory->image_path) {
                Storage::disk('public')->delete($subcategory->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('covers', 'public');
        }

        $subcategory->update($validated);

        return back()->with('success', 'Sottocategoria aggiornata.');
    }

    public function destroy(TrainingSubcategory $subcategory)
    {
        if ($subcategory->quizzes()->exists()) {
            return back()->withErrors([
                'subcategory' => 'Non puoi eliminare una sottocategoria che contiene training quiz.',
            ]);
        }

        if ($subcategory->image_path) {
            Storage::disk('public')->delete($subcategory->image_path);
        }

        $subcategory->delete();

        return back()->with('success', 'Sottocategoria eliminata.');
    }

    private function uniqueSlug(int $categoryId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            TrainingSubcategory::where('training_category_id', $categoryId)
                ->where('slug', $slug)
                ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
