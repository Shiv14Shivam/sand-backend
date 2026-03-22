<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');

            // Pricing
            $table->decimal('price_per_unit', 10, 2);           // price per unit (50kg)
            $table->decimal('delivery_charge_per_km', 10, 2)->default(0);
            $table->integer('available_stock_unit')->default(0); // stock in unit

            // Status
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Soft-delete & timestamps
            $table->timestamps();
            $table->softDeletes();

            // A seller can only have one active listing per product at a time
            $table->unique(['seller_id', 'product_id', 'deleted_at'], 'unique_seller_product_listing');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
