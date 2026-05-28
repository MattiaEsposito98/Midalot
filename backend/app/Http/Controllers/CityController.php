<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:80'],
        ]);

        $q = trim($validated['q'] ?? '');

        if ($q === '') {
            return response()->json([]);
        }

        $cities = City::where('name', 'like', $q . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
