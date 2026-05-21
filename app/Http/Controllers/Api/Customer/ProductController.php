<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with(['category', 'images', 'holidays', 'purposes']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->filterCategory($request->category_id);
        }

        if ($request->filled('holiday_id')) {
            $query->filterHoliday($request->holiday_id);
        }

        if ($request->filled('purpose_id')) {
            $query->filterPurpose($request->purpose_id);
        }

        if ($request->filled('price_min')) {
            $query->filterPrice($request->price_min, null);
        }

        if ($request->filled('price_max')) {
            $query->filterPrice(null, $request->price_max);
        }



        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
                case 'newest':
                    $query->latest();
                    break;
                default:
                    $query->orderBy('views', 'desc');
                    break;
            }
        } else {
            $query->orderBy('views', 'desc');
        }

        return new ProductCollection($query->paginate(12));
    }

    public function show($slug)
    {
        $product = Product::active()
            ->with(['category', 'images', 'holidays', 'purposes'])
            ->where('slug', $slug)
            ->firstOrFail();
        
        $product->increment('views');

        return new ProductResource($product);
    }
}
