<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công. Bạn có thể đăng nhập ngay.',
            'user_id' => $user->id,
            'email' => $user->email,
        ], 201);
    }

    public function verifyEmailSignature(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(config('app.frontend_url') . '/login.html?error=invalid_verification_link');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(config('app.frontend_url') . '/login.html?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return redirect(config('app.frontend_url') . '/login.html?verified=1');
    }

    public function resendVerificationNotification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email này đã được xác thực.'], 400);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            \Log::warning('Resend email verification notification fail: ' . $e->getMessage());
            return response()->json(['message' => 'Gửi email thất bại, vui lòng thử lại sau.'], 500);
        }

        return response()->json([
            'message' => 'Đã gửi lại link xác thực tới email của bạn. Vui lòng kiểm tra hộp thư.',
        ]);
    }

    public function login(LoginRequest $request)
    {
        $loginInput = $request->login ?? $request->phone;

        $user = null;
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginInput)->first();
        } else {
            $user = User::where('phone', $loginInput)->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Thông tin đăng nhập không chính xác.'],
                'login' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        if ($user->status === 'locked') {
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa, mời liên hệ chủ cửa hàng để biết thêm chi tiết.'], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        } else {
            $request->user()->tokens()->delete();
        }

        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}