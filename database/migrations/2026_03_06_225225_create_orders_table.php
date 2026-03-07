<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Who placed the order
            $table->foreignId('customer_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Delivery address snapshot (nullable — customer may not have set one)
            $table->foreignId('delivery_address_id')
                ->nullable()
                ->constrained('addresses')
                ->onDelete('set null');

            /*
            |------------------------------------------------------
            | Overall order status
            |------------------------------------------------------
            | pending            → all items awaiting vendor action
            | partially_accepted → some items accepted, some pending/declined
            | completed          → all items accepted by vendors
            | cancelled          → customer cancelled before any acceptance
            */
            $table->enum('status', [
                'pending',
                'partially_accepted',
                'completed',
                'cancelled',
            ])->default('pending');

            // Total amount = sum of all order_items subtotals (snapshot at order time)
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
