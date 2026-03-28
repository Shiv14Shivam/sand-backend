<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        return [
            'category_id' => Category::factory(),
            'name'        => $name,
            'slug'        => Str::slug($name),
            'logo'        => 'brand-logo.png',
            'description' => $this->faker->sentence(),
            'is_active'   => true,
            'sort_order'  => 0,
        ];
    }
}
