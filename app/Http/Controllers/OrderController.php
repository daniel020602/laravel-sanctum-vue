<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Assuming you have an Order model
use App\Models\OrderProducts; // Assuming you have an OrderProducts model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Menu;

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
    public function store(Request $request)
    {
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

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Gate::authorize('ownerOrAdmin', [Auth::user(), $id]);
        $order = Order::with('order_products')->findOrFail($id);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery') {
            return response()->json(['message' => 'Cannot update order.'], 403);
        }
        $order->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        if($order->status === 'completed' or $order->status === 'prepared' or $order->status === 'delivery') {
            return response()->json(['message' => 'Cannot delete order.'], 403);
        }
        $order->delete();
    }
    public function status(Request $request, string $id)
    {
        Gate::authorize('admin', Auth::user());
        $order = Order::findOrFail($id);
        $order->status = $request->input('status');
        $order->save();
    }
    
}
