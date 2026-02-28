<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q');

        if (!$q || strlen($q) < 2) {
            return response()->json([]);
        }

        $cities = City::where('name', 'like', $q . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
