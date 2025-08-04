<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        $this->menu = Menu::factory()->create(['name' => 'Test Menu','type'=>'main', 'price' => 10]);
    }

    public function test_admin_can_list_all_orders()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order['id']]);
    }

    public function test_user_can_list_own_orders()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order['id']]);
    }

    public function test_user_cannot_list_others_orders()
    {
        $otherUser = User::factory()->create();
        $order = $this->actingAs($otherUser, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)
            ->assertJsonMissing(['id' => $order['id']]);
    }

    public function test_user_can_create_order()
    {
        $items = [
            ['id' => $this->menu->id, 'quantity' => 2]
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => $items
        ]);
        $response->assertStatus(201)
            ->assertJsonFragment(['user_id' => $this->user->id, 'total_amount' => 20]);
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total_amount' => 20]);
        $this->assertDatabaseHas('order_products', ['order_id' => $response->json('id'), 'menu_id' => $this->menu->id, 'quantity' => 2]);
    }

    public function test_cannot_create_order_with_status()
    {
        $items = [
            ['id' => $this->menu->id, 'quantity' => 1]
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => $items,
            'status' => 'completed'
        ]);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Status cannot be set during order creation.']);
    }

    public function test_user_can_view_own_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order['id']]);
    }

    public function test_admin_can_view_any_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order['id']]);
    }

    public function test_user_cannot_view_others_order()
    {
        $otherUser = User::factory()->create();
        $order = $this->actingAs($otherUser, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/orders/{$order['id']}");
        $response->assertStatus(403);
    }

    public function test_user_can_update_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $items = [
            ['id' => $this->menu->id, 'quantity' => 3]
        ];
        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'items' => $items
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['total_amount' => 30]);
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'total_amount' => 30]);
        $this->assertDatabaseHas('order_products', ['order_id' => $order['id'], 'menu_id' => $this->menu->id, 'quantity' => 3]);
    }

    public function test_user_cannot_update_order_with_status()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'status' => 'completed',
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ]);
        $response->assertStatus(403)
            ->assertJson(['message' => 'Status cannot be updated directly.']);
    }

    public function test_user_cannot_update_completed_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();

        // Update status, but don't overwrite $order
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed'
        ]);

        // Optionally, refresh the order from DB if you want to check status
        // $orderModel = Order::find($order['id']);
        // echo "Order status updated to completed: " . $orderModel->status . "\n";

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order['id']}", [
            'items' => [ ['id' => $this->menu->id, 'quantity' => 1] ]
        ]);
        $response->assertStatus(403)
            ->assertJson(['message' => 'Cannot update order.']);
    }

    public function test_user_can_delete_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/orders/{$order['id']}");
        $response->assertStatus(200)
            ->assertJson(['message' => 'Order cancelled successfully.']);
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'status' => 'cancelled']);
    }

    public function test_user_cannot_delete_completed_order()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed'
        ]);
        
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/orders/{$order['id']}");
        $response->assertStatus(403)
            ->assertJson(['message' => 'Cannot delete order.']);
    }

    public function test_admin_can_update_order_status()
    {
        $order = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['id' => $this->menu->id, 'quantity' => 2]
            ]
        ])->json();
        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order['id']}/status", [
            'status' => 'completed'
        ]);
        $order = Order::find($order['id']);
        $this->assertEquals('completed', $order->status);
    }
}
