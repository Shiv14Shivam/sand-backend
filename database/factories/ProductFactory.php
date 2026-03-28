<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'category_id' => Category::factory(),
            'brand_id'    => Brand::factory(),
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name),
            'unit'        => 'ton',
            'unit_weight' => 1000,
            'is_active'   => true,
            'sort_order'  => 0,
        ];
    }
}
