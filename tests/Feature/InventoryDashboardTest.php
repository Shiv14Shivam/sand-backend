<?php

namespace Tests\Feature;

use App\Models\MarketplaceListing;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    /**
     * T42 — View Inventory
     */
    public function test_T42_vendor_can_view_inventory_summary(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $listing = MarketplaceListing::factory()->create([
            'seller_id'            => $vendor->id,
            'available_stock_unit' => 50
        ]);

        // One accepted order item for 10 units
        OrderItem::factory()->create([
            'listing_id'    => $listing->id,
            'vendor_id'     => $vendor->id,
            'status'        => OrderItem::STATUS_ACCEPTED,
            'quantity_unit' => 10
        ]);

        $response = $this->actingAs($vendor)->getJson('/api/vendor/inventory');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.inventory_summary.available_stock_unit', 50)
                 ->assertJsonPath('data.0.inventory_summary.total_accepted_unit', 10);
    }

    /**
     * T43 — Revenue in Inventory Detail
     */
    public function test_T43_revenue_counts_only_paid_orders(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $listing = MarketplaceListing::factory()->create(['seller_id' => $vendor->id]);

        // Paid order (should count towards revenue)
        OrderItem::factory()->create([
            'listing_id'     => $listing->id,
            'vendor_id'      => $vendor->id,
            'payment_status' => OrderItem::PAYMENT_PAID,
            'subtotal'       => 1500.00
        ]);

        // Unpaid order (should NOT count towards revenue)
        OrderItem::factory()->create([
            'listing_id'     => $listing->id,
            'vendor_id'      => $vendor->id,
            'payment_status' => OrderItem::PAYMENT_UNPAID,
            'subtotal'       => 2000.00
        ]);

        $response = $this->actingAs($vendor)->getJson("/api/vendor/inventory/{$listing->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('stats.total_revenue', 1500.00);
    }

    /**
     * T44 — Restock Listing
     */
    public function test_T44_vendor_can_restock_listing(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $listing = MarketplaceListing::factory()->create([
            'seller_id'            => $vendor->id,
            'available_stock_unit' => 10
        ]);

        $response = $this->actingAs($vendor)->patchJson("/api/vendor/inventory/{$listing->id}/restock", [
            'add_unit' => 25
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('new_stock_unit', 35);

        $this->assertEquals(35, $listing->fresh()->available_stock_unit);
    }

    /**
     * T45 — Restock Reactivates Inactive Listing
     */
    public function test_T45_restocking_reactivates_listing(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $listing = MarketplaceListing::factory()->create([
            'seller_id'            => $vendor->id,
            'available_stock_unit' => 0,
            'status'               => MarketplaceListing::STATUS_INACTIVE
        ]);

        $response = $this->actingAs($vendor)->patchJson("/api/vendor/inventory/{$listing->id}/restock", [
            'add_unit' => 10
        ]);

        $response->assertStatus(200);
        $this->assertEquals(MarketplaceListing::STATUS_ACTIVE, $listing->fresh()->status);
    }

    /**
     * T47 & T48 — Low/Out Stock Dashboard Alerts
     */
    public function test_T47_T48_dashboard_stock_alerts_correctness(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        
        // One Out of Stock
        MarketplaceListing::factory()->create([
            'seller_id' => $vendor->id, 
            'available_stock_unit' => 0
        ]);
        
        // One Low Stock (<= 10)
        MarketplaceListing::factory()->create([
            'seller_id' => $vendor->id, 
            'available_stock_unit' => 5
        ]);
        
        // One Healthy Stock
        MarketplaceListing::factory()->create([
            'seller_id' => $vendor->id, 
            'available_stock_unit' => 50
        ]);

        $response = $this->actingAs($vendor)->getJson('/api/vendor/inventory');

        $response->assertStatus(200)
                 ->assertJsonPath('stock_summary.out_of_stock', 1)
                 ->assertJsonPath('stock_summary.low_stock', 1);
    }
}
