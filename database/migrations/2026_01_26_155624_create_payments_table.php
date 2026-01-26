<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();
                  // if order is deleted → payment record deleted

            $table->enum('payment_method', [
                'cash',
                'upi',
                'bank_transfer'
            ]);

            $table->string('reference_number')->nullable();
            // UPI UTR / bank reference / empty for cash

            $table->decimal('amount', 12, 2);
            // payment amount entered by vendor

            $table->boolean('verified_by_vendor')->default(false);
            // vendor confirms payment received

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};