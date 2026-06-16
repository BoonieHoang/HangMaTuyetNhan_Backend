<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        return response()->json(Holiday::orderBy('name')->get());
    }

    public function show($id)
    {
        return response()->json(Holiday::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'ritual_slug' => 'nullable|string|max:255',
        ]);

        $holiday = Holiday::create($validated);

        return response()->json($holiday, 201);
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'ritual_slug' => 'nullable|string|max:255',
        ]);

        $holiday->update($validated);

        return response()->json($holiday);
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
}
