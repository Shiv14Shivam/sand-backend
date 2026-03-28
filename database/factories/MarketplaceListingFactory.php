<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id'              => User::factory()->state(['role' => 'vendor']),
            'product_id'             => Product::factory(),
            'category_id'            => Category::factory(),
            'brand_id'               => Brand::factory(),
            'price_per_unit'         => $this->faker->randomFloat(2, 500, 2000),
            'delivery_charge_per_km' => $this->faker->randomFloat(2, 5, 20),
            'available_stock_unit'   => $this->faker->numberBetween(50, 500),
            'river_source'           => $this->faker->randomElement(['Narmada', 'Ganga', 'Yamuna', 'Godavari']),
            'status'                 => MarketplaceListing::STATUS_ACTIVE,
        ];
    }
}
