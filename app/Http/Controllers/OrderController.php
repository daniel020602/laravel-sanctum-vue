<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Assuming you have an Order model
use App\Models\OrderProducts; // Assuming you have an OrderProducts model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Menu;
use App\Http\Requests\OrderRequest; // Assuming you have an OrderRequest for validation
use Stripe\Stripe;
use Stripe\Charge;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Gate::allows('admin', Auth::user())) {
            $orders = Order::all();
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
        $order = Order::with('orderproducts')->findOrFail($id);
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
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery') {
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
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery' or $order->status === 'cancelled') {
            return response()->json(['message' => 'Cannot delete order.'], 403);
        }
        $order->status = 'cancelled'; // Soft delete by changing status
        $order->save();
        OrderProducts::where('order_id', $id)->delete(); // Remove associated products
        return response()->json(['message' => 'Order cancelled successfully.'], 200);
    }
    public function status(Request $request, string $id)
    {
        Gate::authorize('admin', Auth::user());
        $order = Order::findOrFail($id);
        $order->status = $request->input('status');
        $order->is_paid = $request->boolean('is_paid', $order->is_paid); // Update is_paid if provided
        $order->save();
        return response()->json($order, 200);
    }
    
    public function pay(string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('ownerOrAdmin', $order);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is not in a payable state.'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $charge = Charge::create([
                'amount' => $order->total_amount * 100, // Convert to cents
                'currency' => 'usd',
                'description' => 'Order Payment',
                'source' => request()->input('stripeToken'), // Assuming you pass the token from the frontend
            ]);

            // Update order status to paid
            $order->is_paid = true;
            $order->save();

            return response()->json(['message' => 'Payment successful', 'order' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
}
