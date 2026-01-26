<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // Primary key (bigint, auto increment)

            $table->foreignId('user_id')
                  ->unique()                 // one-to-one relationship
                  ->constrained('users')     // references users.id
                  ->onDelete('cascade');     // delete customer if user is deleted

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
