<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_T27_successful_razorpay_payment(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        // Create listing
        $listing = MarketplaceListing::factory()->create(['seller_id' => $vendor->id]);

        // Create order for the specific customer
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        // Create item for that order
        $item = OrderItem::factory()->create([
            'order_id'       => $order->id,
            'listing_id'     => $listing->id,
            'vendor_id'      => $vendor->id,
            'status'         => OrderItem::STATUS_ACCEPTED,
            'payment_status' => OrderItem::PAYMENT_UNPAID,
            'subtotal'       => 1000.00,
            'delivery_charge' => 200.00,
            'quantity_unit'  => 5
        ]);

        // ── Mock Razorpay ───────────────────────────────────────────────────
        // We use Mockery to intercept the API call.
        // Important: Use overload: only if the class is not already loaded.
        // In Laravel, it's often better to mock the instance if used via DI,
        // but here it's newed up in the controller.
        $mockApi = \Mockery::mock('overload:Razorpay\Api\Api');
        $mockPayment = \Mockery::mock('stdClass');
        
        $mockPayment->status   = 'captured';
        $mockPayment->amount   = 120000; // 1200.00 INR (subtotal 1000 + deliv 200)
        $mockPayment->currency = 'INR';

        $mockApi->shouldReceive('payment->fetch')
                ->with('pay_test_id')
                ->andReturn($mockPayment);

        $response = $this->actingAs($customer, 'sanctum')->postJson("/api/orders/{$item->id}/pay-now", [
            'razorpay_payment_id' => 'pay_test_id'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(OrderItem::PAYMENT_PAID, $item->fresh()->payment_status);
        $this->assertEquals('processing', $item->fresh()->status);
        
        // Final stock check
        $this->assertEquals(95, $listing->fresh()->available_stock_unit);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_T28_rejects_underpayment(): void
    {
        Log::shouldReceive('warning')->once();

        $customer = User::factory()->create(['role' => 'customer']);
        $order    = Order::factory()->create(['customer_id' => $customer->id]);
        $item     = OrderItem::factory()->create([
            'order_id'       => $order->id,
            'status'         => OrderItem::STATUS_ACCEPTED,
            'payment_status' => OrderItem::PAYMENT_UNPAID,
            'subtotal'       => 50000.00,
            'delivery_charge' => 0
        ]);

        $mockApi = \Mockery::mock('overload:Razorpay\Api\Api');
        $mockPayment = \Mockery::mock('stdClass');
        $mockPayment->status   = 'captured';
        $mockPayment->amount   = 100; // Attacker pays ₹1
        $mockPayment->currency = 'INR';

        $mockApi->shouldReceive('payment->fetch')->andReturn($mockPayment);

        $response = $this->actingAs($customer, 'sanctum')->postJson("/api/orders/{$item->id}/pay-now", [
            'razorpay_payment_id' => 'pay_attacker_id'
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('amount mismatch', $response->json('message'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_T30_rejects_non_inr_currency(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order    = Order::factory()->create(['customer_id' => $customer->id]);
        $item     = OrderItem::factory()->create([
            'order_id' => $order->id,
            'status'   => 'accepted'
        ]);

        $mockApi = \Mockery::mock('overload:Razorpay\Api\Api');
        $mockPayment = \Mockery::mock('stdClass');
        $mockPayment->status   = 'captured';
        $mockPayment->amount   = 10000;
        $mockPayment->currency = 'USD';

        $mockApi->shouldReceive('payment->fetch')->andReturn($mockPayment);

        $response = $this->actingAs($customer, 'sanctum')->postJson("/api/orders/{$item->id}/pay-now", [
            'razorpay_payment_id' => 'pay_usd_id'
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Invalid currency', $response->json('message'));
    }

    /**
     * T31 — Replay Attack
     */
    public function test_T31_cannot_pay_already_paid_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order    = Order::factory()->create(['customer_id' => $customer->id]);
        $item     = OrderItem::factory()->create([
            'order_id'       => $order->id,
            'status'         => 'accepted',
            'payment_status' => 'paid'
        ]);

        $response = $this->actingAs($customer, 'sanctum')->postJson("/api/orders/{$item->id}/pay-now", [
            'razorpay_payment_id' => 'pay_reuse_id'
        ]);

        $response->assertStatus(422);
    }

    /**
     * T37 — Vendor Approves Pay Later
     */
    public function test_T37_vendor_approves_pay_later_deducts_stock(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);
        $order    = Order::factory()->create(['customer_id' => $customer->id]);

        $listing = MarketplaceListing::factory()->create([
            'seller_id'            => $vendor->id,
            'available_stock_unit' => 100
        ]);

        $item = OrderItem::factory()->create([
            'order_id'       => $order->id,
            'listing_id'     => $listing->id,
            'vendor_id'      => $vendor->id,
            'status'         => OrderItem::STATUS_ACCEPTED,
            'payment_status' => OrderItem::PAYMENT_LATER,
            'quantity_unit'  => 10
        ]);

        $response = $this->actingAs($vendor, 'sanctum')->postJson("/api/orders/{$item->id}/pay-later/accept");

        $response->assertStatus(200);
        $this->assertEquals(90, $listing->fresh()->available_stock_unit);
    }
}
