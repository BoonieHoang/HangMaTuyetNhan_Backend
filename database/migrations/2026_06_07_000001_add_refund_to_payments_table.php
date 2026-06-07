<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Change status from enum to string to support more statuses without enum migration issues
            $table->string('status', 50)->default('pending')->change();
            
            // Add refund details
            $table->string('refund_reason', 255)->nullable()->after('paid_at');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refund_reason', 'refunded_at']);
        });
    }
};
