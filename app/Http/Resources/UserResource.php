<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'address' => $this->address,
            'gender' => $this->gender,
            'birthday' => $this->birthday ? ($this->birthday instanceof \DateTimeInterface ? $this->birthday->format('Y-m-d') : substr($this->birthday, 0, 10)) : null,
            'merit_points' => $this->merit_points,
            'email_verified_at' => $this->email_verified_at,
            'google_id' => $this->google_id,
            'has_password' => !is_null($this->password),
            'created_at' => $this->created_at,
            'orders_count' => $this->whenCounted('orders'),
        ];
    }
}
