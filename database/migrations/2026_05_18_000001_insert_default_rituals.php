<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run the RitualSeeder automatically during deployment migrations
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\RitualSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional down action
    }
};
