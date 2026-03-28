<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        return [
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name),
            'icon'        => 'category',
            'icon_color'  => $this->faker->safeHexColor(),
            'description' => $this->faker->sentence(),
            'is_active'   => true,
            'sort_order'  => 0,
        ];
    }
}
