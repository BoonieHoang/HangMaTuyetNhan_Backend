<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    // Read-only: no CRUD needed, admin manages via phpMyAdmin
    public function index()
    {
        return response()->json(Holiday::orderBy('name')->get());
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
            'ritual_slug' => 'nullable|string|max:255',
        ]);

        $holiday->update($validated);

        return response()->json($holiday);
    }
}
