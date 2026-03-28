<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    /**
     * T12 — Browse Marketplace
     */
    public function test_T12_user_can_view_active_listings(): void
    {
        // One active listing
        MarketplaceListing::factory()->create(['status' => 'active']);
        // One inactive listing
        MarketplaceListing::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/marketplace');

        $response->assertStatus(200);
        // Only active listing should be visible
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * T13 — Add Item to Cart
     */
    public function test_T13_customer_can_add_item_to_cart(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $listing = MarketplaceListing::factory()->create(['status' => 'active', 'available_stock_unit' => 100]);

        $response = $this->actingAs($customer)->postJson('/api/cart', [
            'listing_id'    => $listing->id,
            'quantity_unit' => 10,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Item added to cart.');

        $this->assertDatabaseHas('carts', [
            'user_id'       => $customer->id,
            'listing_id'    => $listing->id,
            'quantity_unit' => 10,
        ]);
    }

    /**
     * T14 — Update Cart Quantity
     */
    public function test_T14_customer_can_update_cart_quantity(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $cartItem = Cart::factory()->create([
            'user_id'       => $customer->id,
            'quantity_unit' => 5,
        ]);

        $response = $this->actingAs($customer)->putJson("/api/cart/{$cartItem->id}", [
            'listing_id'    => $cartItem->listing_id,
            'quantity_unit' => 15,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(15, $cartItem->fresh()->quantity_unit);
    }

    /**
     * T15 — Remove Cart Item
     */
    public function test_T15_customer_can_remove_item_from_cart(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $cartItem = Cart::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer)->deleteJson("/api/cart/{$cartItem->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('carts', ['id' => $cartItem->id]);
    }

    /**
     * T16 — Clear Entire Cart
     */
    public function test_T16_customer_can_clear_cart(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Cart::factory()->count(3)->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer)->deleteJson('/api/cart/clear');

        $response->assertStatus(200);
        $this->assertEquals(0, Cart::where('user_id', $customer->id)->count());
    }

    /**
     * Guard Test: Insufficient Stock
     */
    public function test_cannot_add_more_than_available_stock(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $listing = MarketplaceListing::factory()->create([
            'status'               => 'active',
            'available_stock_unit' => 10
        ]);

        $response = $this->actingAs($customer)->postJson('/api/cart', [
            'listing_id'    => $listing->id,
            'quantity_unit' => 11, // More than 10
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Insufficient stock.');
    }

    /**
     * Guard Test: Cannot buy own listing
     */
    public function test_vendor_cannot_add_own_listing_to_cart(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $listing = MarketplaceListing::factory()->create([
            'seller_id' => $vendor->id,
            'status'    => 'active'
        ]);

        $response = $this->actingAs($vendor)->postJson('/api/cart', [
            'listing_id'    => $listing->id,
            'quantity_unit' => 1,
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'You cannot add your own listing to the cart.');
    }
}
