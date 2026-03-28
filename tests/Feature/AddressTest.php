<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    /**
     * T08 — Add Delivery Address
     */
    public function test_T08_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/addresses', [
            'label'          => 'Office',
            'address_line_1' => '456 Business Road',
            'city'           => 'Mumbai',
            'state'          => 'Maharashtra',
            'pincode'        => '400001',
            'latitude'       => 19.0760,
            'longitude'      => 72.8777,
            'is_default'     => true,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('label', 'Office');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'label'   => 'Office',
            'is_default' => true,
        ]);
    }

    /**
     * T09 — Set Default Address
     */
    public function test_T09_setting_new_default_clears_old_one(): void
    {
        $user = User::factory()->create();
        
        // Create first default address
        $oldDefault = Address::factory()->create([
            'user_id'    => $user->id,
            'is_default' => true,
        ]);

        // Create second address and set it as default
        $newAddress = Address::factory()->create([
            'user_id'    => $user->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/api/addresses/{$newAddress->id}/default");

        $response->assertStatus(200);

        // Verify old one is NO LONGER default
        $this->assertFalse($oldDefault->fresh()->is_default);
        // Verify new one IS default
        $this->assertTrue($newAddress->fresh()->is_default);
    }

    /**
     * T10 — Get Default Address
     */
    public function test_T10_can_retrieve_default_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id'    => $user->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/address/default');

        $response->assertStatus(200)
                 ->assertJsonPath('id', $address->id);
    }

    /**
     * T11 — Delete Address
     */
    public function test_T11_user_can_delete_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/addresses/{$address->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Address deleted successfully');

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_user_cannot_delete_someone_elses_address(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $addressA = Address::factory()->create(['user_id' => $userA->id]);

        // User B tries to delete User A's address
        $response = $this->actingAs($userB)->deleteJson("/api/addresses/{$addressA->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('addresses', ['id' => $addressA->id]);
    }
}
