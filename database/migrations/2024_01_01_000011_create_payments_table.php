<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->enum('payment_method', ['cod', 'bank_transfer']);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->string('transfer_content', 200)->nullable();
            $table->string('qr_url', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
