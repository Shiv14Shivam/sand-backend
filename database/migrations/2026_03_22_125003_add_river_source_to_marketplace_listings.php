<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->string('river_source')->nullable()->after('available_stock_unit');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropColumn('river_source');
        });
    }
};
