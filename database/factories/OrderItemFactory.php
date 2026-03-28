<?php

namespace Database\Factories;

use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(5, 50);
        $price    = $this->faker->randomFloat(2, 500, 1500);
        $perKm    = $this->faker->randomFloat(2, 5, 20);
        $dist     = $this->faker->randomFloat(2, 10, 100);

        return [
            'order_id'               => Order::factory(),
            'listing_id'             => MarketplaceListing::factory(),
            'vendor_id'              => User::factory()->state(['role' => 'vendor']),
            'product_id'             => Product::factory(),
            'quantity_unit'          => $quantity,
            'price_per_unit'         => $price,
            'delivery_charge_per_km' => $perKm,
            'distance_km'            => $dist,
            'delivery_charge'        => round($perKm * $dist * $quantity, 2),
            'subtotal'               => round($price * $quantity, 2),
            'status'                 => OrderItem::STATUS_PENDING,
            'payment_status'         => OrderItem::PAYMENT_UNPAID,
        ];
    }
}
