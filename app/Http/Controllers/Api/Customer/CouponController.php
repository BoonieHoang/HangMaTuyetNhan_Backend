<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    /**
     * Danh sách mã giảm giá của người dùng hiện tại.
     */
    public function index(Request $request)
    {
        $userCoupons = UserCoupon::with('coupon')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($userCoupons->map(function ($uc) {
            return [
                'id'              => $uc->id,
                'is_used'         => $uc->is_used,
                'used_at'         => $uc->used_at,
                'obtained_at'     => $uc->created_at,
                'coupon'          => [
                    'id'               => $uc->coupon->id,
                    'code'             => $uc->coupon->code,
                    'description'      => $uc->coupon->description,
                    'discount_type'    => $uc->coupon->discount_type,
                    'discount_value'   => $uc->coupon->discount_value,
                    'min_order_amount' => $uc->coupon->min_order_amount,
                    'max_discount'     => $uc->coupon->max_discount,
                    'expires_at'       => $uc->coupon->expires_at,
                    'is_valid'         => $uc->coupon->isValid() && !$uc->is_used,
                ],
            ];
        }));
    }

    /**
     * Đổi điểm công đức lấy mã giảm giá.
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
        ]);

        $user   = $request->user();
        $coupon = Coupon::findOrFail($request->coupon_id);

        if (!$coupon->isValid()) {
            return response()->json(['message' => 'Mã giảm giá này không còn hiệu lực hoặc đã hết lượt sử dụng.'], 422);
        }

        if ($user->merit_points < $coupon->points_cost) {
            return response()->json([
                'message'        => 'Bạn không đủ điểm công đức để đổi mã này.',
                'your_points'    => $user->merit_points,
                'required_points' => $coupon->points_cost,
            ], 422);
        }

        // Kiểm tra người dùng đã có mã này chưa (chưa sử dụng)
        $alreadyHas = UserCoupon::where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('is_used', false)
            ->exists();

        if ($alreadyHas) {
            return response()->json(['message' => 'Bạn đang có mã giảm giá này chưa sử dụng.'], 422);
        }

        DB::transaction(function () use ($user, $coupon) {
            $user->decrement('merit_points', $coupon->points_cost);
            UserCoupon::create([
                'user_id'   => $user->id,
                'coupon_id' => $coupon->id,
            ]);
        });

        return response()->json([
            'message'             => 'Đổi mã giảm giá thành công!',
            'remaining_points'    => $user->fresh()->merit_points,
            'coupon_code'         => $coupon->code,
        ]);
    }

    /**
     * Xem danh sách mã có thể đổi (marketplace).
     */
    public function available(Request $request)
    {
        $coupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderBy('points_cost')
            ->get();

        $user = $request->user();

        return response()->json([
            'your_points' => $user->merit_points,
            'coupons'     => $coupons->map(function ($c) use ($user) {
                return [
                    'id'               => $c->id,
                    'code'             => $c->code,
                    'description'      => $c->description,
                    'discount_type'    => $c->discount_type,
                    'discount_value'   => $c->discount_value,
                    'min_order_amount' => $c->min_order_amount,
                    'max_discount'     => $c->max_discount,
                    'points_cost'      => $c->points_cost,
                    'expires_at'       => $c->expires_at,
                    'can_redeem'       => $user->merit_points >= $c->points_cost,
                ];
            }),
        ]);
    }

    /**
     * Xác minh mã giảm giá khi checkout.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $user = $request->user();

        $userCoupon = UserCoupon::with('coupon')
            ->where('user_id', $user->id)
            ->where('is_used', false)
            ->whereHas('coupon', fn($q) => $q->where('code', strtoupper(trim($request->code))))
            ->first();

        if (!$userCoupon) {
            return response()->json(['message' => 'Mã giảm giá không hợp lệ hoặc không thuộc về bạn.'], 422);
        }

        $coupon = $userCoupon->coupon;

        if (!$coupon->isValid()) {
            return response()->json(['message' => 'Mã giảm giá đã hết hạn hoặc không còn hiệu lực.'], 422);
        }

        if ($request->subtotal < $coupon->min_order_amount) {
            return response()->json([
                'message' => "Đơn hàng tối thiểu " . number_format($coupon->min_order_amount) . "đ để sử dụng mã này.",
            ], 422);
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        return response()->json([
            'valid'           => true,
            'coupon_id'       => $coupon->id,
            'code'            => $coupon->code,
            'description'     => $coupon->description,
            'discount_amount' => $discount,
        ]);
    }
}
