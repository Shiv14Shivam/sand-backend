<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();
                  // delete items if order is deleted

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->restrictOnDelete();
                  // prevent deleting product used in orders

            $table->integer('quantity');

            $table->decimal('price', 10, 2);
            // unit price at order time
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};