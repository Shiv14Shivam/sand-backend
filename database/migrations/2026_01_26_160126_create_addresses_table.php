<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
                  // delete addresses if user is deleted

            $table->string('name');
            // person/site name for delivery

            $table->string('phone', 15);

            $table->text('address_line');

            $table->string('city');

            $table->string('state');

            $table->string('pincode', 10);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};