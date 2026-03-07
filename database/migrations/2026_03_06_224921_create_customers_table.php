<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Type of customer — mirrors vendor's business_type
            $table->enum('customer_type', [
                'individual',   // Single person buying for personal use / small site
                'contractor',   // Construction contractor
                'builder',      // Real estate / builder
                'dealer',       // Reseller / dealer
            ])->default('individual');

            // Optional company name (for non-individual customers)
            $table->string('company_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
