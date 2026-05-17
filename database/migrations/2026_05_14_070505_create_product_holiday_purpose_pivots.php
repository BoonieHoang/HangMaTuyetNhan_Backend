<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create product_holiday pivot
        Schema::create('product_holiday', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Create product_purpose pivot
        Schema::create('product_purpose', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('purpose_id')->constrained('purposes')->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Remove foreign key columns from products table
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'holiday_id')) {
                $table->dropForeign(['holiday_id']);
                $table->dropColumn('holiday_id');
            }
            if (Schema::hasColumn('products', 'purpose_id')) {
                $table->dropForeign(['purpose_id']);
                $table->dropColumn('purpose_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('holiday_id')->nullable()->after('type')->constrained('holidays')->nullOnDelete();
            $table->foreignId('purpose_id')->nullable()->after('holiday_id')->constrained('purposes')->nullOnDelete();
        });

        Schema::dropIfExists('product_purpose');
        Schema::dropIfExists('product_holiday');
    }
};
