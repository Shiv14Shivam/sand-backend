<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->onDelete('cascade');

            // The marketplace listing this item came from (for stock deduction)
            $table->foreignId('listing_id')
                ->constrained('marketplace_listings')
                ->onDelete('restrict'); // Don't allow listing deletion if orders reference it

            // Denormalized vendor FK — so vendor can query their own order items directly
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Denormalized product FK — for display/history even if listing is soft-deleted
            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('restrict');

            // Quantity requested by customer (in bags)
            $table->integer('quantity_bags');

            /*
            |------------------------------------------------------
            | Price snapshot at time of order placement
            |------------------------------------------------------
            | We snapshot pricing so changing listing prices later
            | doesn't affect already-placed orders.
            */
            $table->decimal('price_per_bag', 10, 2);
            $table->decimal('delivery_charge_per_ton', 10, 2)->default(0);

            // quantity_bags × price_per_bag
            $table->decimal('subtotal', 12, 2);

            /*
            |------------------------------------------------------
            | Per-item vendor status
            |------------------------------------------------------
            | pending  → vendor hasn't acted yet
            | accepted → vendor accepted; stock deducted
            | declined → vendor declined; stock NOT deducted
            */
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');

            $table->text('rejection_reason')->nullable();

            // When vendor acted
            $table->timestamp('actioned_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
