<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'label'          => $this->faker->randomElement(['Home', 'Work', 'Construction Site']),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->secondaryAddress(),
            'city'           => $this->faker->city(),
            'state'          => $this->faker->state(),
            'pincode'        => $this->faker->postcode(),
            'latitude'       => $this->faker->latitude(19.0, 21.0),
            'longitude'      => $this->faker->longitude(72.0, 74.0),
            'is_default'     => false,
        ];
    }
}
