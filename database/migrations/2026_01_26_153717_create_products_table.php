<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('vendor_id')
                  ->constrained('vendors')
                  ->cascadeOnDelete();
                  // if vendor is deleted → delete products

            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->restrictOnDelete();
                  // prevent deleting category if products exist

            $table->string('name');

            $table->text('description')->nullable();

            $table->decimal('price', 10, 2);
            // supports large prices safely (₹, $, etc.)

            $table->string('unit');
            // e.g. kg, ton, bag, piece, truckload

            $table->integer('stock_quantity')->default(0);

            $table->boolean('is_approved')->default(false);
            // admin approval control

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
