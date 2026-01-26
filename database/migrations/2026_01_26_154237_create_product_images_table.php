<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
                  // delete images if product is deleted

            $table->string('image_url');
            // stores relative or full URL

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
