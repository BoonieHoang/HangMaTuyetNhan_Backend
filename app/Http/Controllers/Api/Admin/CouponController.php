<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'description'      => 'nullable|string|max:255',
            'discount_type'    => 'required|in:fixed,percent',
            'discount_value'   => 'required|numeric|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'points_cost'      => 'required|integer|min:1',
            'usage_limit'      => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
            'expires_at'       => 'nullable|date|after:now',
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        $coupon = Coupon::create($data);
        return response()->json($coupon, 201);
    }

    public function show($id)
    {
        return response()->json(Coupon::withCount('userCoupons')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $data = $request->validate([
            'code'             => 'sometimes|string|max:50|unique:coupons,code,' . $id,
            'description'      => 'nullable|string|max:255',
            'discount_type'    => 'sometimes|in:fixed,percent',
            'discount_value'   => 'sometimes|numeric|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'points_cost'      => 'sometimes|integer|min:1',
            'usage_limit'      => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
            'expires_at'       => 'nullable|date',
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $coupon->update($data);
        return response()->json($coupon);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);

        if ($coupon->used_count > 0) {
            return response()->json([
                'message' => 'Không thể xóa mã giảm giá đã được sử dụng.'
            ], 422);
        }

        $coupon->delete();
        return response()->json(['message' => 'Xóa mã giảm giá thành công.']);
    }
}
