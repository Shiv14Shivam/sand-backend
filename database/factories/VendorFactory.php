<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory()->state(['role' => 'vendor']),
            'firm_name'     => $this->faker->company() . ' Sands',
            'business_type' => $this->faker->randomElement(['Individual', 'Partnership', 'Private Limited']),
            'gst_number'    => $this->faker->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
        ];
    }
}
