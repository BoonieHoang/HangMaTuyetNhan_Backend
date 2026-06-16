<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to modify the column to text to avoid doctrine/dbal package dependency in Laravel 9
        DB::statement('ALTER TABLE holidays MODIFY COLUMN ritual_slug TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE holidays MODIFY COLUMN ritual_slug VARCHAR(255) NULL');
    }
};
