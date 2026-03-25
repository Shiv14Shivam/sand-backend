<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Customer-selected pay later window in days (1–7)
            $table->unsignedTinyInteger('days_requested')
                ->nullable()
                ->after('payment_due_at')
                ->comment('Number of days customer requested for pay later (1–7)');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('days_requested');
        });
    }
};
