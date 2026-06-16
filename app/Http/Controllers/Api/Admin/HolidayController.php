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
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'ritual_slug' => 'nullable|array',
            'ritual_slug.*.slug'  => 'required|string',
            'ritual_slug.*.title' => 'required|string',
        ]);

        $holiday = Holiday::create([
            'name'        => $request->name,
            'description' => $request->description,
            'ritual_slug' => $request->ritual_slug ?? [],
        ]);

        return response()->json($holiday, 201);
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'ritual_slug' => 'nullable|array',
            'ritual_slug.*.slug'  => 'required|string',
            'ritual_slug.*.title' => 'required|string',
        ]);

        $holiday->update([
            'name'        => $request->input('name', $holiday->name),
            'description' => $request->description,
            'ritual_slug' => $request->ritual_slug ?? [],
        ]);

        return response()->json($holiday->fresh());
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
}
