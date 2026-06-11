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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\VerifyEmailCode;

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

        try {
            Mail::to($user->email)->send(new VerifyEmailCode($code));
        } catch (\Exception $e) {
            Log::error('Send verification email failed on registration: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để nhận mã xác thực.',
            'user_id' => $user->id,
            'email' => $user->email,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        if ($user->status === 'locked') {
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa, mời liên hệ chủ cửa hàng để biết thêm chi tiết.'], 403);
        }

        if (is_null($user->email_verified_at)) {
            $code = rand(100000, 999999);
            Cache::put('email_verification_code_' . $user->id, $code, now()->addMinutes(15));

            try {
                Mail::to($user->email)->send(new VerifyEmailCode($code));
            } catch (\Exception $e) {
                Log::error('Send verification email failed on login check: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Tài khoản chưa được xác thực email. Mã xác thực mới đã được gửi tới email của bạn.',
                'unverified' => true,
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'code.required' => 'Mã xác thực không được để trống.',
            'code.size' => 'Mã xác thực phải gồm 6 chữ số.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Không tìm thấy tài khoản với email này.'],
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Tài khoản của bạn đã được xác thực trước đó.'], 200);
        }

        $cachedCode = Cache::get('email_verification_code_' . $user->id);

        if (! $cachedCode || $cachedCode != $request->code) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác thực không chính xác hoặc đã hết hạn.'],
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        Cache::forget('email_verification_code_' . $user->id);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Xác thực tài khoản thành công.',
            'token' => $token,
            'user' => new UserResource($user->refresh()),
        ]);
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Không tìm thấy tài khoản với email này.'],
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Tài khoản đã được xác thực trước đó.'], 400);
        }

        $code = rand(100000, 999999);
        Cache::put('email_verification_code_' . $user->id, $code, now()->addMinutes(15));

        try {
            Mail::to($user->email)->send(new VerifyEmailCode($code));
        } catch (\Exception $e) {
            Log::error('Send verification email failed on resend: ' . $e->getMessage());
            return response()->json(['message' => 'Không thể gửi email xác thực. Vui lòng thử lại sau.'], 500);
        }

        return response()->json([
            'message' => 'Mã xác thực mới đã được gửi tới email của bạn.',
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