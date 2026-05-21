<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index(Request $request)
    {
        $query = Category::query()->withCount('products');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return CategoryResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        
        if ($request->hasFile('image')) {
            $data['image'] = $this->cloudinaryService->upload($request->file('image'), 'categories');
        }

        $category = Category::create($data);

        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        $category->loadCount('products');
        return new CategoryResource($category);
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('image')) {
            // Optional: delete old image from Cloudinary
            if ($category->image && $publicId = $this->cloudinaryService->extractPublicId($category->image)) {
                $this->cloudinaryService->delete($publicId);
            }
            $data['image'] = $this->cloudinaryService->upload($request->file('image'), 'categories');
        }

        $category->update($data);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            abort(422, 'Category has products');
        }

        // Delete image from Cloudinary
        if ($category->image && $publicId = $this->cloudinaryService->extractPublicId($category->image)) {
            $this->cloudinaryService->delete($publicId);
        }

        $category->delete();

        return response()->noContent();
    }
}
