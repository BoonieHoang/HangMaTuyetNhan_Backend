<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ritual;
use Illuminate\Http\Request;

class RitualController extends Controller
{
    public function index(Request $request)
    {
        $query = Ritual::query();

        return response()->json($query->latest()->get());
    }

    public function show($slug)
    {
        $ritual = Ritual::where('slug', $slug)
            ->firstOrFail();

        $ritual->increment('views');

        return response()->json($ritual);
    }
}
