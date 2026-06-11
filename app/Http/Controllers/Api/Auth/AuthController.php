<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Mail\VerifyEmailCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
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
        ]);

        $code = rand(100000, 999999);
        Cache::put('email_verification_code_' . $user->id, $code, now()->addMinutes(15));
        \Log::info("Verification code for user ID {$user->id} ({$user->email}): {$code}");
        @file_put_contents(storage_path('logs/verification_codes.txt'), "[" . date('Y-m-d H:i:s') . "] Register - User ID {$user->id} ({$user->email}): {$code}\n", FILE_APPEND);

        try {
            Mail::to($user->email)->send(new VerifyEmailCode($code, $user->fullname));
        } catch (\Exception $e) {
            // Log mail exception but do not crash the request in local/demo setup
            \Log::warning('Verify email fail to send: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng nhập mã xác thực gửi đến email của bạn.',
            'user_id' => $user->id,
            'email' => $user->email,
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string|size:6',
        ], [
            'user_id.required' => 'Mã định danh người dùng là bắt buộc.',
            'user_id.exists' => 'Không tìm thấy người dùng này.',
            'code.required' => 'Vui lòng nhập mã xác thực.',
            'code.size' => 'Mã xác thực phải đúng 6 chữ số.',
        ]);

        $userId = $request->user_id;
        $cachedCode = Cache::get('email_verification_code_' . $userId);

        if (!$cachedCode || $cachedCode != $request->code) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác thực không chính xác hoặc đã hết hạn.'],
            ]);
        }

        $user = User::find($userId);
        $user->email_verified_at = now();
        $user->save();

        Cache::forget('email_verification_code_' . $userId);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Xác thực tài khoản thành công.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Tài khoản này đã được xác thực.'], 400);
        }

        $code = rand(100000, 999999);
        Cache::put('email_verification_code_' . $user->id, $code, now()->addMinutes(15));
        \Log::info("Resend verification code for user ID {$user->id} ({$user->email}): {$code}");
        @file_put_contents(storage_path('logs/verification_codes.txt'), "[" . date('Y-m-d H:i:s') . "] Resend - User ID {$user->id} ({$user->email}): {$code}\n", FILE_APPEND);

        try {
            Mail::to($user->email)->send(new VerifyEmailCode($code, $user->fullname));
        } catch (\Exception $e) {
            \Log::warning('Resend email fail to send: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đã gửi lại mã xác thực tới email của bạn.',
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

        if (is_null($user->email_verified_at)) {
            $code = rand(100000, 999999);
            Cache::put('email_verification_code_' . $user->id, $code, now()->addMinutes(15));
            \Log::info("Login verification code for user ID {$user->id} ({$user->email}): {$code}");
            @file_put_contents(storage_path('logs/verification_codes.txt'), "[" . date('Y-m-d H:i:s') . "] Login - User ID {$user->id} ({$user->email}): {$code}\n", FILE_APPEND);
            try {
                Mail::to($user->email)->send(new VerifyEmailCode($code, $user->fullname));
            } catch (\Exception $e) {
                \Log::warning('Resend code on login fail to send: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'unverified',
                'message' => 'Tài khoản chưa được xác thực email. Một mã xác thực mới đã được gửi tới email của bạn.',
                'user_id' => $user->id,
                'email' => $user->email,
            ], 403);
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