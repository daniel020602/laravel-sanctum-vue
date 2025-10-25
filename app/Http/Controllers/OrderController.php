<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Assuming you have an Order model
use App\Models\OrderProducts; // Assuming you have an OrderProducts model
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Menu;
use App\Http\Requests\OrderRequest; // Assuming you have an OrderRequest for validation
use Stripe\Stripe;
use Stripe\Charge;
use Illuminate\Support\Facades\Log;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Gate::allows('admin', Auth::user())) {
            // If an email is provided, return orders for that user only
            if ($request->has('email')) {
                $email = $request->input('email');
                $user = User::where('email', 'like', '%' . $email . '%')->first();
                if ($user) {
                    $orders = Order::where('user_id', $user->id)->with('user')->get();
                } else {
                    // no user found for that email -> return empty array
                    $orders = [];
                }
            } else {
                // no filter -> return all orders including user relation
                $orders = Order::with('user')->get();
            }
        } else {
            $orders = Order::where('user_id', Auth::id())->get();
        }
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrderRequest $request)
    {
        if($request->has('status')) {
            return response()->json(['message' => 'Status cannot be set during order creation.'], 400);
        }
        $user = Auth::user();

        if ($request->boolean('is_delivery') && empty($user->address)) {
            return response()->json(['message' => 'You must provide an address to request delivery.'], 400);
        }
        $items = $request->input('items');
        $price = 0;
        // Calculate total price first
        foreach ($items as $item) {
            $product = Menu::findOrFail($item['id']);
            $price += $product->price * $item['quantity'];
        }

        // Create the order
        $order = Order::create([
            'user_id' => Auth::id(),
            'status' => 'pending',
            'total_amount' => $price,
            'is_paid' => $request->boolean('is_paid', false), // Default to false if not provided
            'is_delivery' => $request->boolean('is_delivery', false), // Default to
        ]);

        // Attach products to the order
        foreach ($items as $item) {
            $product = Menu::findOrFail($item['id']); // Fetch the product to get its price
            OrderProducts::create([
                'order_id' => $order->id,
                'menu_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $product->price, // Add this line

            ]);
        }

        return response()->json($order , 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with('orderproducts', 'user')->findOrFail($id);
        $this->authorize('ownerOrAdmin', $order);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderRequest $request, string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('ownerOrAdmin', $order);
        if ($request->has('status')) {
            return response()->json(['message' => 'Status cannot be updated directly.'], 403);
        }
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery' or $order->status === 'cancelled' or $order->is_paid==true) {
            return response()->json(['message' => 'Cannot update order.'], 403);
        }

        // Update order fields except status
       $order->update($request->except('status'));

        // If items are provided, update order_products
        if ($request->has('items')) {
            $items = $request->input('items');
            $price = 0;
            // Remove old products for this order
            OrderProducts::where('order_id', $order->id)->delete();
            // Add new products
            foreach ($items as $item) {
                $product = Menu::findOrFail($item['id']);
                $price += $product->price * $item['quantity'];
                OrderProducts::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
            }
            // Update total price
            $order->update(['total_amount' => $price]);
        }

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('ownerOrAdmin', $order);
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery' or $order->status === 'cancelled' or $order->is_paid==true) {
            return response()->json(['message' => 'Cannot delete order.'], 403);
        }
        $order->status = 'cancelled'; // Soft delete by changing status
        $order->save();
        OrderProducts::where('order_id', $id)->delete(); // Remove associated products
        return response()->json(['message' => 'Order cancelled successfully.'], 200);
    }
    public function status(Request $request, string $id)
    {
        try {
            Gate::authorize('admin');
            $order = Order::findOrFail($id);

            // Validate status against allowed values to avoid DB enum truncation
            $allowed = ['pending', 'prepared', 'delivery', 'completed', 'cancelled'];
            $newStatus = (string) $request->input('status');
            if (! in_array($newStatus, $allowed, true)) {
                return response()->json(['message' => 'Invalid status value'], 400);
            }

            $order->status = $newStatus;
            $order->is_paid = $request->boolean('is_paid', $order->is_paid); // Update is_paid if provided
            $order->save();
            return response()->json($order, 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Access denied'], 403);
        } catch (\Exception $e) {
            // log unexpected errors
            \Illuminate\Support\Facades\Log::error('Failed to change order status', ['order_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Server error changing order status: ' . $e->getMessage()], 500);
        }
    }
    
    public function pay(string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('ownerOrAdmin', $order);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is not in a payable state.'], 400);
        }
        // Ensure amount in cents is an integer
        $amountCents = (int) round($order->total_amount * 100);
        if ($amountCents <= 0) {
            Log::error('Order payment attempted with invalid amount', ['order_id' => $order->id, 'amount' => $order->total_amount]);
            return response()->json(['message' => 'Invalid order amount'], 400);
        }

        // Dev/test mock path: allow local environment or STRIPE_MOCK=true to bypass Stripe
        if (app()->environment('local') || env('STRIPE_MOCK') === 'true') {
            $mockCharge = [
                'id' => 'ch_mock_' . uniqid(),
                'amount' => $amountCents,
                'status' => 'succeeded',
            ];

            if (request()->has('mock_charge_status')) {
                $mockCharge['status'] = request()->input('mock_charge_status');
            }

            if ($mockCharge['status'] !== 'succeeded') {
                return response()->json(['message' => 'Payment failed (mock)'], 402);
            }

            // Mark order paid and return
            $order->is_paid = true;
            $order->save();

            return response()->json(['message' => 'Payment successful (mock)', 'order' => $order], 200);
        }

        // prefer configured services key, fall back to raw env; return a helpful error when missing
        $stripeKey = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($stripeKey)) {
            // log for server-side debugging without exposing the key to clients
            Log::error('Stripe API key is missing. Ensure STRIPE_SECRET is set in .env and config/services.php includes stripe.secret');
            return response()->json(['message' => 'Stripe API key not configured. Set STRIPE_SECRET in your .env and run php artisan config:clear'], 500);
        }
        Stripe::setApiKey($stripeKey);

        // Require stripe token from client for real charges
        $stripeToken = request()->input('stripeToken');
        if (empty($stripeToken)) {
            return response()->json(['message' => 'stripeToken is required'], 400);
        }

        try {
            $charge = Charge::create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'description' => 'Order Payment',
                'source' => $stripeToken,
            ]);

            // Update order status to paid
            $order->is_paid = true;
            $order->save();

            return response()->json(['message' => 'Payment successful', 'order' => $order], 200);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error during order payment', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Payment failed (Stripe error): ' . $e->getMessage()], 502);
        } catch (\Exception $e) {
            Log::error('Unexpected error during order payment', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
    public function userOrders()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
                       ->whereIn('status', ['pending', 'prepared', 'delivery'])
                       ->with('orderproducts')
                       ->get();

        return response()->json($orders);
    }

    public function inProgress()
    {
        Gate::authorize('admin', Auth::user());
        $orders = Order::whereIn('status', ['pending', 'prepared', 'delivery'])->with('user')
        ->get();
        return response()->json($orders);
    }
    public function statistics()
    {
        Gate::authorize('admin', Auth::user());

        $totalOrders = Order::count();
        $totalRevenue = Order::where('is_paid', true)->sum('total_amount');
        $pendingOrders = Order::where('status', 'pending')->count();
        $preparedOrders = Order::where('status', 'prepared')->count();
        $deliveryOrders = Order::where('status', 'delivery')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();
        $mostCommonItems = OrderProducts::select('menu_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->groupBy('menu_id')
            ->orderByDesc('total_quantity')
            ->with('menu')
            ->take(5)
            ->get();
        
        // Group order products by the Menu.type and sum quantities per type
        $typeOfMenuItems = OrderProducts::join('menus', 'order_products.menu_id', '=', 'menus.id')
            ->select('menus.type as type')
            ->selectRaw('SUM(order_products.quantity) as total_quantity')
            ->groupBy('menus.type')
            ->orderByDesc('total_quantity')
            ->get();
        
        $stats = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pending_orders' => $pendingOrders,
            'prepared_orders' => $preparedOrders,
            'delivery_orders' => $deliveryOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'most_common_items' => $mostCommonItems,
            'type_of_menu_items' => $typeOfMenuItems,
        ];

        return response()->json($stats);
    }
}
