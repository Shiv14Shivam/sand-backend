<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id'         => User::factory()->state(['role' => 'customer']),
            'delivery_address_id' => Address::factory(),
            'status'              => Order::STATUS_PENDING,
            'total_amount'        => 0, // Recalculated based on items
            'notes'               => $this->faker->sentence(),
        ];
    }
}
