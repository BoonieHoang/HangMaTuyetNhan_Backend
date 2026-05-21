<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create product_holiday pivot
        if (!Schema::hasTable('product_holiday')) {
            Schema::create('product_holiday', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 2. Create product_purpose pivot
        if (!Schema::hasTable('product_purpose')) {
            Schema::create('product_purpose', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('purpose_id')->constrained('purposes')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 3. Remove foreign key columns from products table
        if (Schema::hasColumn('products', 'holiday_id')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropForeign(['holiday_id']);
                });
            } catch (\Exception $e) {
                // Ignore if foreign key constraint does not exist
            }
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('holiday_id');
                });
            } catch (\Exception $e) {
                // Ignore if column cannot be dropped
            }
        }

        if (Schema::hasColumn('products', 'purpose_id')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropForeign(['purpose_id']);
                });
            } catch (\Exception $e) {
                // Ignore if foreign key constraint does not exist
            }
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('purpose_id');
                });
            } catch (\Exception $e) {
                // Ignore if column cannot be dropped
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('holiday_id')->nullable()->constrained('holidays')->nullOnDelete();
            $table->foreignId('purpose_id')->nullable()->constrained('purposes')->nullOnDelete();
        });

        Schema::dropIfExists('product_purpose');
        Schema::dropIfExists('product_holiday');
    }
};
