<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        $data = $request->validated();
        
        if (!empty($data['password'])) {
            $isGoogleUserWithoutOldPassword = !empty($user->google_id) && empty($data['old_password']);
            
            if (!$isGoogleUserWithoutOldPassword) {
                if (empty($data['old_password']) || !\Hash::check($data['old_password'], $user->password)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'old_password' => ['Mật khẩu hiện tại không chính xác.'],
                    ]);
                }
            }
            $data['password'] = \Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        unset($data['old_password']);
        
        $user->update($data);

        return new UserResource($user);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            $publicId = $this->cloudinaryService->extractPublicId($user->avatar);
            if ($publicId) {
                $this->cloudinaryService->delete($publicId);
            }
        }

        $url = $this->cloudinaryService->upload($request->file('avatar'), 'avatars');
        
        $user->update(['avatar' => $url]);

        return new UserResource($user);
    }
}
