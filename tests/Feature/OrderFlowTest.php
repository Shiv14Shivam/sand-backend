<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    /**
     * T17 — Place Direct Order
     */
    public function test_T17_customer_can_place_direct_order(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $address = Address::factory()->create(['user_id' => $customer->id]);
        $listing = MarketplaceListing::factory()->create(['status' => 'active']);

        $response = $this->actingAs($customer)->postJson('/api/orders/direct', [
            'listing_id'          => $listing->id,
            'quantity_unit'       => 5,
            'delivery_address_id' => $address->id,
            'notes'               => 'Test order',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Order placed successfully. Awaiting vendor confirmation.');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status'      => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('order_items', [
            'listing_id'    => $listing->id,
            'quantity_unit' => 5,
            'status'        => OrderItem::STATUS_PENDING,
        ]);
    }

    /**
     * T19 — Place Order — Out of Stock
     */
    public function test_T19_cannot_place_order_with_insufficient_stock(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $listing = MarketplaceListing::factory()->create([
            'status'               => 'active',
            'available_stock_unit' => 2
        ]);

        $response = $this->actingAs($customer)->postJson('/api/orders/direct', [
            'listing_id'    => $listing->id,
            'quantity_unit' => 5, // More than 2
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Insufficient stock', $response->json('message'));
    }

    /**
     * T23 — Accept Order — Sufficient Stock
     */
    public function test_T23_vendor_can_accept_order_item(): void
    {
        Notification::fake();

        $vendor = User::factory()->create(['role' => 'vendor']);
        $item = OrderItem::factory()->create([
            'vendor_id' => $vendor->id,
            'status'    => OrderItem::STATUS_PENDING
        ]);

        $response = $this->actingAs($vendor)->postJson("/api/vendor/orders/{$item->id}/accept");

        $response->assertStatus(200);
        $this->assertEquals(OrderItem::STATUS_ACCEPTED, $item->fresh()->status);
        
        // Stock should NOT be deducted yet (re-read the code)
        // Deduction happens on processing (PaymentController)
        $this->assertEquals(
            $item->listing->available_stock_unit, 
            $item->listing->fresh()->available_stock_unit
        );
    }

    /**
     * T24 — Accept Order — Insufficient Stock
     */
    public function test_T24_vendor_cannot_accept_if_stock_dropped_low(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $item = OrderItem::factory()->create([
            'vendor_id'     => $vendor->id,
            'quantity_unit' => 10,
            'status'        => OrderItem::STATUS_PENDING
        ]);

        // Manually drop listing stock below order qty
        $item->listing->update(['available_stock_unit' => 5]);

        $response = $this->actingAs($vendor)->postJson("/api/vendor/orders/{$item->id}/accept");

        $response->assertStatus(422);
        $this->assertStringContainsString('insufficient stock', $response->json('message'));
    }

    /**
     * T25 — Decline Order
     */
    public function test_T25_vendor_can_decline_order_item(): void
    {
        Notification::fake();

        $vendor = User::factory()->create(['role' => 'vendor']);
        $item = OrderItem::factory()->create([
            'vendor_id' => $vendor->id,
            'status'    => OrderItem::STATUS_PENDING
        ]);

        $response = $this->actingAs($vendor)->postJson("/api/vendor/orders/{$item->id}/decline", [
            'rejection_reason' => 'Too far'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(OrderItem::STATUS_DECLINED, $item->fresh()->status);
        $this->assertEquals('Too far', $item->fresh()->rejection_reason);
    }
}
