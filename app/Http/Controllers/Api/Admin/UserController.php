<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return UserResource::collection($query->withCount('orders')->latest()->paginate(15));
    }

    public function show($id)
    {
        $user = User::withCount('orders')->findOrFail($id);
        $totalSpent = $user->orders()->where('status', 'delivered')->sum('total_amount');
        
        $resource = new UserResource($user);
        $resource->additional([
            'orders_count' => $user->orders_count,
            'total_spent' => (float) $totalSpent,
        ]);

        return $resource;
    }

    public function lockToggle(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            abort(403, 'Bạn không thể tự khóa tài khoản của mình.');
        }

        $user->status = $user->status === 'active' ? 'locked' : 'active';
        $user->save();

        return new UserResource($user);
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            abort(403, 'Bạn không thể tự đổi quyền của mình.');
        }

        $request->validate([
            'role' => 'required|in:admin,customer',
        ]);

        $user->role = $request->role;
        $user->save();

        return new UserResource($user);
    }
}
