<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'holidays', 'purposes']);

        if ($request->filled('category_id')) {
            $query->filterCategory($request->category_id);
        }

        if ($request->filled('holiday_id')) {
            $query->filterHoliday($request->holiday_id);
        }

        if ($request->filled('purpose_id')) {
            $query->filterPurpose($request->purpose_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->where('stock', '<=', 10)->where('stock', '>', 0);
            } elseif ($request->stock_status === 'out') {
                $query->where('stock', '<=', 0);
            }
        }

        return ProductResource::collection($query->paginate(15));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);

        if (empty($data['sku'])) {
            $data['sku'] = strtoupper(Str::slug($data['name'])) . '-' . strtoupper(Str::random(4));
        }

        $product = Product::create($data);

        if ($request->has('holiday_ids')) {
            $product->holidays()->sync($request->holiday_ids);
        }
        if ($request->has('purpose_ids')) {
            $product->purposes()->sync($request->purpose_ids);
        }

        return new ProductResource($product->load(['category', 'images', 'holidays', 'purposes']));
    }

    public function show($id)
    {
        $product = Product::with(['category', 'images', 'holidays', 'purposes'])->findOrFail($id);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();
        
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
            if (empty($data['sku'])) {
                $data['sku'] = strtoupper(Str::slug($data['name'])) . '-' . strtoupper(Str::random(4));
            }
        }

        $product->update($data);

        if ($request->has('holiday_ids')) {
            $product->holidays()->sync($request->holiday_ids);
        }
        if ($request->has('purpose_ids')) {
            $product->purposes()->sync($request->purpose_ids);
        }

        return new ProductResource($product->load(['category', 'images', 'holidays', 'purposes']));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Check if product has orders
        if ($product->orderItems()->exists()) {
            // If it has orders, we can't hard delete. We just hide it.
            $product->update(['is_active' => false]);
            return response()->json(['message' => 'Sản phẩm đã có đơn hàng nên không thể xóa vĩnh viễn. Hệ thống đã tự động chuyển sang trạng thái Ngừng bán.'], 200);
        }

        // Delete all images from Cloudinary first
        foreach ($product->images as $image) {
            if ($publicId = $this->cloudinaryService->extractPublicId($image->image_url)) {
                $this->cloudinaryService->delete($publicId);
            }
        }

        $product->delete();

        return response()->noContent();
    }

    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'image|max:2048',
            'is_primary' => 'nullable|integer',
        ]);

        foreach ($request->file('files') as $index => $file) {
            $url = $this->cloudinaryService->upload($file, 'products');

            $product->images()->create([
                'image_url' => $url,
                'is_primary' => ($request->is_primary !== null && (int)$request->is_primary === $index),
                'sort_order' => $index,
            ]);
        }

        return new ProductResource($product->load(['category', 'images', 'holidays', 'purposes']));
    }

    public function deleteImage($id, $imageId)
    {
        $product = Product::findOrFail($id);
        $image = $product->images()->findOrFail($imageId);
        $wasPrimary = $image->is_primary;
        
        if ($publicId = $this->cloudinaryService->extractPublicId($image->image_url)) {
            $this->cloudinaryService->delete($publicId);
        }

        $image->delete();

        // If the deleted image was primary, set another one as primary
        if ($wasPrimary) {
            $nextImage = $product->images()->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return response()->noContent();
    }
    public function setPrimaryImage($id, $imageId)
    {
        $product = Product::findOrFail($id);
        
        // Reset all
        $product->images()->update(['is_primary' => false]);
        
        // Set new primary
        $image = $product->images()->findOrFail($imageId);
        $image->update(['is_primary' => true]);

        return new ProductResource($product->load('images'));
    }
}
