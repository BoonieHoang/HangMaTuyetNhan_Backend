<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ritual;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RitualController extends Controller
{
    public function index(Request $request)
    {
        $query = Ritual::query();
        return $query->latest()->paginate(15);
    }

    public function show($id)
    {
        $ritual = Ritual::findOrFail($id);
        return response()->json($ritual);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'significance' => 'nullable|string',
            'preparation' => 'nullable|string',
            'prayer_text' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();

        $ritual = Ritual::create($validated);

        return response()->json($ritual, 201);
    }

    public function update(Request $request, $id)
    {
        $ritual = Ritual::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'significance' => 'nullable|string',
            'preparation' => 'nullable|string',
            'prayer_text' => 'nullable|string',
        ]);

        if ($request->title !== $ritual->title) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        }

        $ritual->update($validated);

        return response()->json($ritual);
    }

    public function destroy($id)
    {
        $ritual = Ritual::findOrFail($id);
        $ritual->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
}
