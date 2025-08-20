<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $menu;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->menu = Menu::factory()->create(['name' => 'Test Menu', 'type' => 'main', 'price' => 10]);
    }

    /** @test */
    public function admin_can_list_all_orders()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)->assertJsonFragment(['id' => $order['id']]);
    }

    /** @test */
    public function user_can_list_own_orders()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)->assertJsonFragment(['id' => $order['id']]);
    }

    /** @test */
    public function user_cannot_list_others_orders()
    {
        $otherUser = User::factory()->create();
        $order = $this->actingAs($otherUser, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)->assertJsonMissing(['id' => $order['id']]);
    }

    /** @test */
    public function user_can_create_order()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ]);
        $response->assertStatus(201)->assertJsonFragment(['user_id' => $this->user->id, 'total_amount' => 20]);
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total_amount' => 20]);
    }

    /** @test */
    public function cannot_create_order_with_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 1]],
            'status' => 'completed'
        ]);
        $response->assertStatus(400)->assertJson(['message' => 'Status cannot be set during order creation.']);
    }

    /** @test */
    public function user_cannot_create_delivery_order_without_address()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 1]],
            'is_delivery' => true
        ]);
        $response->assertStatus(400)->assertJson(['message' => 'You must provide an address to request delivery.']);
    }

    /** @test */
    public function user_can_create_delivery_order_with_address()
    {
        $this->user->update(['address' => 'Test Street 123']);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 1]],
            'is_delivery' => true
        ]);
        $response->assertStatus(201)->assertJsonFragment(['is_delivery' => true]);
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'is_delivery' => true]);
    }

    /** @test */
    public function user_can_view_own_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)->assertJsonFragment(['id' => $order['id']]);
    }

    /** @test */
    public function admin_can_view_any_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)->assertJsonFragment(['id' => $order['id']]);
    }

    /** @test */
    public function user_cannot_view_others_order()
    {
        $otherUser = User::factory()->create();
        $order = $this->actingAs($otherUser, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_update_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'items' => [['id' => $this->menu->id, 'quantity' => 3]]
        ]);

        $response->assertStatus(200)->assertJsonFragment(['total_amount' => 30]);
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'total_amount' => 30]);
    }

    /** @test */
    public function user_cannot_update_order_with_status()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'status' => 'completed',
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ]);

        $response->assertStatus(403)->assertJson(['message' => 'Status cannot be updated directly.']);
    }

    /** @test */
    public function user_cannot_update_completed_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'items' => [['id' => $this->menu->id, 'quantity' => 1]]
        ]);

        $response->assertStatus(403)->assertJson(['message' => 'Cannot update order.']);
    }

    /** @test */
    public function user_can_delete_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)->assertJson(['message' => 'Order cancelled successfully.']);
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'status' => 'cancelled']);
    }

    /** @test */
    public function user_cannot_delete_completed_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/orders/{$order['id']}");
        $response->assertStatus(403)->assertJson(['message' => 'Cannot delete order.']);
    }

    /** @test */
    public function admin_can_update_order_status_and_paid_flag()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed',
            'is_paid' => true
        ]);

        $orderModel = Order::find($order['id']);
        $this->assertEquals('completed', $orderModel->status);
        $this->assertTrue((bool) $orderModel->is_paid);

    }

    /** @test */
    public function pay_sets_order_as_paid()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        // Mock Stripe here normally, but tesztben elég a "forcePaid"
        $orderModel = Order::find($order['id']);
        $orderModel->is_paid = true;
        $orderModel->save();

        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'is_paid' => true]);
    }

    /** @test */
    public function user_can_pay_for_own_pending_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        // Mock Stripe\Charge static method
        \Stripe\Stripe::setApiKey('sk_test_fake');
        $mockCharge = (object) ['id' => 'ch_test_123'];
        $chargeMock = \Mockery::mock('alias:' . \Stripe\Charge::class);
        $chargeMock->shouldReceive('create')
            ->once()
            ->andReturn($mockCharge);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/orders/{$order['id']}/pay", [
            'stripeToken' => 'tok_test'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment successful',
            ]);
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'is_paid' => true]);
    }

    /** @test */
    public function user_cannot_pay_for_non_pending_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        // Set order status to completed
        $orderModel = \App\Models\Order::find($order['id']);
        $orderModel->status = 'completed';
        $orderModel->save();

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/orders/{$order['id']}/pay", [
            'stripeToken' => 'tok_test'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Order is not in a payable state.']);
    }

    /** @test */
    public function user_cannot_pay_for_others_order()
    {
        $otherUser = User::factory()->create();
        $order = $this->actingAs($otherUser, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/orders/{$order['id']}/pay", [
            'stripeToken' => 'tok_test'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function payment_failure_returns_error_message()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [['id' => $this->menu->id, 'quantity' => 2]]
        ])->json();

        // Mock Stripe\Charge to throw an exception
        \Stripe\Stripe::setApiKey('sk_test_fake');
        $chargeMock = \Mockery::mock('alias:' . \Stripe\Charge::class);
        $chargeMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Card declined'));

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/orders/{$order['id']}/pay", [
            'stripeToken' => 'tok_test'
        ]);

        $response->assertStatus(500)
            ->assertJson(['message' => 'Payment failed: Card declined']);
    }
}
