<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// ─────────────────────────────────────────────────────────────────────────────
// T27–T35  Payment Security Tests
//
// Razorpay's API is mocked using Mockery so no real HTTP calls are made.
// Each test proves one specific security guard in PaymentController::payNow().
// ─────────────────────────────────────────────────────────────────────────────
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a minimal accepted order item owned by $customer, sold by $vendor.
     * The item has a known subtotal so we can craft amount-mismatch scenarios.
     */
    private function createAcceptedOrderItem(
        User $customer,
        User $vendor,
        float $subtotal = 1000.00,
        float $delivery = 200.00
    ): OrderItem {
        $address = Address::create([
            'user_id'        => $customer->id,
            'label'          => 'Home',
            'address_line_1' => '123 Test Street',
            'city'           => 'TestCity',
            'state'          => 'TestState',
            'pincode'        => '000000',
            'is_default'     => true,
        ]);

        $order = Order::create([
            'customer_id'         => $customer->id,
            'delivery_address_id' => $address->id,
            'status'              => Order::STATUS_PENDING,
            'total_amount'        => $subtotal + $delivery,
        ]);

        return OrderItem::create([
            'order_id'        => $order->id,
            'vendor_id'       => $vendor->id,
            'product_id'      => 1,
            'listing_id'      => 1,
            'quantity_unit'   => 2,
            'subtotal'        => $subtotal,
            'delivery_charge' => $delivery,
            'status'          => OrderItem::STATUS_ACCEPTED,
            'payment_status'  => OrderItem::PAYMENT_UNPAID,
        ]);
    }

    /**
     * Build a fake Razorpay payment object (stdClass) so we can mock the SDK.
     */
    private function fakeRazorpayPayment(
        string $status = 'captured',
        int $amountPaise = 120000,
        string $currency = 'INR'
    ): object {
        $payment           = new \stdClass();
        $payment->status   = $status;
        $payment->amount   = $amountPaise;
        $payment->currency = $currency;
        return $payment;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T31 — Replay Attack: Cannot reuse a payment_id on an already-paid order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_already_paid_order_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);

        // Force the order item to already-paid
        $item->update(['payment_status' => OrderItem::PAYMENT_PAID]);

        $response = $this->actingAs($customer)->postJson(
            "/api/orders/{$item->id}/pay-now",
            ['razorpay_payment_id' => 'pay_alreadyusedid']
        );

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'This order has already been paid.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T33 — Cannot pay a pending order (not yet accepted by vendor)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_pending_order_cannot_be_paid(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);
        $item->update(['status' => OrderItem::STATUS_PENDING]);

        $response = $this->actingAs($customer)->postJson(
            "/api/orders/{$item->id}/pay-now",
            ['razorpay_payment_id' => 'pay_anyid']
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('pending', $response->json('message'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T32 — Cross-customer attack: Customer B cannot pay Customer A's order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_customer_cannot_pay_another_customers_order(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);
        $vendor    = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customerA, $vendor);

        // CustomerB tries to pay CustomerA's order
        $response = $this->actingAs($customerB)->postJson(
            "/api/orders/{$item->id}/pay-now",
            ['razorpay_payment_id' => 'pay_attackersdid']
        );

        // findCustomerItem() returns null → 404 "Order not found"
        $response->assertStatus(404)
                 ->assertJsonPath('message', 'Order not found.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T07 — Unauthenticated request to pay endpoint is rejected
    // ─────────────────────────────────────────────────────────────────────────
    public function test_unauthenticated_user_cannot_call_pay_now(): void
    {
        $response = $this->postJson('/api/orders/999/pay-now', [
            'razorpay_payment_id' => 'pay_anything',
        ]);

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T40 — Pay later request rejected on already-paid order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_pay_later_rejected_if_order_already_paid(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);
        $item->update(['payment_status' => OrderItem::PAYMENT_PAID]);

        $response = $this->actingAs($customer)->postJson(
            "/api/orders/{$item->id}/pay-later",
            ['days_requested' => 3]
        );

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'This order has already been paid.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T41 — Duplicate pay-later request is rejected
    // ─────────────────────────────────────────────────────────────────────────
    public function test_duplicate_pay_later_request_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);
        $item->update([
            'payment_status' => OrderItem::PAYMENT_LATER,
            'payment_due_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($customer)->postJson(
            "/api/orders/{$item->id}/pay-later",
            ['days_requested' => 2]
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('Pay later already requested', $response->json('message'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T36 — Valid pay-later request is accepted
    // ─────────────────────────────────────────────────────────────────────────
    public function test_pay_later_request_is_created_successfully(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);

        $response = $this->actingAs($customer)->postJson(
            "/api/orders/{$item->id}/pay-later",
            ['days_requested' => 3]
        );

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.payment_status', 'pay_later')
                 ->assertJsonPath('data.days_requested', 3);

        // Verify DB state
        $this->assertDatabaseHas('order_items', [
            'id'             => $item->id,
            'payment_status' => OrderItem::PAYMENT_LATER,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T22 — Vendor can view their orders
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vendor_can_view_their_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $this->createAcceptedOrderItem($customer, $vendor);

        $response = $this->actingAs($vendor)->getJson('/api/vendor/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta']);

        // Vendor sees their own order
        $this->assertCount(1, $response->json('data'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T23 — Vendor can accept a pending order (stock check, no deduction yet)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vendor_can_accept_pending_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);
        // Reset to pending so we can test accept
        $item->update(['status' => OrderItem::STATUS_PENDING]);

        $response = $this->actingAs($vendor)->postJson(
            "/api/vendor/orders/{$item->id}/accept"
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_items', [
            'id'     => $item->id,
            'status' => OrderItem::STATUS_ACCEPTED,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T25 — Vendor can decline a pending order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vendor_can_decline_pending_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        $item = $this->createAcceptedOrderItem($customer, $vendor);
        $item->update(['status' => OrderItem::STATUS_PENDING]);

        $response = $this->actingAs($vendor)->postJson(
            "/api/vendor/orders/{$item->id}/decline",
            ['rejection_reason' => 'Out of stock in that area.']
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_items', [
            'id'     => $item->id,
            'status' => OrderItem::STATUS_DECLINED,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // T26 — Cannot accept an already-accepted order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vendor_cannot_accept_already_accepted_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);

        // Already accepted
        $item = $this->createAcceptedOrderItem($customer, $vendor);

        $response = $this->actingAs($vendor)->postJson(
            "/api/vendor/orders/{$item->id}/accept"
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('already been', $response->json('message'));
    }
}
