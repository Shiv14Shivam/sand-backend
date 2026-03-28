<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory()->state(['role' => 'customer']),
            'customer_type' => $this->faker->randomElement(['Individual', 'Company']),
            'company_name'  => $this->faker->optional(0.3)->company(),
        ];
    }
}
