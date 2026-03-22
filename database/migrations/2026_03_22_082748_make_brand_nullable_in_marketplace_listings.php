<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable(false)->change();
        });
    }
};
