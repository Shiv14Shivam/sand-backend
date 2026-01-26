<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->restrictOnDelete();
                  // prevent deleting customer with orders

            $table->foreignId('vendor_id')
                  ->constrained('vendors')
                  ->restrictOnDelete();
                  // vendor cannot be deleted if orders exist

            $table->enum('order_status', [
                'pending',
                'confirmed',
                'shipped',
                'delivered',
                'cancelled'
            ])->default('pending');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            $table->decimal('total_amount', 12, 2);
            // supports large order totals safely

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
