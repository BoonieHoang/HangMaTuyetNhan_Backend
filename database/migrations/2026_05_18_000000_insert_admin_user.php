<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if an admin or this phone/email already exists to prevent duplicates
        $adminExists = DB::table('users')
            ->where('role', 'admin')
            ->orWhere('phone', '0332852924')
            ->orWhere('email', 'admin@gmail.com')
            ->exists();

        if (!$adminExists) {
            DB::table('users')->insert([
                'fullname' => 'Chủ quán',
                'email' => 'admin@gmail.com',
                'phone' => '0332852924',
                'gender' => 'female',
                'birthday' => '2004-02-18',
                'password' => Hash::make('123456789'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin@gmail.com')
            ->orWhere('phone', '0332852924')
            ->delete();
    }
};
