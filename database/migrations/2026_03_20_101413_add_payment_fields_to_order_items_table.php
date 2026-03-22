<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'paid', 'pay_later'])
                    ->default('unpaid')
                    ->after('status');
            }

            if (! Schema::hasColumn('order_items', 'payment_due_at')) {
                $table->timestamp('payment_due_at')
                    ->nullable()
                    ->after('payment_status');
            }

            if (! Schema::hasColumn('order_items', 'paid_at')) {
                $table->timestamp('paid_at')
                    ->nullable()
                    ->after('payment_due_at');
            }

            // delivery_charge is the flat fee stored per item
            // (different from delivery_charge_per_km which is already on the table)
            if (! Schema::hasColumn('order_items', 'delivery_charge')) {
                $table->decimal('delivery_charge', 10, 2)
                    ->default(0)
                    ->after('subtotal')
                    ->comment('Flat delivery charge = delivery_charge_per_km * distance_km * quantity_tons');
            }

            if (! Schema::hasColumn('order_items', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)
                    ->nullable()
                    ->after('delivery_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_due_at',
                'paid_at',
                'delivery_charge',
                'distance_km',
            ]);
        });
    }
};
