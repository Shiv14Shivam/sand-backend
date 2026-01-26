<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('user_id')
                  ->unique()                 // one vendor per user
                  ->constrained('users')     // FK → users.id
                  ->onDelete('cascade');

            $table->string('business_name');

            $table->string('gst_number', 15)->unique();
            $table->string('pan_number', 10)->unique();

            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->decimal('rating', 2, 1)->default(0.0);
            // e.g. 0.0 → 5.0

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};