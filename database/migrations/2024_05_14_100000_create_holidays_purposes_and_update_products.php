<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign keys to products table (holidays and purposes tables already exist)
        Schema::table('products', function (Blueprint $table) {
            // Drop old string-based holiday column if exists
            if (Schema::hasColumn('products', 'holiday')) {
                $table->dropColumn('holiday');
            }

            // Add foreign key columns (only if not already added)
            if (!Schema::hasColumn('products', 'holiday_id')) {
                $table->foreignId('holiday_id')->nullable()->after('type')
                      ->constrained('holidays')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'purpose_id')) {
                $table->foreignId('purpose_id')->nullable()->after('holiday_id')
                      ->constrained('purposes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'holiday_id')) {
                $table->dropForeign(['holiday_id']);
                $table->dropColumn('holiday_id');
            }
            if (Schema::hasColumn('products', 'purpose_id')) {
                $table->dropForeign(['purpose_id']);
                $table->dropColumn('purpose_id');
            }
            // Restore old column
            $table->string('holiday')->nullable()->after('type');
        });
    }
};
